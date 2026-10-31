# Desain: Overhaul UX PSB SMKN 2 Singosari (Redesign "Enak Dipakai")

> Dok. desain per 2026-10-31. Mengamendemen `docs/user-requirements.md` dan
> `docs/system-design.md` pada bagian yang disebutkan di §5. Sistem saat ini
> sudah lengkap & teruji (58 test hijau) — redesign ini **memotong friksi,
> tidak menambah fitur baru**.

## 1. Niat & Konteks

Aplikasi PSB akan **benar-benar dipakai** (bukan sekadar demo). Prinsip:
**kurangi langkah & klik, pertahankan semua fitur inti** (kuota, ranking 2
pilihan, cadangan, surat, ekspor, audit). Tidak ada fitur baru: tanpa
SMS/WhatsApp, tanpa pembayaran, tanpa tryout online, tanpa role baru.

## 2. Tujuan / Non-tujuan / Anti-tujuan

**Tujuan**
- Siswa baru bisa mulai mendaftar dalam ≤ 2 klik setelah landing (daftar → langsung di form).
- Admin sanggup memproses 100+ pendaftar tanpa mengulang klik yang sama 100x (verifikasi massal, simpan nilai sekalian).
- Satu bahasa desain yang konsisten di portal publik, portal siswa, dan admin.

**Non-tujuan**
- Mengubah algoritma ranking/kuota (tetap §5.2 system-design, termasuk aturan yang diamendemen di sana).
- Mengubah model data, kecuali yang eksplisit di §4.5.
- Multi-device offline, PWA, i18n.

**Anti-tujuan (jangan lakukan)**
- Kilau demo (animasi berat, ilustrasi besar) yang memperlambat atau menumpulkan aksi utama.
- Menyembunyikan input di balik tab/dialog sehingga siswa tidak tahu apa yang kurang.

## 3. Konstrain

- Laravel 10 + Blade + Bootstrap 5 (portal) + SB Admin 2 (admin, aset sudah di `public/Assets/Dashboard`).
- Suite test existing harus tetap 100% hijau setelah redesign (test lama yang bertentangan dengan perilaku baru **di-amend**, bukan dihapus tanpa ganti — target akhir ± 60 test).
- UI Bahasa Indonesia. Warna primer sekolah `#103cbe`, logo `public/Assets/Img-Login/Logo-SMk.png`.
- Tanpa dependensi JS framework baru (vanilla JS + jQuery yang sudah ada di SB Admin 2).
- MySQL 8 produksi / SQLite in-memory test.

## 4. Keputusan Desain

### 4.1 Portal siswa — alur baru

| # | Perilaku lama | Perilaku baru |
|---|---------------|---------------|
| 1 | Daftar → verifikasi email (link, 7 hari, resend) → login | **Daftar → otomatis login → langsung di form pendaftaran** (`/pendaftaran`). Verifikasi email dihapus total. |
| 2 | Form 3 tab (Data / Orang Tua / Pilihan & Dokumen) | **Satu halaman, 4 seksi vertikal** (Data Diri, Orang Tua/Wali, Pilihan Jurusan, Dokumen) — semua terlihat, tidak ada tab. Di samping (desktop) / atas (mobile) ada **checklist kemajuan** 4 item dengan status ✓/belum. |
| 3 | Upload 5 file; tiap upload memicu reload halaman | 5 slot (satu per jenis dokumen, label + batas tipe/ukuran). Saat file dipilih → **upload via fetch, tanpa reload**; badge slot berubah ✓; error tampil inline di bawah slot. (Fallback: bila fetch gagal total, reload halaman dan error via session — perilaku lama.) |
| 4 | Setelah submit → /status | Sama, dan `/status` jadi "beranda" siswa: redirect after-login default `/status`, tautan "Dashboard Saya" di navbar publik. |
| 5 | Login & register: layout 2 box (biru/putih) | **Satu kartu terpusat**: logo, judul, form, tautan kecil "Belum punya akun? Daftar" / sebaliknya. Warna & logo sekolah dipertahankan. |
| 6 | Navbar portal siswa: Beranda + Logout | **Status + Pendaftaran + Logout** (Beranda publik tetap bisa diakses dari mana saja via logo). |

Detail implementasi:
- `User` tidak lagi `implements MustVerifyEmail`; middleware `verified` dihapus dari semua grup route; route `/verify-email/{token}`, `/verify-email` (notice), `/verify-email/resend` dan view `auth/verify` dihapus. Kolom `email_verified_at` **dibiarkan** (tidak di-migrate ulang) — tidak dipakai.
- `AuthController::register`: setelah `User::create` → `Auth::login($user)` → `redirect('/pendaftaran')`.
- Endpoint `POST /pendaftaran/dokumen` menjawab **JSON** `{ok, jenis, error?}` bila request membawa header `X-Requested-With: XMLHttpRequest`, selain itu tetap redirect+session (kompatibel).
- Checklist kemajuan dihitung client-side (field terisi + dokumen tersimpan); tombol **Kirim Pendaftaran** selalu bisa diklik — validasi tetap di server (perilaku & pesan error saat ini dipertahankan, hanya dipindah ke panel inline di halaman form).
- Halaman `/status` dipoles: kartu utama (nomor, status badge besar, alasan gugur bila ada), kartu kelengkapan dokumen, kartu hasil (tetap di-gate `sudahTerbit` seperti sekarang).

### 4.2 Admin — pipeline & batch

**Dashboard = pipeline tahap (pengganti 4 kartu statistik):**
Satu baris kartu horizontal 4 tahap, masing-masing: label tahap, angka kunci, tombol aksi ke halaman prosesnya:
1. **Pendaftaran** — total pendaftar (+draf) → tombol "Lihat Pendaftar".
2. **Verifikasi** — menunggu / lulus / gugur → tombol "Proses Verifikasi" (angka merah bila >0 menunggu).
3. **Nilai** — lulus-ternilai / lulus-belum → tombol "Input Nilai".
4. **Hasil** — diterima / cadangan / tidak diterima + badge "Pengumuman: terbit/belum" → tombol "Pengumuman".
Grafik 14-hari & tabel kuota dipertahankan di bawah pipeline (blok kecil).

**Verifikasi massal (`/admin/verifikasi`):**
- Kolom checkbox per baris + checkbox "pilih semua (halaman ini)".
- Bilah aksi muncul bila ada pilihan: `[Luluskan N]` `[Gugurkan N]` — gugur massal membuka textarea **satu alasan untuk semua** di bilah tersebut (bukan `prompt()`).
- Implementasi: `POST /admin/verifikasi/batch` body `{aksi: lulus|gugur, alasan?, ids: []}`; loop per pendaftar dengan logika `VerifikasiController::proses` yang ada; **audit tetap satu baris per pendaftar** (detail `{massal: true}`).
- Tombol Lulus/Gugur individual tetap ada; "Gugur" individual memakai input teks inline per baris (bukan `prompt()`).
- Guard tahap & aturan status tetap seperti sekarang.

**Input nilai ala spreadsheet (`/admin/nilai`):**
- Tabel satu baris per pendaftar lulus verifikasi (paginasi 50/halaman): No., Nama, Jurusan, [Rapor][Prestasi][Kunjungan] (input per sel, kecil), **Total** (dihitung live di client dengan bobot config 40/30/30, format 2 desimal).
- **Tidak ada tombol simpan per baris.** Navigasi keyboard: Enter/Tab pindah sel berikutnya. Sel yang berubah ditandai highlight.
- Satu tombol **"Simpan Semua"** (tombul kedua: "Batal" mereset input ke nilai tersimpan). Menyimpan via `POST /admin/nilai/batch` body `{rows: [{id, rapor, prestasi, kunjungan}]}` (hanya baris yang berubah); server memvalidasi 0–100 per nilai, loop `NilaiService::simpan` (riwayat + audit `nilai_ubah` per baris yang berubah — perilaku ada dipertahankan), respons JSON `{ok, tersimpan: n}` + flash.
- Guard tahap seleksi & status lulus verifikasi dipertahankan; setelah publish semua input `disabled`.
- Route lama `PUT /admin/nilai/{pendaftar}` **dihapus** (digantikan batch).
- Blok **Hasil Ranking** (hasil proses ranking) tetap ada di atas tabel input (perilaku task review I8 tidak berubah).

**Sidebar:** dikelompokkan 3 section (`.sidebar-heading` SB Admin 2):
- **Data**: Pendaftar, Jurusan & Kuota
- **Proses**: Verifikasi, Nilai & Ranking, Pengumuman
- **Sistem**: Jadwal PSB, Laporan, Audit Log

**Lainnya:**
- Halaman pengumuman, laporan, audit, jadwal, detail pendaftar: tidak berubah fungsi; penyesuaian visual minor bila diperlukan konsistensi.
- Input manual pendaftar: tetap halaman form (bukan modal).

### 4.3 Bahasa desain

- Portal (publik + siswa): Bootstrap 5 kustom, container max 960px, kartu `shadow-sm`, tombol/badge konsisten, font Nunito (Google Fonts, sama dengan SB Admin 2 agar serasi).
- Admin: SB Admin 2 (aset existing) — tidak diganti template.
- Status badge memakai satu pemetaan warna di seluruh aplikasi: draf=secondary, menunggu=warning, lulus=info, gugur=danger, diterima=success, cadangan=warning-outline, tidak_diterima=secondary. (Dibuat sebagai helper Blade `@statusBadge`.)

### 4.4 Dampak ke test (wajib)

- `AuthTest`: hapus test verifikasi-email; **tambah**: register → otomatis login + redirect `/pendaftaran`; login → redirect sesuai role (ada/tidak ada pendaftar → `/status`).
- `ReviewFixesTest`: hapus test C1 (notice/resend); sisanya tetap (I1–I9).
- `PendaftaranTest`: test upload dokumen memakai header XHR → assert JSON; perilaku validasi (ukuran/MIME/NISN/periode) tidak berubah.
- `VerifikasiTest`: **tambah** test batch: luluskan N → N baris lulus + N audit; gugurkan N dengan alasan → semua gugur dengan alasan sama + N audit `massal:true`.
- `NilaiTest`: ganti test PUT tunggal dengan test batch: 1 POST menyimpan 2 baris → 2 riwayat + 2 audit `nilai_ubah`; validasi >100 tetap ditolak (respons JSON error per baris).
- `StatusRevisiTest`, `RankingTest`, `LaporanTest`, `PengumumanSuratTest`, `JurusanKuotaTest`, `PendaftarAdminTest`: penyesuaian kecil sesuai redirect/login baru (mis. asumsi verified dihapus).
- Target akhir: semua hijau, ± 60 test, `php artisan test` + `pint --test` bersih.

### 4.5 Dampak ke data & dokumen TA

- **Tanpa migration baru.** `users.email_verified_at` dan `users.nisn` tetap; `email_verified_at` tak terpakai lagi (dicatat di komentar seeder).
- `docs/user-requirements.md` & `docs/system-design.md` **diperbarui** sebagai task pertama implementasi: UC-01 (tanpa verifikasi email), UC-03/04 (form 1 halaman, upload per slot), UC-09 (verifikasi massal), §nilai (input batch), alur bisnis, wireframe layar yang berubah, dan catatan "redesign" di bagian relevan.

## 5. Amende terhadap dokumen lama (ringkas)

| Dokumen lama | Bagian | Menjadi |
|---|---|---|
| user-requirements UC-01 | verifikasi email (alur 3–4, alt 3a/3b) | akun aktif langsung; tidak ada link verifikasi |
| user-requirements UC-03 | "3 langkah" form | 1 halaman 4 seksi + checklist |
| user-requirements UC-09 | verifikasi satu per satu | + verifikasi massal (alasan tunggal) |
| user-requirements FR-02 | "verifikasi email (tautan, expiry 7 hari)" | dihapus |
| system-design §6 (layar) | S-PUB-02, S-CS-01, S-AD-01/03/04 | sesuai §4.1 & §4.2 |
| system-design §7 (keamanan) | "verifikasi email" | dihapus; yang lain tetap |

## 6. Dibiarkan kepada builder

- Struktur Blade/JS eksak per halaman (fetch handler, dirty-tracking sel nilai, highlight, fokus keyboard).
- Ukuran paginasi admin (default 20/50) dan label copy minor.
- Rincian animasi transisi (paling ringan: fade/hide-show CSS).
- Penempatan tombol batch pada layout mobile (bilah tetap di atas tabel).

## 7. Pendekatan yang dipertimbangkan (rekap)

- **A. Overhaul UX penuh — DIPILIH**: portal + admin + bahasa desain disatukan; paling memenuhi "benar-benar dipakai"; admin adalah pengguna dengan klik terbanyak per hari.
- B. Potong friksi saja (tanpa rework tampilan) — lebih cepat, tapi siswa tetap mendapat pengalaman berlapis (tab + reload per file).
- C. Portal saja — tidak menyelesaikan friksi admin (verifikasi & nilai per-baris).
