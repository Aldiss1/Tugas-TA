# Implementation Plan: Rebuild UI Modern (Tailwind) — PSB SMKN 2 Singosari

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti seluruh tampilan (landing, portal siswa, admin) dari Bootstrap 5 + SB Admin 2 ke design system Tailwind CSS modern, TANPA mengubah satu pun fitur/rute/perilaku — 79 test feature tetap hijau di akhir setiap task.

**Architecture:** View Blade ditulis ulang per grup halaman mengikuti wireframe design doc; Tailwind 3 + @tailwindcss/forms via Vite (build di-commit); komponen UI & ikon sebagai Blade component; JS vanilla per halaman dipertahankan logikanya (fetch upload, checklist, nilai live, batch bar, sidebar).

**Tech Stack:** Laravel 10, Tailwind CSS 3, @tailwindcss/forms, Vite (laravel-vite-plugin), Chart.js (file vendor existing), Heroicons (inline SVG component), Plus Jakarta Sans (Google Fonts CDN).

**Spec:** `docs/2026-11-01-redesign-modern-design.md` (design system §3, wireframe §4, string wajib §5, risiko §6).

## Global Constraints

- **NOL perubahan** route, controller, service, model, validasi, flash message. Hanya: view, asset CSS/JS, `tailwind.config.js`, `postcss.config.js`, `resources/css/app.css`, `resources/js/app.js`, `.gitignore` (baris `/public/build` dihapus), `package.json` (dependensi dev).
- Semua **string wajib §5 design doc** harus tetap tampil persis (test meng-assert string, bukan class).
- Label `x-status-badge` (7 label) tetap persis; hanya class-nya yang berganti (palet: draf=slate, menunggu=amber, lulus=sky, gugur=rose, diterima=emerald, cadangan=amber outline, tidak_diterima=slate).
- `php artisan test` **harus 79/79 hijau di akhir setiap task**; `./vendor/bin/pint --test` bersih di akhir.
- Build Vite (`public/build`) **di-commit** agar aplikasi jalan tanpa `npm run dev`.
- Tidak boleh ada lagi referensi `bootstrap` / `sb-admin-2` / `fontawesome` di view yang sudah di-rebuild.
- Ikon: Heroicons outline sebagai Blade component di `resources/views/components/icons/` (bukan Font Awesome, bukan library npm).

## Review Focus

1. **Halaman yang direbuild tetap 200 & string-nya lengkap** — regresi string §5; → setiap task punya step smoke `curl` + suite hijau.
2. **`@vite` tidak meledak saat manifest build hilang** — build harus di-commit SEBELUM layout pertama memakai `@vite`; → Task 0 step build+commit sebelum Task 1.
3. **Fetch upload dokumen tetap berfungsi** (FormData + header XHR + badge slot + counter 5/5) — logika JS lama dipindah apa adanya ke markup baru; → Task 3 step smoke browser.
4. **Sidebar admin mobile** tidak menutupi konten & off-canvas bisa dibuka/tutup; → Task 4 step smoke (resize via browser tool).
5. **Print surat tetap A4** (utility print + `@media print` menyembunyikan chrome); → Task 2 step smoke.

## File Structure

| File | Tanggung jawab |
|---|---|
| `tailwind.config.js`, `postcss.config.js` | design token (brand, font), plugin forms |
| `resources/css/app.css` | `@tailwind` directives + base (font body, scrollbar halus) |
| `resources/js/app.js` | entry Vite (boilerplate; JS halaman tetap inline per view seperti sekarang) |
| `resources/views/components/ui/*.blade.php` | `button`, `card`, `input`, `select`, `label`, `badge-status`, `alert`, `table-*`, `empty` |
| `resources/views/components/icons/*.blade.php` | Heroicons: home, users, user-plus, check-circle, x-circle, chart-bar, bell, calendar, document-text, arrow-left, magnifying-glass, plus, printer, academic-cap, clock, exclamation |
| `resources/views/layouts/public.blade.php` | navbar publik sticky + footer + flash |
| `resources/views/layouts/portal.blade.php` | navbar siswa + flash + container |
| `resources/views/layouts/admin.blade.php` | sidebar slate-900 (grup Data/Proses/Sistem) + topbar + off-canvas mobile |
| `resources/views/public/{home,cek-status}.blade.php` | landing & cek status publik |
| `resources/views/login.blade.php`, `auth/register.blade.php` | auth satu kartu |
| `resources/views/portal/{pendaftaran/index,status/index,surat}.blade.php` | portal siswa |
| `resources/views/admin/{dashboard,pendaftar/{index,detail,form},verifikasi/index,nilai/index,jurusan/{index,form},jadwal/index,pengumuman,laporan,audit}.blade.php` | admin 12 halaman |
| `tests/Feature/*` | TIDAK diubah kecuali string test ternyata harus ikut (diharuskan minimal) |

---

### Task 0: Setup Tailwind + komponen UI dasar

**Files:**
- Create: `tailwind.config.js`, `postcss.config.js`, `resources/views/components/ui/*`, `resources/views/components/icons/*`
- Modify: `resources/css/app.css`, `resources/js/app.js`, `.gitignore` (hapus baris `/public/build`), `package.json` (via npm)

**Interfaces:**
- Produces (dipakai semua task): `ui-button` (props `variant` primary|secondary|ghost|danger, `size` md|sm, `type`), `ui-card` (slot header/body), `ui-input` (label + input, prop `label`), `ui-select`, `ui-alert` (variant success|error|info), `ui-badge-status` (prop `status` — wrap enum, label persis `StatusPendaftar::label()`), `icons/*` (prop `class`). Komponen status = ganti pemakai `x-status-badge` lama (komponen lama dipertahankan delegasi ke yang baru agar pemakai lama tak rusak).

- [ ] **Step 1: Install dependensi & config**

```bash
npm install -D tailwindcss@^3.4 postcss autoprefixer @tailwindcss/forms@^0.5
npx tailwindcss init -p
```
`tailwind.config.js`: `content: ['./resources/views/**/*.blade.php', './app/**/*.php']`, `corePlugins: { preflight: true }`, `theme.extend.colors.brand` = skala indigo (50 #EEF2FF … 600 #4F46E5 … 900 #312E81), `theme.extend.fontFamily.sans = ['Plus Jakarta Sans','Inter','system-ui','sans-serif']`, plugin `forms`.
`resources/css/app.css`: `@tailwind base; @tailwind components; @tailwind utilities;` + `@layer base { body { @apply text-slate-700 antialiased; } }`.
`.gitignore`: hapus baris `/public/build`.
`resources/js/app.js`: `console.log('PSB SMKN 2 Singosari');` (placeholder entry).

- [ ] **Step 2: Tulis komponen UI & ikon** per tabel File Structure (Blade component anonim dengan `@props`/`$attributes`). `ui-badge-status`: match 7 status → class badge (rounded-full px-2.5 py-0.5 text-xs font-semibold + warna palet Global Constraints); isi `{{ $status->label() }}` PERSIS. Komponen lama `x-status-badge` diubah isinya menjadi `<x-ui-badge-status :status="$status" {{ $attributes }}/>`.

- [ ] **Step 3: Build & commit build**

Run: `npm run build`
Expected: `public/build/manifest.json` ada; `public/build/assets/*.css` berisi utility Tailwind (cek `grep -c 'tailwindcss' public/build/assets/*.css` > 0).

- [ ] **Step 4: Smoke — suite masih hijau (belum ada view yang berubah)**

Run: `php artisan test`
Expected: 79 passed.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "chore: setup tailwind + komponen UI/ikon dasar (belum dipakai view)"
```

---

### Task 1: Layout publik + landing + cek status

**Files:**
- Rewrite: `resources/views/layouts/public.blade.php`, `resources/views/public/home.blade.php`, `resources/views/public/cek-status.blade.php`

**Interfaces:**
- Consumes: `ui-*`, `icons/*` (Task 0); `@vite(['resources/css/app.css','resources/js/app.js'])`.
- String wajib (design §4.1–4.2 & §5): `Penerimaan Siswa Baru 2026/2027`, `Daftar Sekarang`, `Cek Status`, `Cek Status Pendaftaran`, `Program Keahlian`, `Kalender PSB`, `FAQ`, `Tahap aktif`, `Pendaftaran tidak ditemukan`, nama 7 jurusan, label status, `Masuk`/`Daftar` (navbar).

- [ ] **Step 1: Smoke baseline (page lama masih 200)**

Run: `curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/`
Expected: 200.

- [ ] **Step 2: Rewrite `layouts/public.blade.php`** — navbar sticky putih (logo + `PSB {{ config('psb.nama_sekolah') }}`; link Beranda/Jadwal/FAQ; `Masuk` ghost; `Daftar` primary), container `max-w-6xl mx-auto px-4`, flash (ui-alert), footer slate-900 (nama sekolah + alamat), `@vite` + Google Fonts Plus Jakarta Sans.

- [ ] **Step 3: Rewrite `public/home.blade.php`** sesuai wireframe §4.1 (hero gradient `from-brand-600 to-sky-500` + badge TA + H1 + sub + 2 CTA + strip stats; kartu tahap aktif (emerald)/tidak aktif (slate) — teks `Tahap aktif: {label}`; grid jurusan 4 kolom: ikon academic-cap, badge kode, nama, deskripsi, progress bar `terisi/kuota` + teks `Sisa: N`; timeline 4 langkah; kartu `Kalender PSB` (5 baris: label, tanggal, badge `Berlangsung`/`Belum`/`Selesai`); FAQ accordion (detail/summary native — TIDAK perlu JS); CTA banner + sudah di footer).

- [ ] **Step 4: Rewrite `public/cek-status.blade.php`** sesuai §4.2 (kartu tengah `max-w-xl`, form nomor+tanggal, kartu hasil: nama, nomor mono, pilihan (badge slate), `x-status-badge`, alasan (alert rose) bila ada, blok `Hasil Seleksi` bila `$pendaftar->hasil && $sudahTerbit` — logika blade lama dipertahankan).

- [ ] **Step 5: Smoke + suite**

Run: `curl -s http://127.0.0.1:8000/ | grep -c "Penerimaan Siswa Baru 2026/2027"` → Expected: ≥1
Run: `curl -s http://127.0.0.1:8000/cek-status | grep -c "Cek Status Pendaftaran"` → Expected: ≥1
Run: `php artisan test` → Expected: 79 passed.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat(ui): layout publik + landing + cek status (tailwind)"
```

---

### Task 2: Auth + layout portal + status siswa + surat

**Files:**
- Rewrite: `resources/views/login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/layouts/portal.blade.php`, `resources/views/portal/status/index.blade.php`, `resources/views/portal/surat.blade.php`

**Interfaces:**
- String wajib: `Masuk`, `Daftar`, `Daftar Akun`, `Email atau password salah`, `Selamat datang,`, `NISN`, `Ingat saya`, `Belum punya akun?`, `Sudah punya akun?`, `Status Pendaftaran`, `Belum Ada Pendaftaran`, `Mulai Pendaftaran`, `Menunggu pengumuman`, `Cetak Surat Keterangan Diterima`, `Kirim Ulang Pendaftaran`, `SURAT KETERANGAN DITERIMA`, `Cetak / Simpan PDF`, `No. Pendaftaran`.

- [ ] **Step 1: Rewrite `login.blade.php`** (latar gradient tipis `from-slate-50 to-brand-50`, kartu `max-w-md` rounded-2xl shadow, logo 64px, H1 `Masuk`, sub `PSB {sekolah}`, ui-alert error/success, form email+password+remember, tombol `Masuk`, link `Belum punya akun? Daftar sekarang`). Form action `route('login')` + `@csrf` DIPERTAHANKAN.

- [ ] **Step 2: Rewrite `auth/register.blade.php`** (kartu `max-w-md`, H1 `Daftar Akun`, 5 field, `@error` per field, tombol `Daftar`, link `Sudah punya akun? Login`).

- [ ] **Step 3: Rewrite `layouts/portal.blade.php`** — navbar putih sticky ring-1: logo, link `Status` (`route('status')`) + `Pendaftaran` (`route('pendaftaran')`) (aktif = text-brand-700 font-semibold), kanan: nama user + tombol `Logout` (form POST `route('logout')`), flash ui-alert, container `max-w-6xl`, `@vite` + font.

- [ ] **Step 4: Rewrite `portal/status/index.blade.php`** sesuai §4.6 (kartu hero: `Halo, {nama}` + nomor `font-mono text-2xl` + `x-status-badge` besar + alasan (alert rose) + tombol `Kirim Ulang Pendaftaran` bila `$bolehRevisi && status gugur`; grid 2: kartu Kelengkapan Dokumen (5 baris + badge ✓/belum) & kartu Info; kartu Hasil bila `$p->hasil && $sudahTerbit` (status akhir + jurusan + peringkat + `Cetak Surat Keterangan Diterima`); elseif lulus & belum terbit → kartu `Menunggu pengumuman`; bila `!$p` → empty state `Belum Ada Pendaftaran` + `Mulai Pendaftaran`). Logika blade lama (semua kondisi & tombol) DIPERTAHANKAN persis.

- [ ] **Step 5: Rewrite `portal/surat.blade.php`** — layout A4: `<div class="print-area">` kop (logo + nama sekolah + alamat + garis double), nomor surat, judul `SURAT KETERANGAN DITERIMA` (underline center), tabel data (nomor pendaftaran, nama, NISN, tempat/tgl lahir, asal sekolah, jurusan diterima, peringkat), paragraf pernyataan, blok ttd (Singosari, tgl; Kepala {sekolah}; spasi; <u>Sumijah, S.Pd., M.Si.</u>; NIP). Tombol `Cetak / Simpan PDF` (`onclick="window.print()"`) + `@media print { .no-print{display:none} body{background:#fff} }`. String `SUMIJAH`/nama kasek TETAP.

- [ ] **Step 6: Smoke + suite**

Run: `php artisan test` → Expected: 79 passed (AuthTest, StatusRevisiTest, PengumumanSuratTest).
Run: `curl -s http://127.0.0.1:8000/login | grep -c "Belum punya akun?"` → ≥1.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat(ui): auth, layout portal, status siswa, surat (tailwind)"
```

---

### Task 3: Form pendaftaran siswa (1 halaman + checklist + fetch upload)

**Files:**
- Rewrite: `resources/views/portal/pendaftaran/index.blade.php`

**Interfaces:**
- String wajib: `Formulir Pendaftaran`, `Data Diri`, `Orang Tua`, `Pilihan Jurusan`, `Dokumen Persyaratan`, `✓ Tersimpan`, `Belum`, `mengunggah…`, `Gagal`, `Dokumen 5/5` (pola `Dokumen N/5`), `Simpan Draf`, `Kirim Pendaftaran`, `NISN`, `Pilihan 1 (wajib)`, `Pilihan 2 (opsional)`, `Ukuran file maksimal 2 MB.`, `Tipe file harus PDF, JPG, atau PNG.`.
- Perilaku wajib (test + E2E): form utama `id="form-pendaftaran"` POST `route('pendaftaran')`; form slot `id="form-dok-{jenis}"` DI LUAR form utama (lesson E2E — nested form = bug); fetch XHR header `X-Requested-With`; `id="btn-kirim"` + form `id="form-kirim"` POST `/pendaftaran/kirim` dengan `pendaftar_id`; konfirmasi via `confirm()`.

- [ ] **Step 1: Smoke baseline** — `php artisan test tests/Feature/PendaftaranTest.php tests/Feature/RedesignTest.php` → hijau (baseline sebelum rewrite).

- [ ] **Step 2: Rewrite view** sesuai §4.5: grid `lg:grid-cols-12` — konten `lg:col-span-8` (4 seksi kartu berurutan: header seksi = ikon nomor lingkulan brand + judul; field = ui-input/ui-select 2 kolom; seksi dokumen = 5 slot kartu dashed `ring-1 ring-dashed` dengan `input[type=file][data-jenis={jenis}][form=form-dok-{jenis}]` + `span#slot-{jenis}` + `div#err-{jenis}`) + footer 2 tombol; sidebar `lg:col-span-4` `sticky top-24` (kartu `Kemajuan`: 4 baris `#ck-data/#ck-ortu/#ck-jurusan/#ck-dok` dengan badge `selesai`/`belum` + teks `Dokumen N/5` di `#dok-count`; kartu info kecil). **Semua id & name & struktur form sama persis** dengan view lama (form-pendaftaran, form-dok-*, form-kirim, btn-kirim, slot-*, err-*, ck-*, dok-count).

- [ ] **Step 3: Pindah JS lama apa adanya** (script blok terakhir view lama → view baru): handler change per slot (FormData + fetch + header XHR + update badge/err/counter), `updateChecklist()`, load-state dari badge `bg-success` → ganti deteksi: badge dengan teks `✓ Tersimpan` dihitung sudah (sesuaikan 1 baris ini), handler `#btn-kirim` (alert draf / confirm dokumen kurang / confirm kirim → submit form-kirim).

- [ ] **Step 4: Smoke browser (wajib — E2E singkat)** via browser tool: login budi (data E2E masih ada; bila tak ada, register user baru `uji.ui@mail.com` NISN 1111222233), ke `/pendaftaran`: isi field, upload 1 file ke slot ktp → badge `✓ Tersimpan` muncul TANPA reload; counter `Dokumen 1/5`; `Simpan Draf` → flash `Draf tersimpan`.

- [ ] **Step 5: Suite** — `php artisan test` → 79 passed.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat(ui): form pendaftaran 1 halaman + progres + upload fetch (tailwind)"
```

---

### Task 4: Layout admin + dashboard + jurusan

**Files:**
- Rewrite: `resources/views/layouts/admin.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/admin/jurusan/index.blade.php`, `resources/views/admin/jurusan/form.blade.php`

**Interfaces:**
- String wajib: `Dashboard PSB 2026/2027`, `Dashboard`, `Pendaftar`, `Jurusan & Kuota`, `Verifikasi`, `Nilai & Ranking`, `Pengumuman`, `Jadwal PSB`, `Laporan`, `Audit Log`, `Logout`, `Data`/`Proses`/`Sistem` (label grup), `Lihat Pendaftar`, `Proses Verifikasi`, `Input Nilai`, `Kuota per Jurusan`, `Pengumuman: Terbit`, `Pengumuman: Belum`, `Tambah Jurusan`, `Simpan`, `Batal`, nama 7 jurusan.
- Sidebar: link href PERSIS path lama (`/admin`, `/admin/pendaftar`, dst.); state aktif via `request()->routeIs(...)`.

- [ ] **Step 1: Rewrite `layouts/admin.blade.php`** — `@vite` + font + `<meta name="csrf-token" content="{{ csrf_token() }}">` (dipakai fetch nilai, Task 6). Flex: `aside` fixed `w-64 bg-slate-900` (logo+`PSB {sekolah}` putih; nav: Dashboard; label `Data`/`Proses`/`Sistem` (`text-[11px] uppercase tracking-wider text-slate-500`); item: ikon + label, aktif = `bg-slate-800 text-white`, nonaktif `text-slate-300 hover:bg-slate-800/50`; bawah: avatar inisial + nama + `Logout` (form POST)). `main` `lg:pl-64`: topbar sticky putih (judul `@yield('title')` + nama admin), konten `bg-slate-50 min-h-screen p-4 lg:p-6` + flash ui-alert. Mobile: tombol hamburger topbar → aside `translate-x` toggle (vanilla JS 10 baris) + backdrop. `@vite` + font.

- [ ] **Step 2: Rewrite `admin/dashboard.blade.php`** — H1 `Dashboard PSB {TA}`; 4 kartu pipeline (`grid sm:grid-cols-2 xl:grid-cols-4`: ikon gradiasi, label, angka besar, sub, tombol outline kecil dengan href lama) — data dari `$pipeline` (controller tak berubah); badge `Pengumuman: Terbit|Belum` di kartu Hasil; grid bawah: kartu grafik (`canvas#chartPendaftar` + JS fetch `route('admin.dashboard.chart')` Chart.js line — file Chart.js dari vendor path LAMA, warna `#4F46E5`) & kartu `Kuota per Jurusan` (baris: kode+nama, `terisi/kuota`, progress bar emerald, `Sisa: N`).

- [ ] **Step 3: Rewrite `admin/jurusan/index.blade.php` + `form.blade.php`** — index: H1 `Jurusan & Kuota` + (primary sm) `Tambah Jurusan` (`route('admin.jurusan.create')`); kartu tabel (kode badge brand, nama, rombel, kapasitas, kuota, cadangan, terisi, status badge, aksi edit (ikon)/hapus (ikon, form DELETE + confirm)); form: kartu (`route('admin.jurusan.update'/'store')`, `@method('PUT')` bila edit), field kode/nama/deskripsi/rombel/kuota/cadangan/aktif (checkbox), `Simpan` + `Batal`.

- [ ] **Step 4: Smoke + suite**

Run: `php artisan test` → 79 passed (LaporanTest dashboard, JurusanKuotaTest).
Run (admin login via curl cookie jar opsional) ATAU browser tool: `/admin` 200 + `Dashboard PSB 2026/2027` tampil; resize 390px → hamburger muncul & sidebar buka.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(ui): layout admin + dashboard pipeline + jurusan (tailwind)"
```

---

### Task 5: Admin pendaftar (index/detail/form manual) + verifikasi

**Files:**
- Rewrite: `resources/views/admin/pendaftar/{index,detail,form}.blade.php`, `resources/views/admin/verifikasi/index.blade.php`

**Interfaces:**
- String wajib: `Data Pendaftar`, `Input Manual`, `Semua status`, `Semua jurusan`, `Cari nama/NISN/no. pendaftaran`, `Input Pendaftar Manual`, `Simpan Pendaftar`, `Kembali`, `Pendaftar manual tersimpan`, `Verifikasi Dokumen`, `Luluskan Terpilih`, `Gugurkan Terpilih…`, `Bersihkan`, `Alasan (wajib)`, `Ya, gugur`, `Lulus`, `Gugur`, `Tidak ada pendaftar dalam antrian verifikasi`, `0 dipilih`→`N dipilih`.
- Id/struktur wajib (JS lama): `baris-batch`, `form-batch`, `aksi-batch`, `grup-gugur`, `btn-batch-lulus`, `btn-batch-gugur-toggle`, `btn-bersihkan`, `cb-pilih`, `btn-isi-alasan`, `alasan-inline`, `jumlah-pilihan`; form individual `aksi=lulus` & `aksi=gugur` per baris; `tr[data-baris]`.

- [ ] **Step 1: Rewrite `admin/pendaftar/index.blade.php`** — header H1 + (primary sm) `Input Manual`; kartu filter (ui-select status `Semua status`, ui-select jurusan `Semua jurusan`, input `q`, tombol `Filter`); kartu tabel (inisial avatar rounded, nama + nomor mono kecil, NISN, P1/P2 badge slate, 5 titik dokumen (span rounded-full w-2 h-2, emerald/slate), sumber, `x-status-badge`, aksi ikon detail); paginasi (link `rounded-lg ring-1 px-3 py-1.5`, aktif `bg-brand-600 text-white`). Query string filter PERSIS (`?status=&jurusan=&q=`).

- [ ] **Step 2: Rewrite `admin/pendaftar/detail.blade.php`** — header (avatar inisial besar, nama, nomor mono, `x-status-badge` + badge sumber); kartu bertumpuk: `Data` (dl grid 2 kolom), `Kelengkapan Dokumen` (baris + link preview `/admin/pendaftar/{id}/dokumen/{jenis}`), `Nilai` (3 nilai + total besar; bila belum ada → teks), `Riwayat` (timeline: dot + aksi + waktu + aktor + detail mono). String `Kelengkapan Dokumen`, `Nilai`, `Riwayat` wajib.

- [ ] **Step 3: Rewrite `admin/pendaftar/form.blade.php`** — H1 `Input Pendaftar Manual` + (ghost) `Kembali`; kartu form grid 2 kolom (semua name field PERSIS lama: nisn, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, asal_sekolah, alamat, nik, nama_orang_tua, pekerjaan_orang_tua, telepon_orang_tua, pilihan_1, pilihan_2); kartu `Dokumen (opsional — PDF/JPG/PNG maks 2 MB)` (5 file `name="dokumen_{jenis}"`); footer (primary) `Simpan Pendaftar`. `enctype="multipart/form-data"` wajib.

- [ ] **Step 4: Rewrite `admin/verifikasi/index.blade.php`** — H1 `Verifikasi Dokumen`; kartu tabel (checkbox `cb-pilih` + `#cek-semua`, nomor mono, nama, NISN, jurusan, titik dokumen, keputusan: form `aksi=lulus` (primary sm `Lulus` + confirm) + (danger outline sm) `Gugur` (btn-isi-alasan) + `div.alasan-inline` (form `aksi=gugur`, input `Alasan (wajib)` required, (danger) `Ya, gugur`)); batch bar: `div#baris-batch` (d-none) berisi `form#form-batch` (hidden `aksi-batch`, `span#jumlah-pilihan`, (primary) `Luluskan Terpilih` (#btn-batch-lulus), (danger) `Gugurkan Terpilih…` (#btn-batch-gugur-toggle), (ghost) `Bersihkan` (#btn-bersihkan), `div#grup-gugur` (d-none: input alasan + (danger) submit)); empty state `Tidak ada pendaftar dalam antrian verifikasi`; **JS lama dipindah apa adanya** (fungsi hitung/isiIds/toggle/individual).

- [ ] **Step 5: Smoke + suite**

Run: `php artisan test` → 79 passed (PendaftarAdminTest, VerifikasiTest).
Browser: `/admin/verifikasi` — centang 1 baris → batch bar muncul `1 dipilih`; klik `Gugur` individual → baris perluas + input alasan.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat(ui): admin pendaftar + verifikasi massal (tailwind)"
```

---

### Task 6: Admin nilai/ranking + pengumuman + jadwal + laporan + audit + final

**Files:**
- Rewrite: `resources/views/admin/{nilai/index,pengumuman,jadwal/index,laporan,audit}.blade.php`
- Modify (bila perlu): `resources/js/app.js` (tetap placeholder)

**Interfaces:**
- String wajib: `Nilai & Ranking`, `Proses Ranking`, `Hasil Ranking`, `belum resmi`, `resmi & terkunci`, `Input Nilai (pendaftar lulus verifikasi)`, `Simpan Semua`, `Batal`, `Belum ada pendaftar lulus verifikasi`, `Ranking selesai:`, `Pengumuman PSB`, `Pengumuman TERBIT`, `Terbitkan Pengumuman`, `Pengumuman PSB diterbitkan`, `Rekap per Jurusan`, `DITERIMA`, `CADANGAN`, `TIDAK DITERIMA`, `Jadwal PSB 2026/2027`, `Simpan Jadwal`, `Jadwal PSB disimpan.`, `Laporan`, `Ekspor CSV`, `Ekspor Excel`, `Audit Log`, nama 5 tahap.
- Id/struktur wajib (JS nilai): `btn-simpan-semua`, `btn-batal`, `err-batch`, `.in-nilai[data-kolom][data-asli]`, `.total-nilai`, `tr[data-baris]`; tombol `Proses Ranking` di form POST `/admin/ranking/proses` + confirm + `@disabled($sudahTerbit)`; form nilai HAPUS (pakai fetch JSON ke `/admin/nilai/batch`).

- [ ] **Step 1: Rewrite `admin/nilai/index.blade.php`** — header H1 + (primary) `Proses Ranking` (form POST + confirm); kartu `Hasil Ranking` (bila `$hasil->isNotEmpty()`: alert info `belum resmi` / emerald `resmi & terkunci`, tabel peringkat/nama/nomor/jurusan/total/status (badge)/via); kartu `Input Nilai (pendaftar lulus verifikasi)` + toolbar kanan (`btn-simpan-semua`, `btn-batal`, `span#err-batch`); tabel: nomor mono, nama, jurusan, 3 `input.in-nilai` (`data-kolom`, `data-asli`, `disabled` bila `$sudahTerbit`), `td.total-nilai`; empty `Belum ada pendaftar lulus verifikasi`; **JS lama dipindah** (hitungTotal bobot `config('psb.bobot')`, tandai dirty `ring-2 ring-amber-400`, Enter pindah sel, Simpan Semua → fetch POST JSON `{rows}` header XHR + CSRF dari `meta[name=csrf-token]` (layout admin WAJIB punya meta ini — tambahkan di Task 4 bila belum), error per baris ke `#err-batch`).

- [ ] **Step 2: Rewrite `admin/pengumuman.blade.php`** — banner (emerald: `Pengumuman TERBIT pada {waktu}. Nilai dan ranking terkunci — calon siswa sudah dapat melihat hasil.` / amber: belum terbit + tombol), 3 kartu angka (`DITERIMA`/`CADANGAN`/`TIDAK DITERIMA`), tabel `Rekap per Jurusan` (kuota/terisi/cadangan), (primary lg) `Terbitkan Pengumuman` (form POST + confirm; `@if(!$terbit)`).

- [ ] **Step 3: Rewrite `admin/jadwal/index.blade.php`** — H1 `Jadwal PSB {TA}`; 5 kartu tahap (ikon, label, 2 `input[type=datetime-local]` `name="mulai[{tahap}]"`/`name="selesai[{tahap}]"` value `old(..., $row?->...)` — logika blade lama PERSIS); (primary) `Simpan Jadwal` (form POST `route('admin.jadwal.update')` membungkus semua kartu).

- [ ] **Step 4: Rewrite `admin/laporan.blade.php`** — H1 `Laporan` + toolbar (select status + (secondary) `Ekspor CSV` link `/admin/laporan/export/csv?status={s}` + (success) `Ekspor Excel` link xlsx); 4 kartu angka; tabel `Rekap per Jurusan` (pendaftar/terima/cadangan/kuota/sisa + progress); tabel daftar pendaftar (filter status sama dengan query lama).

- [ ] **Step 5: Rewrite `admin/audit.blade.php`** — H1 `Audit Log`; kartu tabel (waktu mono, `x-badge` aksi, target mono, aktor, detail mono kecil, paginasi).

- [ ] **Step 6: Final verification (WAJIB semua)**

Run: `php artisan test` → Expected: **79 passed**.
Run: `./vendor/bin/pint` lalu `./vendor/bin/pint --test` → passed.
Run: `npm run build` lalu `git status` (jika ada perubahan build → commit).
Browser smoke E2E singkat (data E2E ada; bila DB sudah di-reset, jalankan ulang alur cepat: register 1 siswa + upload 5 + submit → admin: jadwal buka → verifikasi lulus → nilai 1 baris → simpan → ranking → publish → cek status siswa + cetak surat; admin: laporan + ekspor CSV; audit log). Semua halaman admin 200 & string kunci tampil.

- [ ] **Step 7: Commit final**

```bash
git add -A && git commit -m "feat(ui): admin nilai/pengumuman/jadwal/laporan/audit (tailwind) — rebuild modern selesai"
```

---

## Catatan Eksekusi

- Urutan task penting: Task 0 (build) SEBELUM Task 1 (`@vite` butuh manifest); Task 4 (layout admin + meta csrf-token) SEBELUM Task 6 (fetch nilai butuh token).
- Prinsip rewrite view: **struktur form/id/name/logika blade lama = acuan**; yang diganti hanya markup styling + class Tailwind. Bila ragu, salin blok lama dan ganti class.
- Jangan sentuh `app/`, `routes/`, `tests/` (kecuali string test yang HARUS ikut — hindari).
- File aset lama (`public/Assets/Dashboard/*`) tidak dihapus; hanya tak direferensikan lagi (Chart.js file-nya Pengecualian: tetap dipakai).
- Bila `npm run build` lambat di mesin user, build di-commit sehingga tidak perlu rebuild.
