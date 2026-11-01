# Desain: Rebuild UI Modern (Tailwind) — Sistem PSB SMKN 2 Singosari

> Per 2026-11-01. Menggantikan tampilan Bootstrap 5 + SB Admin 2 dengan design system
> Tailwind CSS, **tanpa mengubah satu pun fitur/rute/ perilaku** (79 test feature tetap hijau).

## 1. Niat & Tujuan

- Tampilan **portfolio-grade modern**, konsisten dari landing page → portal siswa → admin.
- Identitas sekolah tetap: logo asli, nuansa biru/indigo.
- Semua fitur, rute, validasi, dan flash message **tidak berubah** — test suite adalah pengaman.

## 2. Non-Goals (v1)

- Dark mode, i18n, PWA, animasi berat.
- Modal custom (tetap pakai `window.confirm` untuk konfirmasi ranking/publish/kirim).
- Ubah logika apa pun di controller/service (hanya view + asset + config build).

## 3. Keputusan Teknis

| Item | Keputusan |
|---|---|
| Styling | **Tailwind CSS 3** + `@tailwindcss/forms` via Vite (sudah ada `vite.config.js` + laravel-vite-plugin) |
| Bootstrap / SB Admin 2 | **Tidak lagi direferensikan** (file aset lama dibiarkan, tak dipakai) |
| Font | **Plus Jakarta Sans** (Google Fonts CDN) — heading `font-semibold tracking-tight`, body slate-600 |
| Ikon | **Heroicons outline** sebagai Blade component inline-SVG di `resources/views/components/icons/` (tanpa dependensi) |
| Chart | Tetap Chart.js — file vendor existing (`public/Assets/Dashboard/vendor/chart.js/Chart.min.js`) dipertahankan, warna disesuaikan |
| Build | `npm install` → `npm run build`; **hasil `public/build` di-commit** agar jalan tanpa `npm run dev`; view memakai `@vite(['resources/css/app.css', 'resources/js/app.js'])` |
| JS | Vanilla (tanpa Alpine/jQuery): fetch upload dokumen, checklist, nilai live, batch bar, sidebar mobile |

### 3.1 Design token (tailwind.config.js)

```
colors.brand = { 50..900 }  → skala indigo (600 = #4F46E5, 500 = #6366F1)
colors.accent = sky (500 = #0EA5E9)
fontFamily.sans = ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif']
```

Palet status (dipakai di `x-status-badge` & semua tabel):
`draf=slate, menunggu_verifikasi=amber, lulus_verifikasi=sky, gugur=rose, diterima=emerald, cadangan=amber (outline), tidak_diterima=slate`.

### 3.2 Komponen baku (ditulis sebagai Blade component di `components/ui/`)

- `ui-button` (variant: primary `bg-brand-600 hover:bg-brand-700`, secondary, ghost, danger; rounded-lg)
- `ui-card` (bg-white rounded-2xl shadow-sm ring-1 ring-slate-100)
- `ui-input` / `ui-select` / `ui-label` (forms plugin; focus ring-brand-500)
- `ui-badge-status` (ganti `x-status-badge` — **label teks tetap persis** lama)
- `ui-alert` (success emerald / error rose / info sky)
- `ui-table` wrapper (kartu, header `text-xs uppercase tracking-wide text-slate-500`, row hover)
- `ui-empty` (empty state)

## 4. Wireframe per Halaman

### 4.1 Landing (public/home) — `public/home.blade.php`
```
[navbar putih sticky: logo+nama | Beranda Jadwal FAQ | Login (ghost) [Daftar (primary)]]
[hero: gradient brand-600→sky-500, pattern halus
  badge "TA 2026/2027" • H1 "Penerimaan Siswa Baru 2026/2027"
  sub: SMK Negeri 2 Singosari — Akreditasi A • 7 Program Keahlian
  [Daftar Sekarang (putih)] [Cek Status (outline)]
  strip stats: 7 Jurusan | 386 Kuota | 5 Tahap]
[Kartu "Tahap aktif: Pendaftaran" (emerald) / "Tidak ada tahap aktif" (slate)]
[Program Keahlian: grid 3-4 kolom — kartu ikon, kode badge, nama, deskripsi,
 progress bar kuota (terisi/kuota)]
[Alur Pendaftaran: 4 langkah horizontal (nomor lingkulan gradasi)]
[Kalender PSB: 5 kartu tahap (nama, tanggal, badge Berlangsung/Selesai)]
[FAQ: accordion 4 item]
[CTA banner gradient + footer gelap slate-900]
```
String wajib: `Penerimaan Siswa Baru 2026/2027`, `Daftar Sekarang`, `Cek Status`,
`Program Keahlian` (boleh ganti "7 Program Keahlian" tetap tampil), `Kalender PSB`, `FAQ`,
nama 7 jurusan, `Sisa`/progress kuota.

### 4.2 Cek Status Publik — `public/cek-status.blade.php`
```
[navbar sama]
[kartu tengah (max-w-xl): "Cek Status Pendaftaran", input Nomor + Tgl Lahir, tombol Cek]
[kartu hasil: nama, nomor, jurusan pilihan (badge), status badge, alasan (rose) bila ada,
 hasil seleksi bila terbit]
```
String wajib: `Cek Status Pendaftaran`, `Pendaftaran tidak ditemukan`, `Hasil Seleksi`, label status.

### 4.3 Login / Register — `login.blade.php`, `auth/register.blade.php`
```
[latar gradient tipis slate-50→brand-50; kartu tengah max-w-md, logo atas]
[Login: H1 "Masuk", sub, input Email/Password, checkbox "Ingat saya", [Masuk],
 link "Belum punya akun? Daftar sekarang", alert error/success]
[Register: H1 "Daftar Akun", input Nama/Email/NISN/Password/Confirm, [Daftar],
 link "Sudah punya akun? Login"]
```
String wajib: `Masuk`, `Daftar`, `Email atau password salah`, `Terlalu banyak percobaan`,
`Selamat datang,`, `NISN`, `Pendaftaran`, `Formulir Pendaftaran` (post-register).

### 4.4 Layout Portal Siswa — `layouts/portal.blade.php`
```
[navbar putih sticky ring-1: logo | Status Pendaftaran [Pendaftaran] | avatar+nama, Logout]
[container max-w-6xl, bg slate-50 min-h]
[alert flash di atas konten]
```
String wajib: `Status`, `Pendaftaran`, `Logout`.

### 4.5 Form Pendaftaran — `portal/pendaftaran/index.blade.php`
```
[grid: konten (lg:col-span-8) + sidebar progres (lg:col-span-4, sticky)]
[Kartu "Formulir Pendaftaran" + badge nomor bila ada]
  [1. Data Diri — kartu, header nomor lingkul brand, grid 2 kolom input]
  [2. Orang Tua / Wali — 3 input]
  [3. Pilihan Jurusan — 2 select (P1 wajib, P2 opsional) + info sisa kuota]
  [4. Dokumen — 5 slot kartu: dashed ring, label, file input,
     badge ✓ Tersimpan (emerald)/Belum (slate)/mengunggah…(amber)/Gagal(rose) + error inline]
  [footer: (ghost) Simpan Draf  (primary) Kirim Pendaftaran]
[Sidebar: kartu "Kemajuan" — 4 baris: ikon centang/lingkaran, label,
 "Data Diri/Orang Tua/Pilihan Jurusan/Dokumen x/5"; kartu info "Setelah dikirim…"]
```
String wajib: `Formulir Pendaftaran`, `Data Diri`, `Orang Tua`, `Pilihan Jurusan`,
`Dokumen`, `✓ Tersimpan`, `Belum`, `Dokumen 5/5` (format `Dokumen N/5`), `Simpan Draf`,
`Kirim Pendaftaran`, `Draf tersimpan`, `Pendaftaran terkirim`, `Selamat datang,`.

### 4.6 Status Siswa — `portal/status/index.blade.php`
```
[Kartu hero: "Halo, {nama}" + nomor pendaftaran besar (font-mono) + badge status besar
 + alasan (alert rose) + (ghost) Kirim Ulang Pendaftaran bila boleh revisi]
[grid 2 kolom: kartu Kelengkapan Dokumen (5 baris badge) | kartu Info (NISN, asal sekolah, dikirim)]
[Kartu Hasil (bila terbit): status akhir besar emerald/rose, jurusan + peringkat,
 (primary) Cetak Surat Keterangan Diterima]
[atau kartu "Menunggu pengumuman…" bila lulus & belum terbit]
[atau empty state "Belum Ada Pendaftaran" + (primary) Mulai Pendaftaran]
```
String wajib: `Menunggu Verifikasi`, `Gugur`, `Diterima`, `Cadangan`, `Tidak Diterima`,
`Cetak Surat Keterangan Diterima`, `Kirim Ulang Pendaftaran`, `dikirim ulang`,
`Belum Ada Pendaftaran`, `Mulai Pendaftaran`, `Menunggu pengumuman`.

### 4.7 Surat — `portal/surat.blade.php`
Tetap A4 print-oriented; styling pakai Tailwind utility + `@media print`;
String wajib: `SURAT KETERANGAN DITERIMA`, `Cetak / Simpan PDF`, nama sekolah, NIP kasek.

### 4.8 Layout Admin — `layouts/admin.blade.php`
```
[sidebar fixed w-64 bg-slate-900: logo+PSB (putih), nav grup:
  (tanpa label) [Dashboard]
  DATA: Pendaftar, Jurusan & Kuota
  PROSES: Verifikasi, Nilai & Ranking, Pengumuman
  SISTEM: Jadwal PSB, Laporan, Audit Log
  (label grup: text-[11px] uppercase tracking-wider text-slate-500)
  bawah: avatar + nama + (Logout)]
[main: ml-64, topbar sticky putih (judul halaman + nama admin), konten bg-slate-50 p-6]
[sidebar mobile: off-canvas + hamburger (tombol di topbar)]
```
String wajib: `Dashboard PSB 2026/2027`, `Pendaftar`, `Jurusan & Kuota`, `Verifikasi`,
`Nilai & Ranking`, `Pengumuman`, `Jadwal PSB`, `Laporan`, `Audit Log`, `Logout`,
grup `DATA`/`PROSES`/`SISTEM` (boleh uppercase via CSS — teks sumber tetap `Data/Proses/Sistem`).

### 4.9 Dashboard Admin — `admin/dashboard.blade.php`
```
[4 kartu pipeline (ikon gradiasi, label, angka besar, sub-angka, tombol outline):
 Pendaftaran (total + draf) → [Lihat Pendaftar]
 Verifikasi (menunggu — merah bila >0) → [Proses Verifikasi]
 Nilai (belum dinilai) → [Input Nilai]
 Hasil (diterima/cadangan/ttd + badge "Pengumuman: Terbit|Belum") → [Pengumuman]]
[grid: kartu grafik (Chart.js line, warna brand) | kartu kuota per jurusan (progress bar)]
```
String wajib: `Pendaftaran`, `Verifikasi`, `Nilai`, `Hasil`, `Pengumuman: Terbit`,
`Pengumuman: Belum`, `Lihat Pendaftar`, `Proses Verifikasi`, `Input Nilai`, `Kuota per Jurusan`.

### 4.10 Data Pendaftar — `admin/pendaftar/index.blade.php`
```
[header: H1 "Data Pendaftar" + (primary sm) Input Manual]
[kartu filter: select status, select jurusan, input cari, (secondary) Filter]
[kartu tabel: avatar inisial, nama(+nomor mono kecil), NISN, P1/P2 badge slate,
 dokumen (5 titik kecil berwarna), sumber, status badge, aksi (ikon detail)]
[paginasi (link ring-1 rounded)]
```
String wajib: `Data Pendaftar`, `Input Manual`, `Semua status`, `Semua jurusan`.

### 4.11 Detail Pendaftar — `admin/pendaftar/detail.blade.php`
Header (avatar inisial besar, nama, nomor, badge status + sumber) + kartu bertumpuk:
Data (grid definisi), Dokumen (baris + preview), Nilai (3 nilai + total), Riwayat (timeline audit).
String wajib: `Kelengkapan Dokumen`, `Nilai`, `Riwayat`.

### 4.12 Form Input Manual — `admin/pendaftar/form.blade.php`
Kartu form 2 kolom (grid), NISN+nama, data diri, ortu, pilihan 1/2, kartu Dokumen (5 file),
(ghost) Kembali + (primary) Simpan Pendaftar.
String wajib: `Input Pendaftar Manual`, `NISN`, `Simpan Pendaftar`, `Kembali`,
`Pendaftar manual tersimpan`.

### 4.13 Verifikasi — `admin/verifikasi/index.blade.php`
```
[kartu tabel: checkbox + nomor + nama + NISN + jurusan + dokumen titik + keputusan]
  keputusan: (primary sm) Lulus | (danger outline sm) Gugur
  → klik Gugur: baris perluas → input "Alasan (wajib)" + (danger) Ya, gugur
[batch bar sticky bawah (muncul bila ada centang): "N dipilih"
 (primary) Luluskan Terpilih | (danger) Gugurkan Terpilih… (+ input alasan muncul) | (ghost) Bersihkan]
[empty state: "Tidak ada pendaftar dalam antrian verifikasi"]
```
String wajib: `Verifikasi Dokumen`, `Luluskan Terpilih`, `Gugurkan Terpilih`, `Bersihkan`,
`Alasan (wajib)`, `Ya, gugur`, `1 pendaftar diproses`, `digugurkan`, `lulus verifikasi`,
`Tidak ada pendaftar dalam antrian`.

### 4.14 Nilai & Ranking — `admin/nilai/index.blade.php`
```
[header: H1 "Nilai & Ranking" + (primary) Proses Ranking (disabled bila terbit)]
[kartu "Hasil Ranking" (bila ada hasil): alert "belum resmi"/"resmi & terkunci",
 tabel peringkat/nama/nomor/jurusan/total/status/via]
[kartu "Input Nilai (pendaftar lulus verifikasi)" + legenda bobot
 + toolbar sticky kanan: (primary) Simpan Semua | (ghost) Batal | error rose]
 [tabel: nomor mono, nama, jurusan, 3 input sel (ring amber bila dirty), total live (mono)]
```
String wajib: `Nilai & Ranking`, `Proses Ranking`, `Hasil Ranking`, `belum resmi`,
`resmi & terkunci`, `Input Nilai`, `Simpan Semua`, `Batal`, `Ranking selesai:`,
`Belum ada pendaftar lulus verifikasi`.

### 4.15 Jurusan & Kuota — `admin/jurusan/index.blade.php` + form
Tabel (kode badge, nama, rombel, kapasitas, kuota, cadangan, terisi, status badge, aksi edit/hapus)
+ (primary) Tambah Jurusan; form = kartu dialog-inline (bukan modal): input + simpan/batal.
String wajib: `Jurusan & Kuota`, nama 7 jurusan, `Tambah Jurusan`.

### 4.16 Jadwal — `admin/jadwal/index.blade.php`
H1 "Jadwal PSB 2026/2027" + 5 kartu tahap (ikon, label, 2 datetime input) + (primary) Simpan Jadwal.
String wajib: `Jadwal PSB`, `Jadwal PSB disimpan.`, nama 5 tahap.

### 4.17 Pengumuman — `admin/pengumuman/index.blade.php`
Banner status (emerald "Pengumuman TERBIT pada …" / amber "belum terbit"),
3 kartu angka (Diterima/Cadangan/Tidak Diterima — label `DITERIMA` dll boleh),
tabel Rekap per Jurusan, (primary besar) Terbitkan Pengumuman (hidden bila terbit).
String wajib: `Pengumuman PSB`, `Pengumuman TERBIT`, `Terbitkan Pengumuman`,
`Pengumuman PSB diterbitkan`, `Rekap per Jurusan`, `DITERIMA`, `CADANGAN`, `TIDAK DITERIMA`.

### 4.18 Laporan — `admin/laporan/index.blade.php`
4 kartu angka + toolbar filter+ekspor ((secondary) Ekspor CSV | (success) Ekspor Excel)
+ tabel Rekap per Jurusan + tabel daftar pendaftar (filter status).
String wajib: `Laporan`, `Rekap per Jurusan`, `Ekspor CSV`, `Ekspor Excel`.

### 4.19 Audit Log — `admin/audit/index.blade.php`
Kartu tabel: waktu (mono), aksi badge, target, aktor, detail (mono kecil).
String wajib: `Audit Log`.

## 5. Konstrain Test (WAJIB — string yang tidak boleh berubah dari UI)

Semua string yang di-assert test feature (79 test) harus tetap tampil persis, antara lain:
- Publik: `Penerimaan Siswa Baru 2026/2027`, `Cek Status Pendaftaran`, `Pendaftaran tidak ditemukan`.
- Auth: `Masuk`, `Daftar`, `Email atau password salah`, `Selamat datang,`.
- Siswa: `Formulir Pendaftaran`, `Data Diri`, `Pilihan Jurusan`, `✓ Tersimpan`, `Dokumen 5/5`
  (pola `Dokumen N/5`), `Simpan Draf`, `Kirim Pendaftaran`, `Draf tersimpan`,
  `Pendaftaran terkirim`, `Menunggu Verifikasi`, `Lulus Verifikasi`, `Gugur`, `Diterima`,
  `Cadangan`, `Tidak Diterima`, `Cetak Surat Keterangan Diterima`, `Kirim Ulang Pendaftaran`,
  `dikirim ulang`, `Belum Ada Pendaftaran`, `Mulai Pendaftaran`, `Menunggu pengumuman`,
  `SURAT KETERANGAN DITERIMA`, `Cetak / Simpan PDF`.
- Admin: `Dashboard PSB 2026/2027`, `Data Pendaftar`, `Input Pendaftar Manual`, `Input Manual`,
  `Verifikasi Dokumen`, `Luluskan Terpilih`, `Gugurkan Terpilih`, `Bersihkan`, `Alasan (wajib)`,
  `Ya, gugur`, `1 pendaftar diproses`, `digugurkan`, `lulus verifikasi`,
  `Tidak ada pendaftar dalam antrian`, `Nilai & Ranking`, `Proses Ranking`, `Hasil Ranking`,
  `belum resmi`, `resmi & terkunci`, `Input Nilai`, `Simpan Semua`, `Batal`, `Ranking selesai:`,
  `Belum ada pendaftar lulus verifikasi`, `Jurusan & Kuota`, `Jadwal PSB`,
  `Jadwal PSB disimpan.`, `Terbitkan Pengumuman`, `Pengumuman TERBIT`,
  `Pengumuman PSB diterbitkan`, `Pengumuman: Terbit`, `Pengumuman: Belum`,
  `Rekap per Jurusan`, `Laporan`, `Audit Log`, `Lihat Pendaftar`, `Proses Verifikasi`,
  `Kuota per Jurusan`, `Pendaftar manual tersimpan`.
- Label `x-status-badge` (7 label status) tetap persis.

Verifikasi final: `php artisan test` 79/79 + `pint --test` + smoke browser per halaman.

## 6. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Regresi test karena string berubah | Daftar string §5 dipatuhi; test jalan per task |
| Vite build / `@vite` error saat `artisan serve` | `npm run build` + commit `public/build`; fallback: link CDN Tailwind Play hanya darurat dev |
| Chart.js tak cocok palet | Warna series diubah inline di dashboard |
| Sidebar admin mobile | Off-canvas sederhana (vanilla JS); desktop tetap fixed |
| Scope creep per halaman | Satu task per grup halaman; wireframe §4 adalah acuan final |

## 7. Pendekatan yang Dipertimbangkan (rekap)

- **A. Theme Bootstrap/SB Admin 2** — cepat & aman, tapi "bootstrap rapi", bukan rebuild modern.
- **B. Rebuild Tailwind penuh — DIPILIH** — design system sendiri, konsisten portfolio-grade, sesuai permintaan "pokok semuanya".
- **C. Hybrid (Tailwind portal, theme admin)** — hemat, tapi dua wajah antara portal & admin.
