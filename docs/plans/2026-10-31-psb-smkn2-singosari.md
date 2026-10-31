# Implementation Plan — Sistem PSB SMKN 2 Singosari

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bangun aplikasi web Penerimaan Siswa Baru SMKN 2 Singosari (portal calon siswa + dashboard admin) sesuai spec.

**Architecture:** Laravel 10 MVC; logika inti (nomor pendaftaran, jadwal, nilai, ranking) di `app/Services/Psb/*`; view Blade dengan dua layout (publik/portal = Bootstrap 5, admin = SB Admin 2 yang sudah ada di `public/Assets`); proses ranking berjalan dalam satu transaksi DB.

**Tech Stack:** PHP 8.1, Laravel ^10.10, Blade, Bootstrap 5.2, SB Admin 2, MySQL 8 (produksi) / SQLite `:memory:` (test), PHPUnit 10, maatwebsite/excel ^3.1 (ekspor Excel).

**Spec:** `docs/user-requirements.md` + `docs/system-design.md` (kini berada di `~/.opencode/plan/psb-smkn2-singosari/`; pindahkan ke `docs/` repo sebagai Task 0 di bawah).

## Global Constraints

- PHP `^8.1`, Laravel `^10.10`, MySQL 8 (target); test pakai SQLite in-memory via `RefreshDatabase`.
- NISN: tepat 10 digit, **unique** (constraint DB + validasi form).
- Nomor pendaftaran: format `PSB-YYYY-XXXXXX` (6 digit urut per tahun), unik.
- Bobot nilai: rapor 0.4, prestasi 0.3, kunjungan 0.3 (dari `config/psb.php`).
- Dokumen: 5 jenis `ktp|kk|ijazah|rapor|foto`, tipe PDF/JPG/PNG, maks 2 MB/file.
- Pilihan jurusan: maks 2; prioritas 1 wajib.
- Login: 3x gagal → kunci 15 menit. Link verifikasi email expiry 7 hari.
- 36 siswa/rombel; cadangan default `ceil(10% × kuota)`.
- Tie-break ranking: `total DESC, nilai_rapor DESC, dikirim_at ASC, id ASC`.
- Semua route `/admin` di belakang middleware `auth` + alias `role:admin` (403 untuk role lain).
- File dokumen disimpan `storage/app/private/dokumen/{nomor}/`, tidak pernah di `public/`; akses hanya lewat controller admin.
- UI berbahasa Indonesia. Semua perubahan status penting masuk `audit_log`.
- Sesi: `Carbon::setTestNow()` wajib dipakai test yang bergantung periode jadwal.

## Review Focus

1. **NISN dobel karena race condition** (dua submit bersamaan) — constraint DB menangkap, user dapat pesan ramah, bukan error 500 → test di Task 4.
2. **Submit form setelah periode ditutup** (form dibuka sebelum tutup, dikirim sesudahnya) — server cek ulang periode saat submit → test di Task 4.
3. **Pilihan 2 masuk jurusan yang kuotanya juga terpakai prioritas 1-nya sendiri** — antrian P2 diproses *setelah* semua P1, hanya ke sisa kuota → test di Task 10.
4. **Edit nilai/ranking setelah pengumuman terbit** — harus diblok + tercatat di audit_log → test di Task 10.
5. **File upload ekstensi `.pdf` yang isinya binary bukan PDF** — validasi MIME server-side menolak → test di Task 4.

## File Structure

```
config/psb.php
app/Enums/Role.php  StatusPendaftar.php  JenisDokumen.php  TahapJadwal.php
app/Models/User.php (diedit: nisn, role)  Jurusan.php  Kuota.php  Pendaftar.php
app/Models/PendaftarPilihan.php  Dokumen.php  NilaiSeleksi.php  NilaiSeleksiRiwayat.php
app/Models/Hasil.php  JadwalPsb.php  AuditLog.php
app/Services/Psb/NomorPendaftaran.php  JadwalService.php  PendaftaranService.php
app/Services/Psb/DokumenService.php  NilaiService.php  RankingService.php
app/Services/Psb/PengumumanService.php  AuditService.php
app/Http/Middleware/EnsureUserIsAdmin.php
app/Http/Controllers/Portal/{Auth,Pendaftaran,Status,Hasil,Surat,CekStatus}Controller.php
app/Http/Controllers/Admin/{Dashboard,Jurusan,Jadwal,Pendaftar,Verifikasi,Nilai,
Ranking,Pengumuman,Laporan,Audit,Dokumen}Controller.php
routes/web.php (diedit: grup publik, portal, /admin)
resources/views/{public,portal,admin,layouts,auth}/*
database/migrations/*  database/factories/*  database/seeders/DatabaseSeeder.php
tests/Feature/{Foundation,Auth,Jadwal,Pendaftaran,StatusRevisi,JurusanKuota,
PendaftarAdmin,Verifikasi,Nilai,Ranking,PengumumanSurat,Laporan}Test.php
```

Konvensi: model `User` (tabel `users`) dipakai untuk kedua role; kolom `nisn` null untuk admin. Service menerima dan mengembalikan model; controller tipis.

---

### Task 0: Persiapan repo & dokumen

**Files:**
- Create: `docs/user-requirements.md`, `docs/system-design.md`, `docs/plans/2026-10-31-psb-smkn2-singosari.md`

- [ ] **Step 1: Salin 3 dokumen** dari `~/.opencode/plan/psb-smkn2-singosari/` ke `docs/` di repo (user-requirements.md, system-design.md, dan plan ini).
- [ ] **Step 2: Commit**

```bash
git add docs && git commit -m "docs: user requirements, system design, dan plan implementasi PSB"
```

---

### Task 1: Fondasi — config, enums, migration, model, seeder

**Files:**
- Create: `config/psb.php`, `app/Enums/{Role,StatusPendaftar,JenisDokumen,TahapJadwal}.php`, 10 migration, 9 model (di luar `User` yang diedit), 9 factory, `database/seeders/DatabaseSeeder.php`
- Modify: `app/Models/User.php` (fillable: nisn, role)
- Test: `tests/Feature/FoundationTest.php`

**Interfaces:**
- Produces: enum string-backed — `Role::{CALON_SISWA,ADMIN}` ('calon_siswa','admin'), `StatusPendaftar::{DRAF,MENUNGGU_VERIFIKASI,LULUS_VERIFIKASI,GUGUR,DITERIMA,CADANGAN,TIDAK_DITERIMA}`, `JenisDokumen::{KTP,KK,IJAZAH,RAPOR,FOTO}`, `TahapJadwal::{PENDAFTARAN,VERIFIKASI,PERBAIKAN,SELEKSI,PENGUMUMAN}`; model: `Jurusan` (relasi `kuota()` 1:1, `pendaftarPilihan()`), `Pendaftar` (`pilihan()`hasMany, `dokumen()`, `nilai()`hasOne, `hasil()`hasOne, `pengguna()`), `JadwalPsb` (unique `tahap`).

- [ ] **Step 1: Tulis test gagal** — `tests/Feature/FoundationTest.php`:

```php
test('seeder membuat 7 jurusan resmi dengan kuota rombel x 36 dan cadangan ceil 10%', function () {
    $j = Jurusan::all();
    expect($j)->toHaveCount(7);
    expect($j->pluck('kode'))->toHaveSameItemsAs(['TKJ','RPL','ANM','BCS','TAV','TEI','MKA']);
    $tkj = Jurusan::where('kode','TKJ')->first();
    expect($tkj->kuota->kuota)->toBe(108);          // 3 rombel x 36
    expect($tkj->kuota->cadangan)->toBe(11);        // ceil(108 x 0.1)
});
test('NISN tidak boleh duplikat di level database', function () {
    User::factory()->create(['nisn' => '1111111111']);
    User::factory()->create(['nisn' => '2222222222']);
    expect(fn () => User::factory()->create(['nisn' => '1111111111']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
test('5 baris jadwal_psb dengan tahap unique dibuat seeder', function () {
    expect(JadwalPsb::pluck('tahap'))->toHaveSameItemsAs(
        ['pendaftaran','verifikasi','perbaikan','seleksi','pengumuman']);
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** — `php artisan test tests/Feature/FoundationTest.php` → FAIL (tabel/model belum ada).
- [ ] **Step 3: Implementasi** — `config/psb.php` persis bag. 8 spec; enum; migration sesuai tabel spec §4 (kolom per ERD §3, termasuk `unique(pendaftar_id,jenis)` di dokumen, `unique(pendaftar_id,prioritas)`, `unique(pendaftar_id)` di nilai/hasil); model + factory (`PendaftarFactory` wajib field lengkap + pilihan 1 + 5 dokumen fake + nilai); `DatabaseSeeder` = admin (password dari env `ADMIN_PASSWORD`, default `admin1234`) + 7 jurusan (rombel awal: TKJ 3, RPL 3, ANM 2, BCS 2, TAV 2, TEI 2, MKA 2; kuota = rombel×36, cadangan = `ceil(kuota*0.1)`) + 5 jadwal contoh TA 2026/2027.
- [ ] **Step 4: Jalankan, pastikan lulus** — `php artisan test tests/Feature/FoundationTest.php` → PASS.
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat: fondasi config, enum, migration, model, seeder PSB"`

---

### Task 2: Autentikasi — register, login, lockout, verifikasi email

**Files:**
- Create: `app/Http/Controllers/Portal/AuthController.php`, `app/Http/Middleware/EnsureUserIsAdmin.php`, `resources/views/auth/{login,register,verify}.blade.php`, `resources/views/layouts/{portal,admin}.blade.php`
- Modify: `routes/web.php`, `app/Http/Kernel.php` (alias `role`)
- Test: `tests/Feature/AuthTest.php`

**Interfaces:**
- Produces: `AuthController::{showLogin,login,showRegister,register,logout,verifyEmail(string $token)}`; middleware alias `role:admin` (redirect 403); route `POST /login`, `POST /register`, `GET /verify-email/{token}`.

- [ ] **Step 1: Tulis test gagal** — `AuthTest.php`:

```php
test('register membuat akun calon_siswa dan mengirim link verifikasi', function () {
    Notification::fake();
    $r = $this->post('/register', ['name'=>'Budi','email'=>'budi@mail.com','nisn'=>'1234567890','password'=>'rahasia1','password_confirmation'=>'rahasia1']);
    $r->assertRedirect('/login');
    expect(User::where('email','budi@mail.com')->first()->role)->toBe('calon_siswa');
    Notification::assertSentTo(User::first(), \Illuminate\Auth\Notifications\VerifyEmail::class);
});
test('register menolak NISN duplikat', function () {
    User::factory()->create(['nisn'=>'1234567890']);
    $this->post('/register',['name'=>'C','email'=>'c@mail.com','nisn'=>'1234567890','password'=>'rahasia1','password_confirmation'=>'rahasia1'])
        ->assertSessionHasErrors('nisn');
});
test('3x login salah mengunci akun 15 menit', function () {
    $u = User::factory()->create();
    foreach (range(1,3) as $i) $this->post('/login',['email'=>$u->email,'password'=>'salah']);
    $this->post('/login',['email'=>$u->email,'password'=>$u->password ?? 'rahasia1'])->assertSessionHasErrors('email');
});
test('token verifikasi aktivasi akun; token kedaluwarsa 7 hari ditolak', function () {
    $u = User::factory()->unverified()->create();
    $token = sha1($u->getEmailForVerification());
    $this->get('/verify-email/'.$token)->assertSessionHasNoErrors();
    Carbon::setTestNow(now()->addDays(8));
    $u2 = User::factory()->unverified()->create();
    $this->get('/verify-email/'.sha1($u2->getEmailForVerification()))->assertSessionHasErrors();
    Carbon::setTestNow();
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** — `php artisan test tests/Feature/AuthTest.php` → FAIL.
- [ ] **Step 3: Implementasi** — auth manual (session, `Hash`): register validasi `nisn:regex:/^\d{10}$/` + unique users,nisn; lockout disimpan di session per email (`login_attempts`, `locked_until`); `EnsureUserIsAdmin` cek `$request->user()?->role === 'admin'`, selain itu `abort(403)`; registrasi non-verified tetap bisa login tapi dashboard mengarahkan ke `/verify-email` (pakai `MustVerifyEmail` untuk role calon_siswa).
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: autentikasi register/login/lockout/verifikasi email + middleware role"`

---

### Task 3: JadwalService + portal publik (beranda, cek status)

**Files:**
- Create: `app/Services/Psb/JadwalService.php`, `app/Http/Controllers/Portal/CekStatusController.php`, `resources/views/public/{home,cek-status}.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/JadwalTest.php`

**Interfaces:**
- Produces: `JadwalService::{tahapAktif(): ?TahapJadwal, isBuka(TahapJadwal $t): bool, statusSemua(): array<string, array{mulai,selesai,aktif}>}`.

- [ ] **Step 1: Tulis test gagal** — `JadwalTest.php`:

```php
test('isBuka true hanya saat now dalam periode tahap', function () {
    $s = app(JadwalService::class);
    JadwalPsb::where('tahap','pendaftaran')->update(['mulai'=>now()->subDay(),'selesai'=>now()->addDay()]);
    expect($s->isBuka(TahapJadwal::PENDAFTARAN))->toBeTrue();
    JadwalPsb::where('tahap','pendaftaran')->update(['mulai'=>now()->subDays(3),'selesai'=>now()->subDay()]);
    expect($s->isBuka(TahapJadwal::PENDAFTARAN))->toBeFalse();
});
test('beranda menampilkan tahap aktif dan 7 jurusan', function () {
    $this->get('/')->assertSee('Penerimaan Siswa Baru')->assertSee('TKJ')->assertSee('Rekayasa Perangkat Lunak');
});
test('cek status publik: no. pendaftaran + tgl lahir cocok menampilkan status; tidak cocok error', function () {
    $p = Pendaftar::factory()->create();
    $this->post('/cek-status',['nomor'=>$p->nomor_pendaftaran,'tgl_lahir'=>$p->tanggal_lahir])
        ->assertSee($p->status->label());
    $this->post('/cek-status',['nomor'=>$p->nomor_pendaftaran,'tgl_lahir'=>'1999-01-01'])
        ->assertSessionHasErrors('nomor');
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — `JadwalService` baca `jadwal_psb` (cache 60 dtk); home render kartu 7 jurusan + jadwal; cek status: cari pendaftar by `nomor_pendaftaran` + `tanggal_lahir`, jika ketidakhasan → error `nomor`.
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: jadwal service, beranda publik, cek status"`

---

### Task 4: Pendaftaran — draf, submit, upload dokumen, nomor pendaftaran

**Files:**
- Create: `app/Services/Psb/NomorPendaftaran.php`, `app/Services/Psb/DokumenService.php`, `app/Services/Psb/PendaftaranService.php`, `app/Http/Controllers/Portal/PendaftaranController.php`, `resources/views/portal/pendaftaran/{index,form}.blade.php`
- Test: `tests/Feature/PendaftaranTest.php`

**Interfaces:**
- Consumes: `JadwalService::isBuka()`, enum Task 1, model Task 1.
- Produces: `NomorPendaftaran::generate(): string`; `PendaftaranService::{simpanDraf(User, array $data): Pendaftar, kirim(Pendaftar): Pendaftar}` (kirim = validasi kelengkapan + `isBuka(PENDAFTARAN)` + set status MENUNGGU_VERIFIKASI + `dikirim_at`); `DokumenService::{simpan(Pendaftar, string $jenis, UploadedFile $f): Dokumen, ganti(Pendaftar, string $jenis, UploadedFile $f): Dokumen}` (validasi `mimes:pdf,jpg,png` + `max:2048` + cek MIME via `f->getMimeType()` harus `application/pdf|image/jpeg|image/png`; simpan `storage/app/private/dokumen/{nomor}/{jenis}_{uniqid}.{ext}`; hapus file lama saat ganti).

- [ ] **Step 1: Tulis test gagal** — `PendaftaranTest.php`:

```php
test('submit lengkap menerbitkan nomor PSB-2026-000001 dan status menunggu_verifikasi', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');
    bukaPendaftaran();
    $u = User::factory()->create();
    $p = app(PendaftaranService::class)->simpanDraf($u, dataLengkap());
    $p = app(PendaftaranService::class)->kirim($p);
    expect($p->nomor_pendaftaran)->toBe('PSB-2026-000001');
    expect($p->status)->toBe(StatusPendaftar::MENUNGGU_VERIFIKASI);
});
test('submit ditolak jika periode pendaftaran ditutup (form dibuka sebelum tutup)', function () {
    bukaPendaftaran(mulai: now()->subDay(), selesai: now()->addMinute());
    $u = User::factory()->create();
    $p = app(PendaftaranService::class)->simpanDraf($u, dataLengkap());
    Carbon::setTestNow(now()->addMinutes(5)); // lewat saat submit
    app(JadwalService::class) /* cache di-clear di service saat isBuka */;
    expect(fn () => app(PendaftaranService::class)->kirim($p))
        ->toThrow(\App\Exceptions\PendaftaranDitutup::class);
});
test('NISN duplikat di level DB menghasilkan pesan ramah, bukan 500', function () {
    bukaPendaftaran();
    Pendaftar::factory()->create(['pengguna_id' => null, 'nik' => '3201xxxx']);
    $u = User::factory()->create();
    // NISN sama dengan pendaftar existing
    $p = app(PendaftaranService::class)->simpanDraf($u, dataLengkap(nisn: Pendaftar::first()->nisn ?? '1111111111'));
    $r = $this->actingAs($u)->post('/pendaftaran/kirim', ['pendaftar_id' => $p->id]);
    $r->assertSessionHasErrors('nisn');
    expect($r->status())->not()->toBe(500);
});
test('file 3MB ditolak; file .pdf berisi binary gambar ditolak; jpg 1MB diterima', function () {
    $p = buatPendaftarDraf();
    $this->actingAs($p->pengguna)
        ->post('/pendaftaran/dokumen', ['jenis' => 'ktp', 'file' => UploadedFile::fake()->image('ktp.jpg', 1024)])
        ->assertSessionHasNoErrors();
    $this->actingAs($p->pengguna)
        ->post('/pendaftaran/dokumen', ['jenis' => 'kk', 'file' => UploadedFile::fake()->create('kk.pdf', 3072, 'application/pdf')])
        ->assertSessionHasErrors('file');
    $fake = new \Illuminate\Http\UploadedFile('x.pdf', 'PK-not-a-real-pdf', 'application/pdf');
    // buat file asli ber-MIME image di tmp lalu upload
    $tmp = tempnam(sys_get_temp_dir(), 'mime').'.pdf';
    file_put_contents($tmp, imagecreatetojpeg(null, $tmp)); // isi binary image
    $this->actingAs($p->pengguna)
        ->post('/pendaftaran/dokumen', ['jenis' => 'ijazah', 'file' => new UploadedFile($tmp, 'ijazah.pdf', null, null, true)])
        ->assertSessionHasErrors('file');
});
test('submit ditolak jika ada dokumen wajib yang belum terunggah', function () {
    $p = buatPendaftarDraf(); // tanpa dokumen
    expect(fn () => app(PendaftaranService::class)->kirim($p))
        ->toThrow(\App\Exceptions\DokumenBelumLengkap::class);
});
```

(Helpers `bukaPendaftaran(mulai?, selesai?)`, `dataLengkap(nisn?)`, `buatPendaftarDraf()` didefinisikan di `tests/TestCase.php` atau Pest concern yang di-import semua test.)

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — `NomorPendaftaran::generate()`: `'PSB-'.Y.'-'.str_pad((Pendaftar::whereYear('created_at',Y)->count()+1),6,'0',STR_PAD_LEFT)` (gunakan transaksi + lock `select ... for update` pada baris `kuota` jurusan pilihan 1 untuk menghindari race); `PendaftaranService` per kontrak di atas; exception `PendaftaranDitutup` & `DokumenBelumLengkap` (subclass `RuntimeException`) di-catch controller → `back()->withErrors`; form 3 langkah satu halaman (tab), validasi server: pilihan 1 wajib, pilihan 2 opsional ≠ pilihan 1, sisa kuota > 0.
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: pendaftaran draf/submit, upload dokumen, nomor pendaftaran"`

---

### Task 5: Status & revisi calon siswa

**Files:**
- Create: `app/Http/Controllers/Portal/StatusController.php`, `resources/views/portal/status/{index,hasil}.blade.php`
- Test: `tests/Feature/StatusRevisiTest.php`

**Interfaces:**
- Produces: route `GET /status` (halaman S-CS-01), `POST /pendaftaran/revisi` (hanya saat status GUGUR + tahap PERBAIKAN buka, atau DRAF + PENDAFTARAN buka).

- [ ] **Step 1: Tulis test gagal** — `StatusRevisiTest.php`:

```php
test('halaman status menampilkan 5 status dokumen dan status verifikasi', function () {
    $p = buatPendaftarTerverifikasi(); // 3 dokumen terunggah
    $this->actingAs($p->pengguna)->get('/status')
        ->assertSee($p->nomor_pendaftaran)->assertSee('Menunggu Verifikasi')->assertSee('KTP');
});
test('pendaftar gugur dapat memperbaiki dan kirim ulang saat tahap perbaikan', function () {
    $p = buatPendaftarGugur(); bukaTahap(TahapJadwal::PERBAIKAN);
    $this->actingAs($p->pengguna)->post('/pendaftaran/revisi', ['pendaftar_id' => $p->id])
        ->assertRedirect('/status');
    expect($p->fresh()->status)->toBe(StatusPendaftar::MENUNGGU_VERIFIKASI);
});
test('revisi diblokir setelah lulus verifikasi', function () {
    $p = buatPendaftarLulus(); 
    $this->actingAs($p->pengguna)->post('/pendaftaran/revisi', ['pendaftar_id' => $p->id])
        ->assertForbidden();
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — status page + guard revisi (status ∈ {DRAF,GUGUR} & tahap sesuai, selain itu `abort(403)`); audit log tiap kirim ulang.
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: dashboard status & revisi pendaftar"`

---

### Task 6: Admin — jurusan & kuota, jadwal, dashboard guard

**Files:**
- Create: `app/Http/Controllers/Admin/{JurusanController,JadwalController,DashboardController}.php`, `resources/views/admin/{jurusan,jadwal,dashboard}.blade.php`
- Modify: `routes/web.php` (grup `/admin` + `role:admin`)
- Test: `tests/Feature/JurusanKuotaTest.php`

**Interfaces:**
- Produces: resource `jurusan` (store/update/destroy → destroy hanya set `aktif=0` jika ada pendaftar), `POST /admin/jadwal`, `GET /admin` (statistik).

- [ ] **Step 1: Tulis test gagal** — `JurusanKuotaTest.php`:

```php
test('calon siswa tidak bisa membuka /admin (403)', function () {
    $u = User::factory()->create(['role' => 'calon_siswa']);
    $this->actingAs($u)->get('/admin')->assertForbidden();
});
test('update jurusan membandingkan cadangan ke ceil(10%) secara manual bila dikosongkan', function () {
    $j = Jurusan::first();
    $this->actingAs(admin())->put("/admin/jurusan/{$j->id}", ['kuota' => 72, 'cadangan' => '', 'rombel' => 2, 'kode' => $j->kode, 'nama' => $j->nama, 'aktif' => 1]);
    expect($j->fresh()->kuota->kuota)->toBe(72);
    expect($j->fresh()->kuota->cadangan)->toBe(8);
});
test('jurusan yang sudah punya pendaftar tidak bisa dihapus, hanya dinonaktifkan', function () {
    $j = Jurusan::first(); Pendaftar::factory()->create(); PendaftarPilihan::create(['pendaftar_id'=>Pendaftar::first()->id,'jurusan_id'=>$j->id,'prioritas'=>1]);
    $this->actingAs(admin())->delete("/admin/jurusan/{$j->id}");
    expect($j->fresh()->aktif)->toBeFalse();
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — resource jurusan + kuota (1:1), jadwal (5 baris, date picker), dashboard 4 kartu + data grafik harian (JSON endpoint `GET /admin/dashboard/chart`).
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: admin jurusan/kuota/jadwal + guard role"`

---

### Task 7: Admin — daftar pendaftar, detail, input manual, preview dokumen

**Files:**
- Create: `app/Http/Controllers/Admin/{PendaftarController,DokumenController}.php`, `resources/views/admin/pendaftar/{index,detail}.blade.php`
- Test: `tests/Feature/PendaftarAdminTest.php`

**Interfaces:**
- Consumes: `DokumenService` (Task 4).
- Produces: `PendaftarController::{index(status?,jurusan?,q?),show,store(manual)}`; `DokumenController@show(Pendaftar, JenisDokumen)` → stream file (admin only).

- [ ] **Step 1: Tulis test gagal** — `PendaftarAdminTest.php`:

```php
test('filter status, jurusan, dan teks bekerja', function () {
    buatDataPendaftar(20); // campuran status
    $this->actingAs(admin())->get('/admin/pendaftar?status=lulus_verifikasi')->assertOk();
    $this->actingAs(admin())->get('/admin/pendaftar?jurusan=1&q=budi')->assertOk();
});
test('input manual menerbitkan nomor, sumber=manual, NISN unik tetap dicek', function () {
    bukaPendaftaran();
    $r = $this->actingAs(admin())->post('/admin/pendaftar', dataManualLengkap());
    $p = Pendaftar::latest('id')->first();
    expect($p->sumber)->toBe('manual');
    expect($p->nomor_pendaftaran)->toMatch('/^PSB-\d{4}-\d{6}$/');
    $this->actingAs(admin())->post('/admin/pendaftar', dataManualLengkap(nisn: $p->nisn))
        ->assertSessionHasErrors('nisn');
});
test('preview dokumen: admin 200, calon siswa 403', function () {
    $p = buatPendaftarTerverifikasi();
    $this->actingAs(admin())->get("/admin/pendaftar/{$p->id}/dokumen/ktp")->assertOk();
    $this->actingAs($p->pengguna)->get("/admin/pendaftar/{$p->id}/dokumen/ktp")->assertForbidden();
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — index data-table (ServerSide: search, filter status/jurusan, paginasi 20); detail 4 tab (Data, Dokumen preview iframe/blob, Nilai, Riwayat audit); form manual = form portal + field sumber; `DokumenController@show` cek file ada → `response()->file($path, ['Content-Type' => $mime])`.
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: admin pendaftar, input manual, preview dokumen"`

---

### Task 8: Admin — verifikasi dokumen

**Files:**
- Create: `app/Http/Controllers/Admin/VerifikasiController.php`, `resources/views/admin/verifikasi/index.blade.php`
- Test: `tests/Feature/VerifikasiTest.php`

**Interfaces:**
- Produces: `POST /admin/verifikasi/{pendaftar}/proses` body `{aksi: lulus|gugur, alasan?}`.

- [ ] **Step 1: Tulis test gagal** — `VerifikasiTest.php`:

```php
test('verifikasi lulus mengubah status dan mencatat audit', function () {
    $p = buatPendaftarTerverifikasi();
    $this->actingAs(admin())->post("/admin/verifikasi/{$p->id}/proses", ['aksi' => 'lulus']);
    expect($p->fresh()->status)->toBe(StatusPendaftar::LULUS_VERIFIKASI);
    expect(AuditLog::where('target', "pendaftar:{$p->id}")->where('aksi','verifikasi_lulus')->count())->toBe(1);
});
test('gugur wajib alasan; calon siswa melihat alasannya', function () {
    $p = buatPendaftarTerverifikasi();
    $this->actingAs(admin())->post("/admin/verifikasi/{$p->id}/proses", ['aksi' => 'gugur'])->assertSessionHasErrors('alasan');
    $this->actingAs(admin())->post("/admin/verifikasi/{$p->id}/proses", ['aksi' => 'gugur', 'alasan' => 'NIK tidak cocok']);
    $this->actingAs($p->pengguna)->get('/status')->assertSee('NIK tidak cocok');
});
test('verifikasi hanya bisa saat tahap verifikasi buka', function () {
    tutupTahap(TahapJadwal::VERIFIKASI);
    $p = buatPendaftarTerverifikasi();
    $this->actingAs(admin())->post("/admin/verifikasi/{$p->id}/proses", ['aksi' => 'lulus'])->assertForbidden();
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — guard tahap; update status + `alasan_gugur`; `AuditService::catat($user, $target, $aksi, $detail)` (helper Task ini, dipakai task 9–12).
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: verifikasi dokumen + audit log"`

---

### Task 9: Nilai seleksi — service, perhitungan total, riwayat, input admin

**Files:**
- Create: `app/Services/Psb/NilaiService.php`, `app/Http/Controllers/Admin/NilaiController.php`, `resources/views/admin/nilai/index.blade.php`
- Test: `tests/Feature/NilaiTest.php`

**Interfaces:**
- Produces: `NilaiService::{hitungTotal(float $rapor, float $prestasi, float $kunjungan): float, simpan(NilaiSeleksi $n, array $nilai, ?User $by): void}` (validasi 0–100; snapshot lama ke `nilai_seleksi_riwayat` sebelum update; total dari `config('psb.bobot')`); route `PUT /admin/nilai/{pendaftar}`.

- [ ] **Step 1: Tulis test gagal** — `NilaiTest.php`:

```php
test('total = 0.4r + 0.3p + 0.3k', function () {
    expect(app(NilaiService::class)->hitungTotal(80, 90, 100))->toBe(89.0);
});
test('input nilai menyimpan dan membuat riwayat saat diubah', function () {
    $p = buatPendaftarLulus(); bukaTahap(TahapJadwal::SELEKSI);
    $this->actingAs(admin())->put("/admin/nilai/{$p->id}", ['rapor' => 80, 'prestasi' => 90, 'kunjungan' => 100]);
    $this->actingAs(admin())->put("/admin/nilai/{$p->id}", ['rapor' => 85, 'prestasi' => 90, 'kunjungan' => 100]);
    $n = $p->fresh()->nilai;
    expect($n->total)->toBe(92.5);
    expect(NilaiSeleksiRiwayat::where('nilai_seleksi_id', $n->id)->count())->toBe(1);
});
test('nilai 150 ditolak; pendaftar belum lulus verifikasi tidak bisa diinput', function () {
    $p = buatPendaftarTerverifikasi();
    $this->actingAs(admin())->put("/admin/nilai/{$p->id}", ['rapor' => 150, 'prestasi' => 0, 'kunjungan' => 0])->assertSessionHasErrors('rapor');
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — guard status LULUS_VERIFIKASI + tahap SELEKSI; `simpan()` wrap transaksi + riwayat.
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: input nilai seleksi + riwayat"`

---

### Task 10: Ranking & pengumuman — inti sistem

**Files:**
- Create: `app/Services/Psb/RankingService.php`, `app/Services/Psb/PengumumanService.php`, `app/Http/Controllers/Admin/{RankingController,PengumumanController}.php`, `resources/views/admin/{ranking,pengumuman}.blade.php`
- Test: `tests/Feature/RankingTest.php`

**Interfaces:**
- Consumes: `NilaiService`, `JadwalService`, `AuditService`.
- Produces: `RankingService::proses(User $by): array` — return `['diterima'=>n,'cadangan'=>n,'tidak_diterima'=>n,'per_jurusan'=>[...]]`; melempar `App\Exceptions\AdaPendaftarTanpaNilai` (bawa daftar id) bila ada LULUS_VERIFIKASI tanpa nilai. `PengumumanService::terbitkan(User $by): void` — set flag `JadwalPsb` tahap PENGUMUMAN `dimulai` + baris `audit_log` aksi `publish`; setelah publish, `NilaiService::simpan` & `RankingService::proses` melempar `App\Exceptions\PengumumanSudahTerbit`.

- [ ] **Step 1: Tulis test gagal** — `RankingTest.php`:

```php
test('ranking mengisi kuota per jurusan prioritas 1', function () {
    // 2 jurusan kuota 2 (cadangan 1). A: P1=J1 (95), P2=J1 (90), P3=J2 (88)
    // J1: A diterima(1), P2 diterima(2); J2: P3 diterima(1)
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1'], 'P2' => ['nilai' => 90, 'p1' => 'J1'], 'P3' => ['nilai' => 88, 'p1' => 'J2']], kuota: ['J1' => 2, 'J2' => 2], cadangan: ['J1' => 1, 'J2' => 1]);
    $r = app(RankingService::class)->proses(admin());
    expect($r['diterima'])->toBe(3);
});
test('overflow prioritas 1 jatuh ke pilihan 2 hanya pada sisa kuota', function () {
    // J1 kuota 1: A(95) diterima. B(90) P1=J1, P2=J2; J2 kuota 1 kosong.
    // B harus diterima via J2 (via_pilihan=2)
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1'], 'B' => ['nilai' => 90, 'p1' => 'J1', 'p2' => 'J2']], kuota: ['J1' => 1, 'J2' => 1]);
    app(RankingService::class)->proses(admin());
    $b = Pendaftar::where('nama_lengkap', 'B')->first();
    expect($b->hasil->status_akhir)->toBe('diterima');
    expect($b->hasil->via_pilihan)->toBe(2);
    expect($b->hasil->jurusan_id)->toBe(Jurusan::where('kode', 'J2')->value('id'));
});
test('posisi setelah kuota = cadangan s.d. batas; sisanya tidak diterima', function () {
    // J1 kuota 1 cadangan 1: A(95) diterima, B(90) cadangan, C(80) tidak diterima
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1'], 'B' => ['nilai' => 90, 'p1' => 'J1'], 'C' => ['nilai' => 80, 'p1' => 'J1']], kuota: ['J1' => 1], cadangan: ['J1' => 1]);
    app(RankingService::class)->proses(admin());
    expect(['A','B','C'])->map(fn($n) => Pendaftar::where('nama_lengkap',$n)->first()->hasil->status_akhir)
        ->toBe(['diterima','cadangan','tidak_diterima']);
});
test('tie-break: total sama → rapor desc → dikirim_at asc', function () {
    // total 90: X(rapor 85, daftar lebih awal), Y(rapor 90, daftar lebih lambat) → Y rank 1
    buatSkenarioTieBreak();
    app(RankingService::class)->proses(admin());
    expect(Pendaftar::where('nama_lengkap','Y')->first()->hasil->perankingan)->toBe(1);
});
test('proses diblokir jika ada pendaftar lulus verifikasi tanpa nilai', function () {
    buatPendaftarLulus(tanpaNilai: true);
    expect(fn () => app(RankingService::class)->proses(admin()))
        ->toThrow(AdaPendaftarTanpaNilai::class);
});
test('proses ulang menghapus hasil lama; publish mengunci nilai & ranking + audit', function () {
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1]);
    app(RankingService::class)->proses(admin());
    app(PengumumanService::class)->terbitkan(admin());
    $a = Pendaftar::first();
    expect(fn () => app(NilaiService::class)->simpan($a->nilai, ['rapor' => 100, 'prestasi' => 0, 'kunjungan' => 0], admin()))
        ->toThrow(PengumumanSudahTerbit::class);
    expect(fn () => app(RankingService::class)->proses(admin()))
        ->toThrow(PengumumanSudahTerbit::class);
    expect(AuditLog::where('aksi', 'publish')->count())->toBe(1);
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — `RankingService::proses()`: guard publish; transaksi; kumpulkan antrian P1 per jurusan (urut tie-break spec), isi DITERIMA s.d. kuota, CADANGAN s.d. kuota+cadangan, sisanya ke antrian P2; proses antrian P2 per jurusan yang kuotanya belum penuh (sisa = kuota − terisi P1), urutan P2 mengikuti ranking antrian global (bukan urutan input); tulis `hasil` (hapus lama), update `kuota.terisi`, update `pendaftar.status`; `PengumumanService::terbitkan()` guard idempoten + audit.
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: proses ranking, penetapan status, penerbitan pengumuman"`

---

### Task 11: Hasil & surat diterima (portal + publik)

**Files:**
- Create: `app/Http/Controllers/Portal/{HasilController,SuratController}.php`, `resources/views/portal/{hasil,surat}.blade.php`
- Modify: `resources/views/public/cek-status.blade.php` (tampilkan status akhir)
- Test: `tests/Feature/PengumumanSuratTest.php`

**Interfaces:**
- Produces: `GET /status` (S-CS-02 saat publish), `GET /surat/{nomor_pendaftaran}` (view print, data dari `config('psb.*')`, nomor induk sementara `PSB-YYYY-XXXXXX`).

- [ ] **Step 1: Tulis test gagal** — `PengumumanSuratTest.php`:

```php
test('yang diterima melihat hasil + surat; cadangan & tidak diterima melihat statusnya', function () {
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1'], 'B' => ['nilai' => 80, 'p1' => 'J1']], kuota: ['J1' => 1]);
    app(RankingService::class)->proses(admin());
    app(PengumumanService::class)->terbitkan(admin());
    $a = Pendaftar::where('nama_lengkap','A')->first();
    $this->actingAs($a->pengguna)->get('/status')->assertSee('Diterima')->assertSee('Cetak');
    $this->actingAs($a->pengguna)->get("/surat/{$a->nomor_pendaftaran}")
        ->assertSee('SMK Negeri 2 Singosari')->assertSee($a->nomor_pendaftaran);
    $b = Pendaftar::where('nama_lengkap','B')->first();
    $this->actingAs($b->pengguna)->get('/status')->assertSee('Tidak Diterima');
});
test('cek status publik menampilkan status akhir setelah publish', function () {
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1]);
    app(RankingService::class)->proses(admin());
    app(PengumumanService::class)->terbitkan(admin());
    $a = Pendaftar::first();
    $this->post('/cek-status', ['nomor' => $a->nomor_pendaftaran, 'tgl_lahir' => $a->tanggal_lahir])
        ->assertSee('Diterima');
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL.
- [ ] **Step 3: Implementasi** — halaman hasil per status; surat: layout A4 print-only (`@media print`), kop sekolah dari config, tanda tangan kosong (Kepala Sekolah + NIP).
- [ ] **Step 4: Jalankan, pastikan lulus** → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: halaman hasil & surat keterangan diterima"`

---

### Task 12: Dashboard statistik, laporan, ekspor, audit log UI

**Files:**
- Create: `app/Http/Controllers/Admin/{LaporanController,AuditController}.php`, `app/Exports/PendaftarExport.php`, `resources/views/admin/{laporan,audit}.blade.php`
- Modify: `app/Http/Controllers/Admin/DashboardController.php` (kartu final), `composer.json` (tambah `maatwebsite/excel ^3.1`)
- Test: `tests/Feature/LaporanTest.php`

**Interfaces:**
- Consumes: semua model + `PendaftarExport implements FromQuery` (kolom: no. pendaftaran, nama, NISN, P1, P2, total, status akhir, jurusan diterima).

- [ ] **Step 1: Tulis test gagal** — `LaporanTest.php`:

```php
test('dashboard menampilkan 4 statistik sesuai data', function () {
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1'], 'B' => ['nilai' => 80, 'p1' => 'J1']], kuota: ['J1' => 1]);
    app(RankingService::class)->proses(admin());
    $this->actingAs(admin())->get('/admin')
        ->assertSee('Total Pendaftar')->assertSee('1 Diterima');
});
test('ekspor CSV dan XLSX berisi baris pendaftar terpilih', function () {
    buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1'], 'B' => ['nilai' => 80, 'p1' => 'J1']], kuota: ['J1' => 1]);
    app(RankingService::class)->proses(admin());
    $r1 = $this->actingAs(admin())->get('/admin/laporan/export/csv?status=diterima');
    $r1->assertOk()->assertDownload('pendaftar-diterima.csv');
    $r2 = $this->actingAs(admin())->get('/admin/laporan/export/xlsx?status=diterima');
    $r2->assertOk()->assertDownload('pendaftar-diterima.xlsx');
});
test('halaman audit log menampilkan aksi publish terakhir', function () {
    app(PengumumanService::class)->terbitkan(admin());
    $this->actingAs(admin())->get('/admin/audit')->assertSee('publish');
});
```

- [ ] **Step 2: Jalankan, pastikan gagal** → FAIL (setelah `composer require maatwebsite/excel`).
- [ ] **Step 3: Implementasi** — laporan: rekap per jurusan (pendaftar/terima/cadangan/sisa), filter status, ekspor via `Excel::download(new PendaftarExport($filter))`; audit table paginasi.
- [ ] **Step 4: Jalankan suite penuh** — `php artisan test` → SEMUA PASS; lalu `./vendor/bin/pint --test` (style) → PASS.
- [ ] **Step 5: Commit** — `git commit -m "feat: laporan, ekspor CSV/Excel, audit log UI"`

---

## Catatan Eksekusi

- Jalankan `php artisan storage:link` tidak diperlukan (file tidak via public disk).
- Setiap task yang gagal di step test → jangan lanjut implementasi sebelum test benar-benar merah.
- Data dummy test memakai factory; jangan commit data produksi.
- Setelah Task 12: tawarkan review keseluruhan (requesting-code-review) sebelum dianggap selesai.
