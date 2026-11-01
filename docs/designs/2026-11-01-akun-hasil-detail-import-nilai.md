# Desain: Ubah Password, Hasil Seleksi Detail, Import Nilai CSV

Tanggal: 2026-11-01
Status: usulan — menunggu persetujuan
Prekursor: reset password (commit `0f8418a`, sudah jadi)

## Konteks

Tiga fitur penting yang masih hilang dari sistem PSB:

1. **Ubah password saat login** — saat ini calon siswa tidak bisa mengganti password
   (hanya lewat reset). Menyelesaikan alur autentikasi.
2. **Hasil seleksi detail** — kartu "Hasil Seleksi" di `/status` baru menunjukkan status
   + jurusan + peringkat. Skor per komponen, via P1/P2, dan kuota belum terlihat.
3. **Import nilai CSV** — admin input nilai manual per sel; ratusan pendaftar butuh
   import massal.

## Fitur 1 — Ubah Password (halaman Akun)

**Alur:** `GET /akun` (portal, middleware auth) → form; `POST /akun/password`.

- Halaman `/akun` menampilkan: nama, email, NISN (read-only, catatan "email tidak
  dapat diubah — hubungi admin") + form ubah password.
- Controller baru: `App\Http\Controllers\Portal\AkunController` (`index`, `updatePassword`).
- Validasi:
  - `password_lama` — required; dicek `Hash::check` → error "Password lama salah."
  - `password` — min 8, `confirmed`.
- Setelah sukses: **logout paksa** + invalidasi session → redirect ke login dengan
  flash "Password berhasil diubah. Silakan masuk kembali."
  - Alasan: best practice keamanan; setelah ganti password, user wajib autentikasi
    ulang dengan password baru (dapat dijelaskan di laporan TA).
- View: `resources/views/portal/akun/index.blade.php` (gaya Tailwind, konsisten
  halaman status).
- Nav portal: link "Akun" ditambahkan di `layouts/portal.blade.php` (sebelum Logout).
- Route name: `akun`, `akun.password`.
- Bisa dipakai admin juga (satu portal, role-agnostic).

**Tes (AkunTest):**
1. Guest di-redirect ke login dari `/akun`.
2. Login calon siswa → halaman terbuka, email terlihat.
3. Ganti dengan `password_lama` salah → error, password DB tidak berubah.
4. Ganti sukses → password lama gagal login, password baru berhasil, user ter-logout.
5. Password baru < 8 karakter / konfirmasi tidak sama → ditolak.

## Fitur 2 — Hasil Seleksi Detail (portal /status)

**Lingkup kecil, fokus pada kartu "Hasil Seleksi" yang sudah ada** di
`resources/views/portal/status/index.blade.php` (baris 105–127):

- Warna status: `diterima` = emerald, `cadangan` = amber, `tidak_diterima` = rose
  (saat ini cadangan ikut warna rose — menyesatkan, diperbaiki).
- Tambah rincian saat `$p->hasil` ada **dan** pengumuman sudah terbit:
  - Rincian skor dari `$p->nilai`: rapor, prestasi, kunjungan, total (bobot dari
    `config('psb.bobot')`).
  - Via pilihan (P1/P2) + kuota jurusan tujuan (mis. "Kuota TKJ: 28").
- Controller: eager-load `hasil.jurusan.kuota` + `nilai` di `StatusController@index`
  (sekarang lazy-load).
- **Privasi:** hanya data milik user yang login (hasOne per pendaftar). Halaman publik
  `/cek-status` **tetap** hanya menampilkan status, tanpa skor/peringkat.

**Tes (tambah ke file tes status/pengumuman yang sesuai):**
1. Terbit + diterima → peringkat, jurusan, rincian skor, total, via, kuota terlihat.
2. Terbit + cadangan → label "Cadangan" (amber) + peringkat.
3. Terbit + tidak_diterima → label "Tidak Diterima".
4. `hasil` ada tapi pengumuman belum terbit → detail TIDAK tampil.
5. Pendaftar tanpa `hasil` → tidak ada kartu hasil.

## Fitur 3 — Import Nilai CSV (admin)

**Format CSV (UTF-8, header wajib, desimal pakai titik):**

```
nisn,rapor,prestasi,kunjungan
1234567890,85.5,90,88
```

**Route (grup admin):**
- `GET /admin/nilai/import/template` → download CSV template (header + 1 baris contoh).
- `POST /admin/nilai/import` → upload file.

**UI:** kartu "Import Nilai (CSV)" baru di `admin/nilai/index.blade.php`:
file input + tombol "Unduh Template" + aturan (header, range 0–100, max 1000 baris,
all-or-nothing). Sesuai tema halaman (Tailwind di dalam layout admin).

**Parsing (controller):**
- File: required, max 2048 KB, tipe csv/txt.
- `fgetcsv`; baris pertama harus header `nisn,rapor,prestasi,kunjungan`
  (case-insensitive, trim). Baris kosong dilewati. Maksimal 1000 baris data.

**Bisnis (method baru `NilaiService::impor(array $baris, ?User $by)`:**
- Validasi per baris: NISN 10 digit; Pendaftar ada & status `LULUS_VERIFIKASI`;
  nilai numerik 0–100; NISN duplikat di dalam file = error.
- **All-or-nothing** (konsisten dengan `batch`): jika ada 1 baris error, TIDAK ada
  yang disimpan; kembalikan daftar error per baris.
- Semua valid → simpan lewat `simpan()` (total berbobot + riwayat + audit).
- Audit: `nilai_impor` (jumlah baris) — `simpan()` sudah mencatat per nilai.
- Guard: tahap `SELEKSI` harus dibuka (403 seperti `batch`); `PengumumanSudahTerbit`
  dilempar service.

**Tes (NilaiImportTest):**
1. Template CSV terunduh, header benar.
2. Import valid → nilai tersimpan, total sesuai bobot, status flash sukses.
3. NISN tidak ada → seluruh import ditolak, tidak ada yang tersimpan.
4. NISN bukan `LULUS_VERIFIKASI` → ditolak.
5. Nilai > 100 → ditolak.
6. NISN duplikat dalam file → ditolak.
7. Header salah → ditolak dengan pesan jelas.
8. Pengumuman sudah terbit → ditolak.
9. Audit `nilai_impor` tercatat.

## Di luar scope (disengaja)

- Notifikasi SMS/WhatsApp/email otomatis — butuh API berbayar, di luar scope TA.
- Import Excel (.xlsx) — perlu dependency; CSV bisa di-export langsung dari Excel.
- Export PDF — laporan sudah ada export CSV/Excel.
- Reset password untuk admin — admin di-reset via seeder/database (sudah cukup).

## Efek samping / risik

- Halaman `/akun` baru + route baru: tidak mengubah alur yang ada.
- Perubahan kartu hasil di `/status`: tampilan saja, data sudah tersedia di DB.
- Import: all-or-nothing berarti admin harus perbaiki file lalu upload ulang jika ada
  1 baris rusak — dipilih demi konsistensi dengan `batch` dan keamanan data.
- Dokumen `docs/user-requirements.md` & `docs/system-design.md` perlu disinkronkan
  (tugas akhir plan): tambah use case FR + diagram terkait.
