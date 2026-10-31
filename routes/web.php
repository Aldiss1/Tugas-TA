<?php

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\CekStatusController;
use App\Http\Controllers\Portal\PendaftaranController;
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

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart', [\App\Http\Controllers\Admin\DashboardController::class, 'chart'])->name('dashboard.chart');

    Route::resource('jurusan', \App\Http\Controllers\Admin\JurusanController::class)->except('show');

    Route::get('/pendaftar', [\App\Http\Controllers\Admin\PendaftarController::class, 'index'])->name('pendaftar.index');
    Route::post('/pendaftar', [\App\Http\Controllers\Admin\PendaftarController::class, 'store'])->name('pendaftar.store');
    Route::get('/pendaftar/{pendaftar}', [\App\Http\Controllers\Admin\PendaftarController::class, 'show'])->name('pendaftar.show');
    Route::get('/pendaftar/{pendaftar}/dokumen/{jenis}', [\App\Http\Controllers\Admin\DokumenController::class, 'show'])->name('pendaftar.dokumen');

    Route::get('/verifikasi', [\App\Http\Controllers\Admin\VerifikasiController::class, 'index'])->name('verifikasi.index');
    Route::post('/verifikasi/{pendaftar}/proses', [\App\Http\Controllers\Admin\VerifikasiController::class, 'proses'])->name('verifikasi.proses');

    Route::get('/nilai', [\App\Http\Controllers\Admin\NilaiController::class, 'index'])->name('nilai.index');
    Route::put('/nilai/{pendaftar}', [\App\Http\Controllers\Admin\NilaiController::class, 'simpan'])->name('nilai.simpan');
    Route::post('/ranking/proses', [\App\Http\Controllers\Admin\RankingController::class, 'proses'])->name('ranking.proses');

    Route::get('/pengumuman', [\App\Http\Controllers\Admin\PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::post('/pengumuman/terbit', [\App\Http\Controllers\Admin\PengumumanController::class, 'terbit'])->name('pengumuman.terbit');

    Route::get('/jadwal', [\App\Http\Controllers\Admin\JadwalController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal', [\App\Http\Controllers\Admin\JadwalController::class, 'update'])->name('jadwal.update');
});

// --- Portal calon siswa -------------------------------------------------

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/status', [\App\Http\Controllers\Portal\StatusController::class, 'index'])->name('status');
});

Route::middleware(['auth', 'verified'])->prefix('pendaftaran')->group(function () {
    Route::get('/', [PendaftaranController::class, 'index'])->name('pendaftaran');
    Route::post('/', [PendaftaranController::class, 'simpan']);
    Route::post('/dokumen', [PendaftaranController::class, 'dokumen']);
    Route::post('/kirim', [PendaftaranController::class, 'kirim']);
    Route::post('/revisi', [\App\Http\Controllers\Portal\StatusController::class, 'revisi']);
});

// --- Autentikasi -------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify-email');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
