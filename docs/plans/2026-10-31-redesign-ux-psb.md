# Implementation Plan: Redesign UX PSB (Overhaul "Enak Dipakai")

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Potong friksi portal siswa & admin (hapus verifikasi email, form 1 halaman, upload tanpa reload, verifikasi massal, input nilai batch, dashboard pipeline) tanpa menambah fitur dan tanpa mengubah algoritma ranking.

**Architecture:** Perbaikan bertahap atas kode existing (Laravel 10, Blade, Bootstrap 5 + SB Admin 2). Setiap task berdiri sendiri: test di-amend/ditambah → hijau → commit. Alur ranking/kuota/publish tidak disentuh.

**Tech Stack:** Laravel 10 (PHP 8.5), Blade, Bootstrap 5, SB Admin 2 (aset existing), vanilla JS + jQuery, PHPUnit 10, MySQL 8 / SQLite in-memory.

**Spec:** `docs/2026-10-31-redesign-ux-psb-design.md` (mengamendemen `docs/user-requirements.md` & `docs/system-design.md`).

## Global Constraints

- Tanpa fitur baru; algoritma ranking/kuota/cadangan & aturan "status diterapkan saat publish" TIDAK berubah (system-design §5.2).
- **Tanpa migration baru.** `users.email_verified_at` dibiarkan (tidak dipakai lagi, beri komentar di seeder).
- Suite test harus 100% hijau di akhir tiap task; target akhir ± 60 test; `php artisan test` + `./vendor/bin/pint` bersih.
- UI Bahasa Indonesia; warna primer `#103cbe`; logo `asset('Assets/Img-Login/Logo-SMk.png')`.
- Tanpa dependensi JS framework baru (vanilla JS + jQuery SB Admin 2 yang sudah ada).
- Deteksi upload XHR: header `X-Requested-With: XMLHttpRequest` → respons JSON `{ok: bool, jenis?: string, error?: string}`; tanpa header → perilaku lama (redirect + session).
- Helper badge status: komponen Blade `x-status-badge :status="StatusPendaftar"` dengan pemetaan warna: draf=secondary, menunggu_verifikasi=warning, lulus_verifikasi=info, gugur=danger, diterima=success, cadangan=warning (outline), tidak_diterima=secondary.
- Commit per task mengikuti pesan commit di tiap task.

## Review Focus

1. **Siswa baru yang belum pernah menyimpan draf membuka `/pendaftaran`** — form harus render kosong tanpa error checklist/JS. → Task 2 (test `form_render_untuk_user_baru`).
2. **Batch verifikasi dengan `ids` kosong atau berisi pendaftar yang sudah tidak MENUNGGU_VERIFIKASI** — tidak boleh 500, tidak memproses apa pun yang bukan MENUNGGU. → Task 4 (test `batch_ids_kosong_atau_sudah_diproses_tidak_memproses_apa_apa`).
3. **Batch nilai dengan nilai di atas 100 atau `id` tak dikenal/duplikat** — tolak per-baris via JSON, jangan simpan sebagian diam-diam; baris tak-berubah tidak membuat riwayat/audit. → Task 5 (test `batch_nilai_invalid_ditolak_per_baris`).
4. **Siswa belum login membuka `/status`** — redirect ke login (middleware auth), bukan 500/blank. → Task 1 (test `status_403_guest`).
5. **Upload XHR file `.pdf` yang isinya binary JPEG** — JSON `{ok:false, error}` (bukan 500, bukan redirect). → Task 2 (test `upload_xhr_mime_spoof_ditolak_json`).

## File Structure

| Task | Create | Modify |
|------|--------|--------|
| 0 | — | `docs/user-requirements.md`, `docs/system-design.md` |
| 1 | `tests/Feature/RedesignTest.php` (bagian auth) | `app/Models/User.php`, `app/Http/Controllers/Portal/AuthController.php`, `routes/web.php`, `app/Http/Middleware/RedirectIfAuthenticated.php`, hapus `resources/views/auth/verify.blade.php`, `tests/Feature/AuthTest.php`, `tests/Feature/ReviewFixesTest.php` (hapus test C1), `database/seeders/DatabaseSeeder.php` (komentar) |
| 2 | `tests/Feature/RedesignTest.php` (bagian form/upload) | `app/Http/Controllers/Portal/PendaftaranController.php`, `resources/views/portal/pendaftaran/index.blade.php`, `resources/views/components/status-badge.blade.php`, `tests/Feature/PendaftaranTest.php` |
| 3 | — | `resources/views/login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/portal/status/index.blade.php`, `resources/views/layouts/portal.blade.php` (font Nunito) |
| 4 | — | `app/Http/Controllers/Admin/VerifikasiController.php`, `resources/views/admin/verifikasi/index.blade.php`, `routes/web.php`, `tests/Feature/VerifikasiTest.php` |
| 5 | — | `app/Http/Controllers/Admin/NilaiController.php`, `resources/views/admin/nilai/index.blade.php`, `routes/web.php`, `tests/Feature/NilaiTest.php` |
| 6 | — | `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/layouts/admin.blade.php` (sidebar grouping), `tests/Feature/LaporanTest.php` |

`tests/Feature/RedesignTest.php` dibuat di Task 1 dan ditambah per-task (Task 2) agar semua test redesign terpusat; test lama tetap di file masing-masing dan di-amend.

---

### Task 0: Selaraskan dokumen TA

**Files:**
- Modify: `docs/user-requirements.md`, `docs/system-design.md`

- [ ] **Step 1: Amend `docs/user-requirements.md` sesuai design §5**

Perubahan (jaga gaya & nomor dokumen, sisipkan catat "redesign 2026-10-31" di tiap bagian yang diubah):
- UC-01: hapus langkah verifikasi email (alur 3–4 & alt 3a/3b) → "Akun aktif langsung setelah pendaftaran; sistem otomatis login dan membuka form pendaftaran."
- UC-03: "3 langkah/tab" → "1 halaman 4 seksi (Data Diri, Orang Tua/Wali, Pilihan Jurusan, Dokumen) dengan checklist kemajuan".
- UC-04: "upload memicu reload" → "upload per slot via fetch, tanpa reload; status tersimpan tampil per slot".
- UC-09: tambah "verifikasi massal (pilih banyak, satu alasan untuk gugur massal); audit tetap per pendaftar".
- Bagian nilai (UC-10): "input per pendaftar" → "input ala spreadsheet; simpan semua dalam satu POST batch; riwayat & audit per baris yang berubah".
- FR-02: hapus "verifikasi email (tautan, expiry 7 hari)".
- Alur bisnis: langkah 2 menjadi "Registrasi akun → otomatis login → isi pendaftaran".

- [ ] **Step 2: Amend `docs/system-design.md`**

- §6 (Desain Layar): ganti wireframe S-PUB-02 (form 1 halaman + checklist + slot upload), S-CS-01 (status sebagai beranda), S-AD-01 (dashboard pipeline 4 tahap), S-AD-03 (verifikasi: checkbox + bilah batch + alasan inline), S-AD-04 (nilai: tabel spreadsheet + Simpan Semua); hapus referensi verifikasi email di §7 (Keamanan) dan §9 (rute `/verify-email*`).
- §9 (Peta Route): hapus baris `/verify-email*`, tambah `POST /admin/verifikasi/batch` dan `POST /admin/nilai/batch`; coret `PUT /admin/nilai/{pendaftar}`.

- [ ] **Step 3: Commit**

```bash
git add docs && git commit -m "docs: amende user-requirements & system-design sesuai redesign UX"
```

---

### Task 1: Hapus verifikasi email + auto-login register

**Files:**
- Create: `tests/Feature/RedesignTest.php`
- Modify: `app/Models/User.php`, `app/Http/Controllers/Portal/AuthController.php`, `routes/web.php`, `app/Http/Middleware/RedirectIfAuthenticated.php`, `database/seeders/DatabaseSeeder.php`, `tests/Feature/AuthTest.php`, `tests/Feature/ReviewFixesTest.php`
- Delete: `resources/views/auth/verify.blade.php`

**Interfaces:**
- Produces: `AuthController::{showLogin, login, showRegister, register, logout}` (tanpa `verifyEmail/notice/resend`); route `POST /register` → auto-login + `redirect('/pendaftaran')`; semua grup route TANPA middleware `verified`; `RedirectIfAuthenticated`: admin → `/admin`, selain itu → `/status`.
- Test helper (di `RedesignTest`): pattern setUp standar (`RefreshDatabase` + `$this->seed()`).

- [ ] **Step 1: Tulis test gagal di `tests/Feature/RedesignTest.php`**

```php
public function test_register_aktiflangsung_otomatis_login_ke_form(): void
{
    $r = $this->post('/register', [
        'name' => 'Budi', 'email' => 'budi@mail.com', 'nisn' => '1234567890',
        'password' => 'rahasia1', 'password_confirmation' => 'rahasia1',
    ]);
    $r->assertRedirect('/pendaftaran');
    $this->assertAuthenticated();
    $this->assertSame('calon_siswa', User::where('email', 'budi@mail.com')->first()->role);
}

public function test_user_belum_verifikasi_tetap_bisa_buka_portal(): void
{
    $u = User::factory()->unverified()->create();
    $this->actingAs($u)->get('/status')->assertOk();
    $this->actingAs($u)->get('/pendaftaran')->assertOk();
}

public function test_status_guest_diarahkan_login(): void
{
    $this->get('/status')->assertRedirect('/login');
}

public function test_route_verifikasi_email_dihapus(): void
{
    $u = User::factory()->unverified()->create();
    $this->actingAs($u)->get('/verify-email')->assertNotFound();
    $this->actingAs($u)->get('/verify-email/abc')->assertNotFound();
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test tests/Feature/RedesignTest.php`
Expected: FAIL (register masih redirect `/login`, `/verify-email` masih ada, `/status` user unverified redirect notice).

- [ ] **Step 3: Implementasi**

- `User`: hapus `implements MustVerifyEmail` + import-nya.
- `AuthController::register`: setelah `User::create` → `Auth::login($user)` → `return redirect('/pendaftaran')->with('status', 'Selamat datang, '.$user->name.'. Lengkapi formulir pendaftaran Anda.');` Hapus method `verifyEmail`, `notice`, `resend` + import `VerifyEmail` bila ada.
- `routes/web.php`: hapus route `verify-email/{token}`, `verify-email` (notice), `verify-email/resend`; ganti `verified:verify-email.notice` → hapus dari semua grup (admin & portal).
- Hapus `resources/views/auth/verify.blade.php`.
- `RedirectIfAuthenticated`: sudah role-based (admin→/admin, selain itu→/status) — pastikan tetap benar.
- `DatabaseSeeder`: komentar di baris admin `// email_verified_at tidak dipakai lagi (redesign 2026-10-31)`.
- Amend `tests/Feature/AuthTest.php`: hapus test `test_token_verifikasi_...`; test register ubah assertRedirect → `/pendaftaran` + `assertAuthenticated`; hapus `Notification::fake`/`assertSentTo` terkait VerifyEmail.
- Amend `tests/Feature/ReviewFixesTest.php`: hapus kedua test C1 (`user_belum_verifikasi_...` dan `resend_verifikasi_...`).

- [ ] **Step 4: Jalankan suite penuh, pastikan hijau**

Run: `php artisan test`
Expected: SEMUA PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(redesign): hapus verifikasi email, register auto-login ke form"
```

---

### Task 2: Form 1 halaman + checklist + upload XHR JSON + status badge

**Files:**
- Create: `resources/views/components/status-badge.blade.php`
- Modify: `app/Http/Controllers/Portal/PendaftaranController.php`, `resources/views/portal/pendaftaran/index.blade.php`, `tests/Feature/PendaftaranTest.php`, `tests/Feature/RedesignTest.php` (tambah test di bawah)

**Interfaces:**
- Consumes: Task 1 (register auto-login; route `/pendaftaran` tanpa `verified`).
- Produces: `POST /pendaftaran/dokumen` → bila header `X-Requested-With: XMLHttpRequest`: `200 {ok:true, jenis}` atau `422 {ok:false, error}`; selain itu redirect+session (perilaku lama). Komponen `<x-status-badge :status="$p->status"/>`.
- View form: 4 seksi (id `seksi-data`, `seksi-ortu`, `seksi-jurusan`, `seksi-dokumen`) + `#checklist` (4 item dengan class `done` saat valid) + per slot `input[name=file][data-jenis={jenis}]` + `#slot-{jenis}` badge + `#err-{jenis}`.

- [ ] **Step 1: Tulis test gagal (tambah di `RedesignTest`)**

```php
public function test_form_render_untuk_user_baru(): void
{
    $u = User::factory()->create();
    $this->actingAs($u)->get('/pendaftaran')->assertOk()->assertSee('Pilihan Jurusan');
}

public function test_upload_xhr_berhasil_json(): void
{
    $this->bukaPendaftaran();
    $p = $this->buatPendaftarDraf();
    $r = $this->actingAs($p->pengguna)->call('POST', '/pendaftaran/dokumen',
        ['jenis' => 'ktp'], ['X-Requested-With' => 'XMLHttpRequest'],
        ['file' => \Illuminate\Http\UploadedFile::fake()->image('ktp.jpg')]);
    $r->assertOk();
    $this->assertTrue(collect($r->getJSON())->only('ok', 'jenis')->all() === ['ok' => true, 'jenis' => 'ktp']);
}

public function test_upload_xhr_3mb_ditolak_json(): void
{
    $this->bukaPendaftaran();
    $p = $this->buatPendaftarDraf();
    $r = $this->actingAs($p->pengguna)->call('POST', '/pendaftaran/dokumen',
        ['jenis' => 'kk'], ['X-Requested-With' => 'XMLHttpRequest'],
        ['file' => \Illuminate\Http\UploadedFile::fake()->create('kk.pdf', 3072, 'application/pdf')]);
    $r->assertStatus(422);
    $this->assertFalse($r->getJSON('ok'));
}

public function test_upload_xhr_mime_spoof_ditolak_json(): void
{
    $this->bukaPendaftaran();
    $p = $this->buatPendaftarDraf();
    $tmp = tempnam(sys_get_temp_dir(), 'psb').'.pdf';
    imagejpeg(\imagecreate(10, 10), $tmp);
    try {
        $r = $this->actingAs($p->pengguna)->call('POST', '/pendaftaran/dokumen',
            ['jenis' => 'ijazah'], ['X-Requested-With' => 'XMLHttpRequest'],
            ['file' => new \Illuminate\Http\UploadedFile($tmp, 'ijazah.pdf', null, null, true)]);
        $r->assertStatus(422);
        $this->assertFalse($r->getJSON('ok'));
    } finally {
        @unlink($tmp);
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test tests/Feature/RedesignTest.php`
Expected: test upload XHR FAIL (endpoint masih redirect); test form mungkin sudah pass (view lama punya seksi "Pilihan Jurusan"? — bila fail karena teks seksi, ikuti naming seksi di Step 3).

- [ ] **Step 3: Implementasi**

- `PendaftaranController::dokumen`: setelah validasi & try/catch `DokumenService`, bila `$request->header('X-Requested-With') === 'XMLHttpRequest'` → `response()->json(['ok' => true, 'jenis' => $jenis])` / `response()->json(['ok' => false, 'error' => $e->getMessage()], 422)` (termasuk error validasi: hook `failedValidation` → jawab JSON 422 `{ok:false, error: pertama}` untuk request XHR — implementasi: override `failedValidation` di controller atau cek `$request->ajax()` di validator closure; pilih cara paling ringkas).
- `resources/views/components/status-badge.blade.php`: `<span class="badge bg-{warna}">{{ $status->label() }}</span>` dengan `@props(['status'])` + match warna per Global Constraints.
- Tulis ulang `resources/views/portal/pendaftaran/index.blade.php` (1 halaman):
  - Layout: 2 kolom desktop (`col-lg-9` form, `col-lg-3` checklist `sticky`); mobile: checklist di atas (list ringkas 4 item).
  - Seksi Data Diri: field lama (nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, asal_sekolah, alamat, nik, nisn read-only).
  - Seksi Orang Tua/Wali: nama, pekerjaan, telepon.
  - Seksi Pilihan Jurusan: 2 select (p1 wajib, p2 opsional) dengan sisa kuota; info "Pilihan 2 berbeda dari Pilihan 1".
  - Seksi Dokumen: 5 kartu slot (label `JenisDokumen::label()`, `input file` `accept=".pdf,.jpg,.jpeg,.png"`, badge status `#slot-{jenis}`, area error `#err-{jenis}`, teks "PDF/JPG/PNG, maks. 2 MB").
  - `#checklist`: 4 item (Data Diri, Orang Tua, Pilihan Jurusan, Dokumen 0/5) — class `done` ditoggle JS.
  - Tombol: `Simpan Draf` (POST form biasa) + `Kirim Pendaftaran` (POST `/pendaftaran/kirim` via confirm yang menampilkan ringkasan checklist; bila dokumen <5/5 tetap boleh, server menolak dengan pesan daftar kurang).
  - JS (vanilla): tiap `input file` change → `fetch` form-data (jenis+file, header XHR) → sukses: badge slot ✓ + hitung checklist; gagal: isi `#err-{jenis}` (tampilkan lagi saat user memilih file berikutnya).
- Ganti badge status di `portal/status/index.blade.php` dan `admin/pendaftar/*` dengan `<x-status-badge>` (opsional di task ini; wajib di `portal/status`).

- [ ] **Step 4: Jalankan suite penuh, pastikan hijau**

Run: `php artisan test`
Expected: SEMUA PASS (termasuk test lama `PendaftaranTest` yang non-XHR tetap redirect+session).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(redesign): form 1 halaman + checklist, upload XHR per slot tanpa reload, status badge"
```

---

### Task 3: Login/register satu kartu + status sebagai beranda + Nunito

**Files:**
- Modify: `resources/views/login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/portal/status/index.blade.php`, `resources/views/layouts/portal.blade.php`

**Interfaces:**
- Consumes: Task 1–2.
- Produces: halaman login & register berupa 1 kartu terpusat (logo, judul, form, tautan lintas); `/status` dengan kartu utama (nomor + `<x-status-badge>` besar + alasan gugur), kartu kelengkapan dokumen, kartu hasil (tetap di-gate `sudahTerbit`).

- [ ] **Step 1: Tulis test (tambah di `RedesignTest`)**

```php
public function test_login_register_satu_kartu_render(): void
{
    $this->get('/login')->assertOk()->assertSee('Login')->assertSee('Daftar');
    $this->get('/register')->assertOk()->assertSee('NISN');
}

public function test_status_baru_menampilkan_kartu_utama(): void
{
    $p = $this->buatPendaftarTerverifikasi();
    $this->actingAs($p->pengguna)->get('/status')
        ->assertOk()
        ->assertSee($p->nomor_pendaftaran)
        ->assertSee('Menunggu Verifikasi');
}
```

- [ ] **Step 2: Jalankan, pastikan gagal** (test kartu mungkin sebagian pass — yang penting test `status_baru` memaksa struktur kartu utama bila belum ada; bila sudah pass semua, catat di ledger bahwa test ini regresi-murni dan lanjut).

Run: `php artisan test tests/Feature/RedesignTest.php`
Expected: minimal test `status_baru` atau `login_register` gagal sebelum redesign view.

- [ ] **Step 3: Implementasi**

- `login.blade.php`: 1 kartu `max-width: 420px` terpusat (bg `#f4f6fb`): logo 80px, h1 "Login", sub "PSB {{ config('psb.nama_sekolah') }}", alert status/error, form email+password, tautan "Belum punya akun? Daftar". Hapus layout 2-box & link `style.css` yang mati.
- `auth/register.blade.php`: gaya kartu sama; field nama/email/NISN/password/confirm; tautan "Sudah punya akun? Login".
- `layouts/portal.blade.php` + `layouts/public.blade.php`: tambah `<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">` + `body { font-family: 'Nunito', system-ui, sans-serif; }`.
- `layouts/portal.blade.php` (navbar siswa): ganti tautan menjadi **Status** (`/status`), **Pendaftaran** (`/pendaftaran`), tombol Logout — logo tetap taut ke beranda publik (design §4.1 baris 6).
- `portal/status/index.blade.php`: kartu utama (nomor pendaftaran besar, `<x-status-badge :status="$p->status"/>`, alasan gugur bila ada), kartu kelengkapan (5 baris + badge ✓/belum), kartu hasil (logika gate `sudahTerbit` TIDAK diubah), tombol "Kirim Ulang" bila `bolehRevisi` dan status gugur.

- [ ] **Step 4: Jalankan suite penuh, pastikan hijau**

Run: `php artisan test`
Expected: SEMUA PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(redesign): login/register satu kartu, status sebagai beranda siswa, font Nunito"
```

---

### Task 4: Verifikasi massal

**Files:**
- Modify: `app/Http/Controllers/Admin/VerifikasiController.php`, `resources/views/admin/verifikasi/index.blade.php`, `routes/web.php`, `tests/Feature/VerifikasiTest.php`

**Interfaces:**
- Consumes: `AuditService::catat(?User, string $target, string $aksi, array $detail)`; guard tahap verifikasi (abort 403).
- Produces: `POST /admin/verifikasi/batch` body `{aksi: 'lulus'|'gugur', alasan?: string, ids: int[]}` → `RedirectResponse` (flash). Per pendaftar id: hanya diproses bila status masih `MENUNGGU_VERIFIKASI`; audit `verifikasi_lulus`/`verifikasi_gugur` dengan `detail.massal = true`; alasan wajib untuk `gugur` (validasi). Route lama `POST /admin/verifikasi/{pendaftar}/proses` tetap ada (individual).
- Refactor: `VerifikasiController::proses` mengekstrak `private function prosesSatu(Pendaftar $p, string $aksi, ?string $alasan, ?User $by, bool $massal): void`.

- [ ] **Step 1: Tulis test gagal (tambah di `VerifikasiTest`)**

```php
public function test_batch_luluskan_banyak(): void
{
    $p1 = $this->buatPendaftarTerverifikasi();
    $p2 = $this->buatPendaftarTerverifikasi();

    $this->actingAs($this->admin())
        ->post('/admin/verifikasi/batch', ['aksi' => 'lulus', 'ids' => [$p1->id, $p2->id]]);

    $this->assertSame(\App\Enums\StatusPendaftar::LULUS_VERIFIKASI, $p1->fresh()->status);
    $this->assertSame(\App\Enums\StatusPendaftar::LULUS_VERIFIKASI, $p2->fresh()->status);
    $this->assertSame(2, \App\Models\AuditLog::where('aksi', 'verifikasi_lulus')
        ->where('detail->massal', true)->count());
}

public function test_batch_gugur_satu_alasan_untuk_semua(): void
{
    $ps = [$this->buatPendaftarTerverifikasi(), $this->buatPendaftarTerverifikasi()];

    $this->actingAs($this->admin())
        ->post('/admin/verifikasi/batch', ['aksi' => 'gugur', 'alasan' => 'Dokumen kabur', 'ids' => array_column($ps, 'id')]);

    foreach ($ps as $p) {
        $f = $p->fresh();
        $this->assertSame(\App\Enums\StatusPendaftar::GUGUR, $f->status);
        $this->assertSame('Dokumen kabur', $f->alasan_gugur);
    }
}

public function test_batch_gugur_tanpa_alasan_ditolak(): void
{
    $p = $this->buatPendaftarTerverifikasi();
    $this->actingAs($this->admin())
        ->post('/admin/verifikasi/batch', ['aksi' => 'gugur', 'ids' => [$p->id]])
        ->assertSessionHasErrors('alasan');
    $this->assertSame(\App\Enums\StatusPendaftar::MENUNGGU_VERIFIKASI, $p->fresh()->status);
}

public function test_batch_ids_kosong_atau_sudah_diproses_tidak_memproses_apa_apa(): void
{
    $p = $this->buatPendaftarTerverifikasi();

    // ids kosong → tidak ada yang diproses
    $this->actingAs($this->admin())
        ->post('/admin/verifikasi/batch', ['aksi' => 'lulus', 'ids' => []]);
    $this->assertSame(\App\Enums\StatusPendaftar::MENUNGGU_VERIFIKASI, $p->fresh()->status);

    // proses $p lewat jalur individual, lalu batch (id sudah-diproses + id tak dikenal)
    $this->actingAs($this->admin())
        ->post('/admin/verifikasi/'.$p->id.'/proses', ['aksi' => 'lulus']);
    $this->actingAs($this->admin())
        ->post('/admin/verifikasi/batch', ['aksi' => 'gugur', 'alasan' => 'x', 'ids' => [$p->id, 999999]])
        ->assertRedirect();

    $this->assertSame(\App\Enums\StatusPendaftar::LULUS_VERIFIKASI, $p->fresh()->status);
    $this->assertSame(0, \App\Models\AuditLog::where('detail->massal', true)->count());
}

public function test_batch_diluar_tahap_verifikasi_diblokir(): void
{
    $this->tutupTahap(\App\Enums\TahapJadwal::VERIFIKASI);
    $p = $this->buatPendaftarTerverifikasi();
    $this->actingAs($this->admin())
        ->post('/admin/verifikasi/batch', ['aksi' => 'lulus', 'ids' => [$p->id]])
        ->assertForbidden();
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test tests/Feature/VerifikasiTest.php`
Expected: 4 test batch FAIL (route belum ada); test lama tetap PASS.

- [ ] **Step 3: Implementasi**

- `VerifikasiController`: ekstrak `prosesSatu()` dari logika `proses()` (termasuk cek status MENUNGGU — bila tidak MENUNGGU, lewati tanpa error); method `batch(Request, JadwalService, AuditService)`: guard tahap (abort 403), validasi `aksi in:lulus,gugur`, `alasan required_if:aksi,gugur`, `ids array`; loop ids → `Pendaftar::find` → `prosesSatu` hanya bila ditemukan & MENUNGGU; flash "N pendaftar diproses".
- Route: `Route::post('/verifikasi/batch', ...)->name('verifikasi.batch')` (di dalam grup admin).
- View `admin/verifikasi/index.blade.php`: kolom checkbox `name="ids[]" value="{id}"` + header "pilih semua halaman"; bilah batch (tampil via JS bila ada centang): `[Luluskan N]` `[Gugurkan N]` + input teks alasan (wajib saat gugur massal); form batch POST ke `verifikasi.batch`; tombol individual tetap, "Gugur" individual = input teks inline per baris (buka baris kecil saat diklik) + submit `proses`.

- [ ] **Step 4: Jalankan suite penuh, pastikan hijau**

Run: `php artisan test`
Expected: SEMUA PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(redesign): verifikasi massal (batch) + alasan inline, audit per pendaftar"
```

---

### Task 5: Input nilai ala spreadsheet + batch save

**Files:**
- Modify: `app/Http/Controllers/Admin/NilaiController.php`, `resources/views/admin/nilai/index.blade.php`, `routes/web.php`, `tests/Feature/NilaiTest.php`

**Interfaces:**
- Consumes: `NilaiService::simpan(NilaiSeleksi, array{rapor,prestasi,kunjungan}, ?User)` (sudah bikin riwayat+audit `nilai_ubah` per perubahan); guard tahap `SELEKSI` (abort 403) & status `LULUS_VERIFIKASI` (abort 403); `PengumumanSudahTerbit` guard di service.
- Produces: `POST /admin/nilai/batch` body `{rows: [{id, rapor, prestasi, kunjungan}]}` → XHR: `200 {ok:true, tersimpan:n}` / `422 {ok:false, error, baris:[{id, error}]}`; non-XHR: redirect+flash. **Route `PUT /admin/nilai/{pendaftar}` DIHAPUS.**
- View: tabel (paginasi 50) kolom: No., Nama, Jurusan, input `rapor`/`prestasi`/`kunjungan` (step 0.01, min 0 max 100, `name="rows[{i}][rapor]"` dst), kolom Total (JS live `0.4r+0.3p+0.3k`, 2 desimal), sel berubah diberi class `dirty`; tombol `Simpan Semua` (POST batch, hanya baris dirty) + `Batal`; setelah publish semua input `disabled` (deteksi via data attribute `data-published` dari controller).

- [ ] **Step 1: Amend test di `tests/Feature/NilaiTest.php`**

Ganti `test_input_nilai_menyimpan_dan_membuat_riwayat_saat_diubah` menjadi:

```php
public function test_batch_simpan_dua_baris_satu_kali(): void
{
    $p1 = $this->buatPendaftarLulus();
    $p2 = $this->buatPendaftarLulus();

    $r = $this->actingAs($this->admin())->call('POST', '/admin/nilai/batch', [
        'rows' => [
            ['id' => $p1->id, 'rapor' => 80, 'prestasi' => 90, 'kunjungan' => 100],
            ['id' => $p2->id, 'rapor' => 70, 'prestasi' => 70, 'kunjungan' => 70],
        ],
    ], ['X-Requested-With' => 'XMLHttpRequest']);

    $r->assertOk();
    $this->assertSame(2, $r->getJSON('tersimpan'));
    $this->assertSame(89.0, (float) $p1->fresh()->nilai->total);
    $this->assertSame(70.0, (float) $p2->fresh()->nilai->total);
    $this->assertSame(2, \App\Models\AuditLog::where('aksi', 'nilai_ubah')->count());
}
```

Tambah:

```php
public function test_batch_nilai_invalid_ditolak_per_baris(): void
{
    $p1 = $this->buatPendaftarLulus();
    $p2 = $this->buatPendaftarLulus();

    $r = $this->actingAs($this->admin())->call('POST', '/admin/nilai/batch', [
        'rows' => [
            ['id' => $p1->id, 'rapor' => 150, 'prestasi' => 0, 'kunjungan' => 0],
            ['id' => 999999, 'rapor' => 80, 'prestasi' => 0, 'kunjungan' => 0],
            ['id' => $p2->id, 'rapor' => 80, 'prestasi' => 80, 'kunjungan' => 80],
        ],
    ], ['X-Requested-With' => 'XMLHttpRequest']);

    $r->assertStatus(422);
    $this->assertSame(2, collect($r->getJSON('baris'))->count()); // 2 baris bermasalah
    $this->assertNull($p2->fresh()->nilai); // baris valid tidak disimpan karena ada baris invalid
}

public function test_batch_tidak_membuat_riwayat_bila_nilai_tidak_berubah(): void
{
    $p = $this->buatPendaftarLulus();
    \App\Models\NilaiSeleksi::factory()->for($p)->create([
        'nilai_rapor' => 80, 'nilai_prestasi' => 90, 'nilai_kunjungan' => 100, 'total' => 89.0,
    ]);

    $this->actingAs($this->admin())->call('POST', '/admin/nilai/batch', [
        'rows' => [['id' => $p->id, 'rapor' => 80, 'prestasi' => 90, 'kunjungan' => 100]],
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

    $this->assertSame(0, \App\Models\NilaiSeleksiRiwayat::count());
    $this->assertSame(0, \App\Models\AuditLog::where('aksi', 'nilai_ubah')->count());
}

public function test_route_put_nilai_lama_dihapus(): void
{
    $p = $this->buatPendaftarLulus();
    $this->actingAs($this->admin())->put('/admin/nilai/'.$p->id, ['rapor' => 1, 'prestasi' => 1, 'kunjungan' => 1])
        ->assertNotFound();
}

public function test_batch_diluar_tahap_seleksi_diblokir(): void
{
    $this->tutupTahap(\App\Enums\TahapJadwal::SELEKSI);
    $p = $this->buatPendaftarLulus();
    $this->actingAs($this->admin())->call('POST', '/admin/nilai/batch', [
        'rows' => [['id' => $p->id, 'rapor' => 80, 'prestasi' => 80, 'kunjungan' => 80]],
    ])->assertForbidden();
}
```

(Hapus test `test_pendaftar_belum_lulus...` yang memakai PUT → ganti jadi `batch` terhadap pendaftar MENUNGGU → 403; sesuaikan `test_nilai_di_atas_100_ditolak` menjadi bagian `batch_nilai_invalid` di atas.)

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test tests/Feature/NilaiTest.php`
Expected: test batch FAIL (route belum ada); `put_lama_dihapus` FAIL (route masih ada).

- [ ] **Step 3: Implementasi**

- `NilaiController`: hapus `simpan()`; tambah `batch(Request, NilaiService, JadwalService)`:
  - guard tahap `SELEKSI` (abort 403);
  - validasi: `rows` required array; per baris `id integer exists:pendaftar,id`, `rapor/prestasi/kunjungan numeric 0-100`;
  - **all-or-nothing**: kumpulkan semua error per baris (termasuk `id` yang pendaftarnya bukan `LULUS_VERIFIKASI` → error "bukan pendaftar lulus verifikasi"); bila ada error → jawab 422 JSON (atau flash non-XHR) tanpa menyimpan apa pun;
  - bila bersih: loop `NilaiService::simpan` (hanya baris yang benar-benar berubah akan bikin riwayat/audit — perilaku service ada), hitung `tersimpan` = jumlah baris yang diproses;
  - tangkap `PengumumanSudahTerbit` → 422/flash "Pengumuman sudah terbit".
- Hapus route `PUT /admin/nilai/{pendaftar}`; tambah `POST /admin/nilai/batch` name `nilai.batch`.
- View `admin/nilai/index.blade.php`: ubah kolom nilai jadi input per sel dengan `name="rows[]..."` satu form besar (method POST, action `nilai.batch`, `enctype` default); JS vanilla: (a) hitung total per baris saat input; (b) tandai class `dirty` bila berbeda dari nilai awal (simpan `data-asli`); (c) `Simpan Semua` → kumpulkan hanya baris dirty → `fetch` POST JSON (header XHR) → sukses: flash + update `data-asli` + hapus `dirty`; gagal: tampilkan error per baris (`baris[]`); (d) `Batal` → kembalikan input ke `data-asli`; (e) `data-published="1"` → disable semua input + simpan. Blok **Hasil Ranking** & guard tahap TIDAK diubah.

- [ ] **Step 4: Jalankan suite penuh, pastikan hijau**

Run: `php artisan test`
Expected: SEMUA PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(redesign): input nilai spreadsheet + simpan semua (batch), hapus route PUT per-baris"
```

---

### Task 6: Dashboard pipeline + sidebar grouping + final

**Files:**
- Modify: `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/layouts/admin.blade.php`, `tests/Feature/LaporanTest.php`

**Interfaces:**
- Consumes: semua model; `PengumumanService::sudahTerbit()`; `x-status-badge` (Task 2).
- Produces: `GET /admin` view `admin.dashboard` dengan `assertViewHas('pipeline')`: `pipeline = ['pendaftar' => {total, draf}, 'verifikasi' => {menunggu, lulus, gugur}, 'nilai' => {ternilai, belum}, 'hasil' => {diterima, cadangan, tidak_diterima, terbit: bool}]` + route `GET /admin/dashboard/chart` tidak berubah.

- [ ] **Step 1: Amend test di `LaporanTest`**

Ganti assert `stat` di `test_dashboard_menampilkan_statistik_sesuai_data` dengan:

```php
$this->actingAs($this->admin())
    ->get('/admin')
    ->assertOk()
    ->assertSee('Pendaftaran')
    ->assertViewHas('pipeline', fn (array $pl) =>
        $pl['pendaftar']['total'] === 2
        && $pl['hasil']['diterima'] === 1
        && $pl['hasil']['terbit'] === true);
```

(skenario test sudah `proses()` + `terbitkan()` — pertahankan).

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test tests/Feature/LaporanTest.php`
Expected: test dashboard FAIL (view data `pipeline` belum ada).

- [ ] **Step 3: Implementasi**

- `DashboardController::index`: hitung `pipeline` per definisi (count by status; `ternilai` = pendaftar LULUS yang punya `nilai`; `belum` = LULUS tanpa nilai; `draf` = status DRAF; `terbit` = `PengumumanService::sudahTerbit()`); simpan kartu statistik lama tidak perlu (pipeline menggantikan), grafik & tabel kuota tetap dikirim.
- View `admin.dashboard`: baris 4 kartu pipeline (label, angka kunci, badge status bila perlu, tombol aksi: "Lihat Pendaftar" → `/admin/pendaftar`, "Proses Verifikasi" → `/admin/verifikasi` (angka merah bila `menunggu > 0`), "Input Nilai" → `/admin/nilai`, "Pengumuman" → `/admin/pengumuman` (badge "Terbit"/"Belum")); bawahnya: grafik + tabel kuota (blok lama, dipertahankan).
- `layouts/admin.blade.php`: sisipkan `<div class="sidebar-heading">Data</div>` sebelum Pendaftar; `Proses` sebelum Verifikasi; `Sistem` sebelum Jadwal.

- [ ] **Step 4: Final verification — suite penuh + style**

Run: `php artisan test` → Expected: SEMUA PASS (target ± 60 test).
Run: `./vendor/bin/pint` → lalu `./vendor/bin/pint --test` → Expected: bersih.
Run: `php artisan test` lagi setelah pint → Expected: SEMUA PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(redesign): dashboard pipeline tahap, sidebar grouping, final style"
```

---

## Catatan Eksekusi

- Urutan task penting: Task 1 (auth) sebelum Task 2 (form) karena form bergantung pada auto-login; Task 4 & 5 independen satu sama lain.
- Test yang DIHAPUS hanya test yang menguji perilaku yang memang dihapus (verifikasi email, PUT nilai per-baris) — masing-masing sudah digantikan test baru pada task yang sama.
- Jangan sentuh `app/Services/Psb/RankingService.php`, `PengumumanService.php`, `PendaftaranService.php` (kecuali Task 1 tidak menyentuhnya) — friksi yang dipotong ada di lapisan controller/view/route.
- Setelah semua task: final review whole-branch (subagent fresh), lalu laporkan rulings & minors tertunda.
