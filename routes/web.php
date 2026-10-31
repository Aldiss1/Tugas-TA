<?php

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\CekStatusController;
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

// --- Autentikasi -------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify-email');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
