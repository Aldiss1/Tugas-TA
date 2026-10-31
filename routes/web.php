<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DokumenController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\NilaiController;
use App\Http\Controllers\Admin\PendaftarController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\RankingController;
use App\Http\Controllers\Admin\VerifikasiController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\CekStatusController;
use App\Http\Controllers\Portal\PendaftaranController;
use App\Http\Controllers\Portal\StatusController;
use App\Http\Controllers\Portal\SuratController;
use App\Models\Jurusan;
use App\Services\Psb\JadwalService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Route utama. Grup:
| - publik (portal calon siswa & umum)
| - portal (calon siswa login)
| - /admin (role admin)
|
*/

Route::get('/', function () {
    $jadwal = app(JadwalService::class);

    return view('public.home', [
        'jurusan' => Jurusan::with('kuota')->where('aktif', true)->orderBy('nama')->get(),
        'statusJadwal' => $jadwal->statusSemua(),
        'tahapAktif' => $jadwal->tahapAktif(),
    ]);
})->name('home');

// --- Portal publik: cek status ----------------------------------------

Route::get('/cek-status', [CekStatusController::class, 'show'])->name('cek-status');
Route::post('/cek-status', [CekStatusController::class, 'cek']);

// --- Admin ----------------------------------------------------------------

Route::middleware(['auth', 'verified:verify-email.notice', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart', [DashboardController::class, 'chart'])->name('dashboard.chart');

    Route::resource('jurusan', JurusanController::class)->except('show');

    Route::get('/pendaftar', [PendaftarController::class, 'index'])->name('pendaftar.index');
    Route::post('/pendaftar', [PendaftarController::class, 'store'])->name('pendaftar.store');
    Route::get('/pendaftar/{pendaftar}', [PendaftarController::class, 'show'])->name('pendaftar.show');
    Route::get('/pendaftar/{pendaftar}/dokumen/{jenis}', [DokumenController::class, 'show'])->name('pendaftar.dokumen');

    Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
    Route::post('/verifikasi/{pendaftar}/proses', [VerifikasiController::class, 'proses'])->name('verifikasi.proses');

    Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai.index');
    Route::put('/nilai/{pendaftar}', [NilaiController::class, 'simpan'])->name('nilai.simpan');
    Route::post('/ranking/proses', [RankingController::class, 'proses'])->name('ranking.proses');

    Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::post('/pengumuman/terbit', [PengumumanController::class, 'terbit'])->name('pengumuman.terbit');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export/{format}', [LaporanController::class, 'export'])->name('laporan.export');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal', [JadwalController::class, 'update'])->name('jadwal.update');
});

// --- Portal calon siswa -------------------------------------------------

Route::middleware(['auth', 'verified:verify-email.notice'])->group(function () {
    Route::get('/status', [StatusController::class, 'index'])->name('status');
    Route::get('/surat/{nomor}', [SuratController::class, 'show'])->name('surat');
});

Route::middleware(['auth', 'verified:verify-email.notice'])->prefix('pendaftaran')->group(function () {
    Route::get('/', [PendaftaranController::class, 'index'])->name('pendaftaran');
    Route::post('/', [PendaftaranController::class, 'simpan']);
    Route::post('/dokumen', [PendaftaranController::class, 'dokumen']);
    Route::post('/kirim', [PendaftaranController::class, 'kirim']);
    Route::post('/revisi', [StatusController::class, 'revisi']);
});

// --- Autentikasi -------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify-email');
Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [AuthController::class, 'notice'])->name('verify-email.notice');
    Route::post('/verify-email/resend', [AuthController::class, 'resend'])->name('verify-email.resend');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
