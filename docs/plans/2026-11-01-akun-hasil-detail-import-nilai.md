# Implementation Plan: Ubah Password, Hasil Seleksi Detail, Import Nilai CSV

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan tiga fitur penting: halaman ubah password (`/akun`), rincian hasil seleksi di `/status` (skor, peringkat, via, kuota), dan import nilai massal dari CSV (admin).

**Architecture:** Mengikuti pola yang sudah ada — controller tipis + service untuk logika bisnis (`app/Services/Psb/`), view Blade Tailwind (portal) / kartu Tailwind di layout admin, tes fitur di `tests/Feature/` dengan helper `tests/TestCase.php`. TDD per task, commit per task.

**Tech Stack:** Laravel 10, PHP 8.5, PHPUnit (bukan Pest), Blade + Tailwind, SQLite (dev), fputcsv/fgetcsv bawaan PHP (tanpa dependency baru).

**Spec:** `docs/designs/2026-11-01-akun-hasil-detail-import-nilai.md`

## Global Constraints

- Semua copy user-facing dalam bahasa Indonesia.
- Tes dijalankan dengan `php artisan test` dari root proyek; baseline sekarang 96 test lulus.
- Helper tes yang dipakai (dari `tests/TestCase.php`): `$this->seed()`, `bukaTahap(TahapJadwal $t)`, `tutupTahap($t)`, `admin()`, `buatPendaftarLulus()`; state factory `Pendaftar::factory()->denganUser()->lulusVerifikasi()->denganNilai(r,p,k)->denganHasil(statusAkhir, kodeJurusan, peringkat, via)`.
- Bobot nilai dari `config('psb.bobot')` = rapor 0.4 / prestasi 0.3 / kunjungan 0.3.
- Aksi admin yang mengubah data terkunci: (a) tahap `SELEKSI` harus buka via `JadwalService::isBuka()` → selain itu `abort(403)`; (b) setelah pengumuman terbit, `NilaiService` melempar `App\Exceptions\PengumumanSudahTerbit`.
- User factory: password default `'password'`; `User::factory()->admin()` untuk admin.
- Commit message style repo: `feat: ...`, `test: ...`, `docs: ...` (deskripsi bahasa Indonesia boleh).
- **JANGAN push ke origin/main** — hanya commit lokal.

## Review Focus

Gagal-mode yang disiratkan spec tapi tidak otomatis teruji; masing-masing dikunci oleh tes di task pemegangnya:

1. **CSV hasil export Excel (UTF-8 BOM + baris `\r\n`)** harus ter-parse benar → Tes di Task 4: `test_import_csv_dengan_bom_dan_crlf_berhasil`.
2. **CSV > 1000 baris data** harus ditolak dengan pesan jelas (bukan dipotong diam-diam) → Tes di Task 4: `test_import_csv_lebih_1000_baris_ditolak`.
3. **Desimal pakai koma (`85,5`)** harus ditolak dengan pesan "pakai titik" → Tes di Task 4: `test_import_csv_desimal_koma_ditolak`.
4. **Header CSV dengan kolom tambahan/unknown** harus di-parse berdasarkan nama kolom, bukan posisi → Tes di Task 4: `test_import_csv_kolom_tambahan_diabaikan`.
5. **`hasil` tanpa jurusan** (`tidak_diterima`, `jurusan_id` null) — kartu detail di `/status` harus render tanpa error, tanpa baris peringkat/jurusan/kuota → Tes di Task 2: `test_tidak_diterima_tanpa_jurusan_render_aman`.

---

### Task 1: Ubah Password — halaman Akun

**Files:**
- Create: `app/Http/Controllers/Portal/AkunController.php`
- Create: `resources/views/portal/akun/index.blade.php`
- Modify: `routes/web.php` (grup `Route::middleware(['auth'])` pertama, baris ~85)
- Modify: `resources/views/layouts/portal.blade.php` (nav, setelah link Pendaftaran baris 28)
- Test: `tests/Feature/AkunTest.php`

**Interfaces:**
- Produces: route `akun` (`GET /akun`), route `akun.password` (`POST /akun/password`). Nama field form: `password_lama`, `password`, `password_confirmation`. Flash sukses di login: `'Password berhasil diubah. Silakan masuk kembali.'`
- Consumes: `Auth::logout()`, `$request->session()->invalidate()` (pola sama dengan `AuthController::logout`), cast `hashed` di model User.

- [ ] **Step 1: Tulis tes gagal** — buat `tests/Feature/AkunTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/akun')->assertRedirect('/login');
    }

    public function test_calon_siswa_melihat_data_akun(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/akun')
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_ganti_password_ditolak_jika_password_lama_salah(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/akun/password', [
            'password_lama' => 'salah',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertSessionHasErrors('password_lama');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_ganti_password_berhasil_memaksa_login_ulang(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/akun/password', [
            'password_lama' => 'password',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('passwordbaru1', $user->fresh()->password));

        $this->post('/login', ['email' => $user->email, 'password' => 'passwordbaru1'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_password_baru_kurang_8_karakter_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/akun/password', [
            'password_lama' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter AkunTest`
Expected: 5 FAIL (route `/akun` belum ada → 404/redirect tak sesuai).

- [ ] **Step 3: Implementasi `AkunController`** — `app/Http/Controllers/Portal/AkunController.php`, namespace `App\Http\Controllers\Portal`, extend `App\Http\Controllers\Controller`:

```php
public function index(): View
{
    return view('portal.akun.index');
}

public function updatePassword(Request $request): RedirectResponse
```

Urutan di `updatePassword`: (1) `$request->validate([...])` dengan aturan `'password_lama' => ['required','string']`, `'password' => ['required','string','min:8','confirmed']`; (2) cek `Hash::check($request->input('password_lama'), $request->user()->password)` → jika salah `back()->withErrors(['password_lama' => 'Password lama salah.'])`; (3) `$request->user()->update(['password' => $request->input('password')])` (cast `hashed` yang meng-hash); (4) `Auth::logout()` + `$request->session()->invalidate()` + `$request->session()->regenerateToken()`; (5) `return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan masuk kembali.');`

- [ ] **Step 4: Tambah route** di `routes/web.php` dalam grup `Route::middleware(['auth'])->group(function () {` (yang memuat route `status` dan `surat`):

```php
Route::get('/akun', [AkunController::class, 'index'])->name('akun');
Route::post('/akun/password', [AkunController::class, 'updatePassword'])->name('akun.password');
```

+ import `use App\Http\Controllers\Portal\AkunController;` di atas file.

- [ ] **Step 5: Buat view** `resources/views/portal/akun/index.blade.php` — `@extends('layouts.portal')`, `@section('title', 'Akun Saya')`. Isi: kartu data (Nama `auth()->user()->name`, Email, NISN `auth()->user()->nisn ?? '—'` + catatan kecil "Email tidak dapat diubah — hubungi admin") dan kartu form ubah password (3 field: `password_lama`, `password`, `password_confirmation`, semua type password; submit `POST` ke `route('akun.password')` dengan `@csrf`). Gaya Tailwind meniru `resources/views/portal/status/index.blade.php` (kartu `bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-6`).

- [ ] **Step 6: Tambah link nav** di `resources/views/layouts/portal.blade.php` setelah link Pendaftaran (baris 28), gaya identik:

```blade
<a href="{{ route('akun') }}" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('akun') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100' }} transition">Akun</a>
```

- [ ] **Step 7: Jalankan, pastikan lulus**

Run: `php artisan test --filter AkunTest`
Expected: 5 PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Portal/AkunController.php resources/views/portal/akun/index.blade.php routes/web.php resources/views/layouts/portal.blade.php tests/Feature/AkunTest.php
git commit -m "feat: halaman akun + ubah password (logout paksa setelah berhasil)"
```

---

### Task 2: Hasil Seleksi Detail di /status

**Files:**
- Modify: `app/Http/Controllers/Portal/StatusController.php:16-24`
- Modify: `resources/views/portal/status/index.blade.php:105-127` (blok `@if ($p->hasil && $sudahTerbit)`)
- Test: `tests/Feature/StatusHasilTest.php`

**Interfaces:**
- Consumes: factory states `denganNilai(?float, ?float, ?float)`, `denganHasil(string $statusAkhir, string $kodeJurusan, int $perankingan = 1, int $via = 1)`; `App\Services\Psb\PengumumanService::terbitkan(User)`; model `Kuota` (field `kuota`, `cadangan`, `terisi`); `Hasil::factory()`.
- String tampilan yang dikunci tes (WAJIB persis): label status `Diterima` / `Cadangan` / `Tidak Diterima`; `Peringkat {n}`; `via pilihan P{1|2}`; `Kuota {n}`; nilai numerik `85`, `90`, `88`, `87.4` (total dari bobot 0.4/0.3/0.3). Badge cadangan memakai kelas Tailwind `bg-amber-50`.

- [ ] **Step 1: Tulis tes gagal** — buat `tests/Feature/StatusHasilTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\Kuota;
use App\Models\Pendaftar;
use App\Services\Psb\PengumumanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusHasilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Kuota::updateOrCreate(
            ['jurusan_id' => Jurusan::where('kode', 'TKJ')->value('id')],
            ['kuota' => 28, 'cadangan' => 3, 'terisi' => 3]
        );
    }

    private function pendaftarHasil(string $statusAkhir, ?int $perankingan = 3, ?int $via = 1, bool $terbit = true): Pendaftar
    {
        $p = Pendaftar::factory()
            ->denganUser()
            ->lulusVerifikasi()
            ->denganNilai(85, 90, 88)
            ->create();

        Hasil::factory()->for($p)->create([
            'status_akhir' => $statusAkhir,
            'jurusan_id' => $perankingan ? Jurusan::where('kode', 'TKJ')->value('id') : null,
            'perankingan' => $perankingan,
            'via_pilihan' => $via,
        ]);

        if ($terbit) {
            app(PengumumanService::class)->terbitkan($this->admin());
        }

        return $p;
    }

    public function test_diterima_melihat_rincian_lengkap(): void
    {
        $p = $this->pendaftarHasil('diterima', 3, 1);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Diterima')
            ->assertSee('Peringkat 3')
            ->assertSee('via pilihan P1')
            ->assertSee('Kuota 28')
            ->assertSee('85')
            ->assertSee('90')
            ->assertSee('88')
            ->assertSee('87.4');
    }

    public function test_cadangan_melihat_label_cadangan(): void
    {
        $p = $this->pendaftarHasil('cadangan', 30, 1);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Cadangan')
            ->assertSee('bg-amber-50')
            ->assertSee('Peringkat 30');
    }

    public function test_tidak_diterima_tanpa_jurusan_render_aman(): void
    {
        $p = $this->pendaftarHasil('tidak_diterima', null, null);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Tidak Diterima')
            ->assertDontSee('Peringkat')
            ->assertDontSee('Kuota');
    }

    public function test_hasil_yang_belum_terbit_tidak_tampil(): void
    {
        $p = $this->pendaftarHasil('diterima', 3, 1, terbit: false);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Menunggu pengumuman')
            ->assertDontSee('Peringkat');
    }

    public function test_cek_status_publik_tidak_memuat_rincian(): void
    {
        $p = $this->pendaftarHasil('diterima', 3, 1);

        $this->post('/cek-status', [
            'nomor' => $p->nomor_pendaftaran,
            'tgl_lahir' => $p->tanggal_lahir->format('Y-m-d'),
        ])
            ->assertOk()
            ->assertDontSee('Peringkat')
            ->assertDontSee('87.4');
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter StatusHasilTest`
Expected: FAIL (string 'Peringkat 3', 'via pilihan P1', 'Kuota 28', 'bg-amber-50' belum ada di view).

- [ ] **Step 3: Eager-load di controller** — `StatusController::index`:

```php
$p = Pendaftar::where('pengguna_id', $request->user()->id)
    ->with(['hasil.jurusan.kuota', 'nilai'])
    ->first();
```

- [ ] **Step 4: Perluas kartu Hasil Seleksi** di `resources/views/portal/status/index.blade.php` (blok baris 105-127). Isi kartu yang direncanakan (string persis seperti Interfaces):
  - Baris label status: `Diterima` (badge emerald), `Cadangan` (badge dengan kelas `bg-amber-50` + teks amber), `Tidak Diterima` (badge rose/slate) — ganti logika ternary emerald/rose lama dengan `match` atas `$p->hasil->status_akhir`.
  - Jika `$p->hasil->perankingan` ada: baris `Peringkat {{ $p->hasil->perankingan }}` di jurusan `{{ $p->hasil->jurusan->nama }}` + `via pilihan P{{ $p->hasil->via_pilihan }}`.
  - Jika `$p->hasil->jurusan` ada: baris `Kuota {{ $p->hasil->jurusan->kuota->kuota }}`.
  - Jika `$p->nilai` ada: tabel kecil rincian — `Rapor {{ $p->nilai->nilai_rapor }}`, `Prestasi {{ $p->nilai->nilai_prestasi }}`, `Kunjungan {{ $p->nilai->nilai_kunjungan }}`, `Total {{ $p->nilai->total }}` + catatan bobot (40/30/30).
  - Pertahankan tombol "Cetak Surat Keterangan Diterima" untuk status `diterima`.
  - Semua akses lewat null-safe (`?->`) — hasil tanpa jurusan tidak boleh crash.

- [ ] **Step 5: Jalankan, pastikan lulus**

Run: `php artisan test --filter StatusHasilTest`
Expected: 5 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Portal/StatusController.php resources/views/portal/status/index.blade.php tests/Feature/StatusHasilTest.php
git commit -m "feat: rincian hasil seleksi di /status (skor, peringkat, via, kuota)"
```

---

### Task 3: NilaiService::impor() — aturan bisnis all-or-nothing

**Files:**
- Modify: `app/Services/Psb/NilaiService.php`
- Test: `tests/Feature/NilaiImportTest.php` (dibuat di task ini, ditambah lagi di Task 4)

**Interfaces:**
- Produces:

```php
/**
 * Import nilai massal (all-or-nothing). Jika ada 1 baris error, TIDAK ada yang disimpan.
 *
 * @param array<int, array{nisn: string, rapor: float, prestasi: float, kunjungan: float, baris: int}> $baris
 * @return array{ok: true, tersimpan: int}|array{ok: false, pesan: string, baris: array<int, array{baris: int, nisn: string, error: string}>}
 */
public function impor(array $baris, ?User $by = null): array
```

  - Melempar `App\Exceptions\PengumumanSudahTerbit` jika pengumuman sudah terbit (cek paling awal).
  - Error per baris mungkin: `'Pendaftar tidak ditemukan.'`, `'Bukan pendaftar lulus verifikasi.'`, `'NISN duplikat di file.'`
  - Pesan agregat: `'Ada N baris bermasalah.'` (N = jumlah baris error)
  - Audit: `app(AuditService::class)->catat($by, 'psb', 'nilai_impor', ['jumlah' => N])` + audit per nilai dari `simpan()` yang sudah ada.
- Consumes: `NilaiService::simpan(NilaiSeleksi, array{rapor, prestasi, kunjungan}, ?User)` (sudah ada), `Pendaftar::where('nisn', ...)`, status `StatusPendaftar::LULUS_VERIFIKASI`.

- [ ] **Step 1: Tulis tes gagal** — buat `tests/Feature/NilaiImportTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Exceptions\PengumumanSudahTerbit;
use App\Models\AuditLog;
use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\NilaiSeleksi;
use App\Models\Pendaftar;
use App\Services\Psb\NilaiService;
use App\Services\Psb\PengumumanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NilaiImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bukaTahap(\App\Enums\TahapJadwal::SELEKSI);
    }

    private function baris(Pendaftar $p, float $r = 85, float $pr = 90, float $k = 88, int $no = 2): array
    {
        return ['nisn' => $p->nisn, 'rapor' => $r, 'prestasi' => $pr, 'kunjungan' => $k, 'baris' => $no];
    }

    public function test_impor_valid_menyimpan_semua_dan_mencatat_audit(): void
    {
        [$a, $b] = [$this->buatPendaftarLulus(), $this->buatPendaftarLulus()];
        $service = app(NilaiService::class);

        $r = $service->impor([$this->baris($a), $this->baris($b)], $this->admin());

        $this->assertTrue($r['ok']);
        $this->assertSame(2, $r['tersimpan']);
        $this->assertSame(2, NilaiSeleksi::count());
        $this->assertSame($service->hitungTotal(85, 90, 88), (float) $a->nilai->total);
        $this->assertSame(1, AuditLog::where('aksi', 'nilai_impor')->count());
    }

    public function test_impor_all_or_nothing_jika_ada_baris_rusak(): void
    {
        $a = $this->buatPendaftarLulus();

        $r = app(NilaiService::class)->impor([
            $this->baris($a),
            ['nisn' => '0000000000', 'rapor' => 80, 'prestasi' => 80, 'kunjungan' => 80, 'baris' => 3],
        ]);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Pendaftar tidak ditemukan', $r['baris'][0]['error']);
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_impor_ditolak_jika_nisn_bukan_lulus_verifikasi(): void
    {
        $p = Pendaftar::factory()->denganUser()->menungguVerifikasi()->create();

        $r = app(NilaiService::class)->impor([$this->baris($p)]);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('lulus verifikasi', $r['baris'][0]['error']);
    }

    public function test_impor_ditolak_jika_nisn_duplikat_dalam_file(): void
    {
        $a = $this->buatPendaftarLulus();

        $r = app(NilaiService::class)->impor([$this->baris($a, 80, 80, 80, 2), $this->baris($a, 90, 90, 90, 3)]);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('duplikat', $r['baris'][0]['error']);
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_impor_ditolak_setelah_pengumuman_terbit(): void
    {
        $a = $this->buatPendaftarLulus();
        Hasil::factory()->for($a)->create(['status_akhir' => 'diterima']);
        app(PengumumanService::class)->terbitkan($this->admin());

        $this->expectException(PengumumanSudahTerbit::class);
        app(NilaiService::class)->impor([$this->baris($a)]);
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter NilaiImportTest`
Expected: 5 FAIL (method `impor()` tidak ada).

- [ ] **Step 3: Implementasi `impor()` di `app/Services/Psb/NilaiService.php`**

Algoritma (ditulis karena tidak terdeduksi dari tes):
1. `if (app(PengumumanService::class)->sudahTerbit()) throw PengumumanSudahTerbit::make();`
2. Loop `$baris`: `$lihat` (array NISN→true) untuk deteksi duplikat; `$p = Pendaftar::where('nisn', $row['nisn'])->first();` — error sesuai Interfaces; `continue` jika error.
3. Jika ada error: `return ['ok' => false, 'pesan' => 'Ada '.count($errors).' baris bermasalah.', 'baris' => $errors];` — TIDAK ada penulisan DB sebelumnya (all-or-nothing).
4. `DB::transaction(function () use (...)` — loop `simpan($p->nilai()->firstOrNew(), ['rapor' => $row['rapor'], 'prestasi' => $row['prestasi'], 'kunjungan' => $row['kunjungan']], $by)`; count.
5. `app(AuditService::class)->catat($by, 'psb', 'nilai_impor', ['jumlah' => $tersimpan]);` lalu `return ['ok' => true, 'tersimpan' => $tersimpan];`

- [ ] **Step 4: Jalankan, pastikan lulus**

Run: `php artisan test --filter NilaiImportTest`
Expected: 5 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Psb/NilaiService.php tests/Feature/NilaiImportTest.php
git commit -m "feat: NilaiService::impor — import nilai massal all-or-nothing + audit"
```

---

### Task 4: Import Nilai via HTTP (controller, route, template, view admin)

**Files:**
- Modify: `app/Http/Controllers/Admin/NilaiController.php`
- Modify: `routes/web.php` (grup admin, setelah route `nilai/batch`)
- Modify: `resources/views/admin/nilai/index.blade.php`
- Test: `tests/Feature/NilaiImportTest.php` (tambah tes HTTP di file yang sudah ada dari Task 3)

**Interfaces:**
- Consumes: `NilaiService::impor(array $baris, ?User $by): array` (Task 3) dengan shape return `['ok' => true, 'tersimpan' => int]` atau `['ok' => false, 'pesan' => string, 'baris' => [...]]`.
- Produces: route `nilai.template` (`GET /admin/nilai/import/template`), route `nilai.import` (`POST /admin/nilai/import`). Flash sukses: `'{N} nilai berhasil diimpor.'`; flash error: session key `imporError` = `['pesan' => string, 'baris' => array]` (bentuk sama dengan return error service).
- Format CSV (header wajib, urutan bebas, case-insensitive, kolom ekstra diabaikan): `nisn,rapor,prestasi,kunjungan`. Desimal pakai titik. Maksimal 1000 baris data. Baris kosong dilewati. Nomor baris untuk laporan = nomor fisik di file (header = baris 1).
- Pesan error format (dipakai di view & tes): header → `'Header CSV harus berisi kolom: nisn,rapor,prestasi,kunjungan.'`; kompa → `'Desimal harus memakai titik (contoh: 85.5).'`; non-angka → `'Tidak berupa angka.'`; range → `'Nilai harus antara 0 dan 100.'`; NISN → `'NISN harus 10 digit angka.'`; limit → `'Maksimal 1000 baris data.'`

- [ ] **Step 1: Tambah tes HTTP gagal** di `tests/Feature/NilaiImportTest.php` (method di dalam class yang sudah ada):

```php
    public function test_template_csv_terunduh(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/nilai/import/template')
            ->assertOk()
            ->assertDownload('template-nilai.csv');

        $this->assertStringContainsString('nisn,rapor,prestasi,kunjungan', response()->getContent());
    }

    public function test_import_csv_valid_berhasil(): void
    {
        [$a, $b] = [$this->buatPendaftarLulus(), $this->buatPendaftarLulus()];
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},85,90,88\n{$b->nisn},80,70,60\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect()
            ->assertSessionHas('status', '2 nilai berhasil diimpor.');

        $this->assertSame(2, NilaiSeleksi::count());
    }

    public function test_import_csv_dengan_bom_dan_crlf_berhasil(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "\xEF\xBB\xBF" . "nisn,rapor,prestasi,kunjungan\r\n{$a->nisn},85,90,88\r\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect()
            ->assertSessionHas('status', '1 nilai berhasil diimpor.');
    }

    public function test_import_csv_lebih_1000_baris_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $baris = "nisn,rapor,prestasi,kunjungan\n" . str_repeat("{$a->nisn},85,90,88\n", 1001);

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $baris)])
            ->assertRedirect();

        $this->assertStringContainsString('Maksimal 1000 baris data', session('imporError')['pesan'] ?? '');
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_import_csv_desimal_koma_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},85,5,90,88\n"; // "85" dipisah koma → kolom bergeser

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $err = session('imporError')['baris'][0]['error'] ?? '';
        $this->assertMatchesRegularExpression('/(koma|titik|angka|kolom)/', $err);
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_import_csv_header_salah_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "nama,rapor\n{$a->nama_lengkap},85\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $this->assertStringContainsString('Header CSV', session('imporError')['pesan'] ?? '');
    }

    public function test_import_csv_kolom_tambahan_diabaikan(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "ket,nisn,rapor,prestasi,kunjungan\nok,{$a->nisn},85,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect()
            ->assertSessionHas('status', '1 nilai berhasil diimpor.');
    }

    public function test_import_diblokir_diluar_tahap_seleksi(): void
    {
        $this->tutupTahap(\App\Enums\TahapJadwal::SELEKSI);
        $csv = "nisn,rapor,prestasi,kunjungan\n1234567890,85,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertForbidden();
    }
```

Catatan tes `test_import_csv_desimal_koma_ditolak`: isi `85,5` di kolom rapor membuat baris menjadi 5 kolom; kolom `kunjungan` terisi `88` dan ada sel berlebih — baris ini TIDAK akan valid (rapor='85' ok, prestasi='5' ok, kunjungan='88' ok, sel ke-5 terbuang) — MAKA itu aslinya bisa lolos; untuk menjamin error, ganti isi CSV menjadi `{$a->nisn},85,5,90` (3 nilai, salah 1 kolom jadi '5' dan 'kunjungan' kosong → error non-angka). **Pakai versi ini:** `$csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},85,5,90\n";` (kolom `kunjungan` kosong → `'Tidak berupa angka.'`).

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter NilaiImportTest`
Expected: 5 lama PASS, 8 baru FAIL (route belum ada).

- [ ] **Step 3: Implementasi `template()` dan `import()` di `NilaiController`**

`template()` — tanpa gate tahap (hanya unduh template):

```php
public function template(): \Symfony\Component\HttpFoundation\StreamedResponse
```

Isi: `response()->streamDownload(function () { $out = fopen('php://output', 'w'); fputcsv($out, ['nisn', 'rapor', 'prestasi', 'kunjungan']); fputcsv($out, ['1234567890', '85.5', '90', '88']); fclose($out); }, 'template-nilai.csv', ['Content-Type' => 'text/csv']);`

`import(Request $request, NilaiService $service, JadwalService $jadwal)` — urutan:
1. Gate: `if (! $jadwal->isBuka(TahapJadwal::SELEKSI)) abort(403, 'Tahap seleksi tidak sedang dibuka.');`
2. `$request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);`
3. Parse: `fopen($request->file('file')->getRealPath(), 'r')` + `fgetcsv($fh)` (default delimiter `,`).
   - Baris pertama = header: buang BOM (`str_replace("\xEF\xBB\xBF", '', $sel)`), trim, `mb_strtolower`; buat `array_flip` indeks kolom; harus memuat keempat `nisn,rapor,prestasi,kunjungan` → selain itu balas error header (langkah 5 dengan `['pesan' => ..., 'baris' => []]`).
   - Loop baris data: nomor baris fisik (header = 1); baris kosong (semua sel `''`/`null`) dilewati; > 1000 baris data → balas error limit.
   - Per sel: trim; NISN harus `preg_match('/^\d{10}$/', ...)`; nilai: `str_contains($v, ',')` → pesan koma; `! is_numeric($v)` → pesan non-angka; `< 0 || > 100` → pesan range. Kumpulkan baris error `['baris' => int, 'nisn' => string, 'error' => string]`.
4. Jika ada error format → balas error (all-or-nothing, tidak panggil service).
5. Tanpa error: `$r = $service->impor($baris, $request->user());` → ok: `return back()->with('status', $r['tersimpan'].' nilai berhasil diimpor.');` — error: `return back()->with('imporError', $r);`
6. `fclose($fh)` di `finally`/akhir (bisa `try/finally`).

- [ ] **Step 4: Tambah route** di grup admin `routes/web.php` (setelah `Route::post('/nilai/batch', ...)`):

```php
Route::get('/nilai/import/template', [NilaiController::class, 'template'])->name('nilai.template');
Route::post('/nilai/import', [NilaiController::class, 'import'])->name('nilai.import');
```

- [ ] **Step 5: Tambah kartu Import di `resources/views/admin/nilai/index.blade.php`**

Sisipkan setelah kartu "Input Nilai" (sebelum blok `<script>` di `@if (! $sudahTerbit)`), dibungkus `@if (! $sudahTerbit)`:
- Kartu gaya sama (`bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5`).
- Judul `Import Nilai (CSV)` + keterangan format (header, desimal titik, max 1000 baris, all-or-nothing) + link `Unduh Template` ke `route('nilai.template')`.
- Jika `session('imporError')`: blok error (judul `session('imporError.pesan')` + `<ul>` daftar `Baris {baris} (NISN {nisn}): {error}`).
- Form `POST` `route('nilai.import')` `enctype="multipart/form-data"`: input `type="file" name="file" accept=".csv" required` + tombol submit `Import`.

- [ ] **Step 6: Jalankan, pastikan lulus**

Run: `php artisan test --filter NilaiImportTest`
Expected: 13 PASS (5 lama + 8 baru).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/NilaiController.php routes/web.php resources/views/admin/nilai/index.blade.php tests/Feature/NilaiImportTest.php
git commit -m "feat: import nilai CSV di admin (template, parse, laporan error per baris)"
```

---

### Task 5: Sinkronisasi dokumen TA (user-requirements + system-design + README)

**Files:**
- Modify: `docs/user-requirements.md` (§6 Spesifikasi Use Case, §7 Kebutuhan Fungsional)
- Modify: `docs/system-design.md` (§6.2, §6.3, §7, §9)
- Modify: `README.md` (bagian kredensial/catatan dev)

**Interfaces:**
- Consumes: struktur existing dokumen (UC-01..UC-14 di §6 user-requirements; §6.2 portal calon siswa & §6.3 admin & §7 Keamanan & §9 Peta Route di system-design).
- Tidak ada kode — verifikasi = dokumen konsisten + `php artisan test` tetap hijau.

- [ ] **Step 1: Perbarui `docs/user-requirements.md`**
  - §6: tambah 2 use case baru setelah UC-14, format sama dengan UC yang ada (aktor, tujuan, alur, catatan):
    - `### UC-15 Ubah Password (Calon Siswa/Admin)` — via `/akun`; password lama wajib benar; min 8 + konfirmasi; setelah sukses logout paksa.
    - `### UC-16 Import Nilai dari CSV (Admin)` — format `nisn,rapor,prestasi,kunjungan`; all-or-nothing; laporan error per baris; hanya saat tahap seleksi buka & sebelum pengumuman terbit.
    - Ubah judul/deskripsi UC-06 menjadi mencakup "rincian hasil (skor, peringkat, via, kuota) setelah pengumuman terbit".
  - §7: tambah baris FR untuk ketiga fitur (nomor FR melanjutkan urut existing) + catat reset password via email yang sudah ada (FR sebelumnya bila belum tercatat).

- [ ] **Step 2: Perbarui `docs/system-design.md`**
  - §6.2: tambah layar "Akun" (form ubah password).
  - §6.2 atau §5: catat kartu "Hasil Seleksi" bertambah rincian (skor per komponen, peringkat, via P1/P2, kuota).
  - §6.3: tambah kartu "Import Nilai (CSV)" di halaman Nilai & Ranking.
  - §7 Keamanan: tambah poin — reset password via token email + ubah password memaksa login ulang.
  - §9 Peta Layar → Route: tambah `/akun`, `/akun/password`, `/admin/nilai/import`, `/admin/nilai/import/template` (sertakan `/lupa-password` + `/reset-password/{token}` bila belum ada dari fitur reset password).

- [ ] **Step 3: Perbarui `README.md`**
  - Tambah satu paragraf/point di bagian catatan dev: "Reset password & mail: dev memakai `MAIL_MAILER=log` — email (termasuk tautan reset password) tercatat di `storage/logs/laravel.log`; production ganti ke SMTP."

- [ ] **Step 4: Verifikasi akhir**

Run: `php artisan test`
Expected: seluruh suite hijau (baseline 96 + 5 + 5 + 5 + 8 = 119 test).

- [ ] **Step 5: Commit**

```bash
git add docs/user-requirements.md docs/system-design.md README.md
git commit -m "docs: sinkronkan dokumen TA dengan fitur akun, hasil detail, import CSV"
```

---

## Self-Review (sudah dijalankan saat penulisan)

- **Spec coverage:** Fitur 1 → Task 1; Fitur 2 → Task 2; Fitur 3 → Task 3+4; sinkronisasi dokumen → Task 5. ✔
- **Type consistency:** shape return `impor()` (Task 3) sama dengan yang dikonsumsi Task 4 (flash `imporError`); string tes Task 2 sama dengan string view Task 2; nama route konsisten. ✔
- **Review Focus:** kelima failure mode masing-masing punya tes (Task 2 #5, Task 4 #1–4). ✔
- **Proportion:** plan ~1.5x spec; blok kode hanya untuk hal yang tidak bisa dideduksi (tes, algoritma parse/impor). ✔
