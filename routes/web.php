<?php

use App\Http\Controllers\Portal\AuthController;
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
    return view('Dashboard');
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
