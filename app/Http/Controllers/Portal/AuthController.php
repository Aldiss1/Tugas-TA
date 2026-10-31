<?php

namespace App\Http\Controllers\Portal;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'nisn' => ['required', 'string', 'regex:/^\d{10}$/', 'unique:users,nisn', 'unique:pendaftar,nisn'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nisn.regex' => 'NISN harus 10 digit angka.',
            'nisn.unique' => 'NISN sudah terdaftar.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'nisn' => $validated['nisn'],
            'role' => Role::CALON_SISWA->value,
        ]);

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('login')
            ->with('status', 'Akun berhasil dibuat. Periksa email untuk tautan verifikasi.');
    }

    public function login(Request $request): RedirectResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        $attemptKey = 'login_attempts.'.Str::lower($email);
        $lockKey = 'login_locked_until.'.Str::lower($email);

        if (time() < (int) session($lockKey, 0)) {
            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan salah. Coba lagi dalam '.config('psb.lock_login.durasi_menit').' menit.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user && Hash::check((string) $request->input('password'), $user->password)) {
            session()->forget($attemptKey);
            session()->forget($lockKey);
            Auth::login($user, $request->boolean('remember'));

            return $user->role === Role::ADMIN->value
                ? redirect()->intended('/admin')
                : redirect()->intended('/status');
        }

        $attempts = (int) session($attemptKey, 0) + 1;
        session([$attemptKey => $attempts]);

        if ($attempts >= (int) config('psb.lock_login.percobaan')) {
            session([$lockKey => time() + (int) config('psb.lock_login.durasi_menit') * 60]);

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan salah. Coba lagi dalam '.config('psb.lock_login.durasi_menit').' menit.',
            ]);
        }

        return back()->withErrors(['email' => 'Email atau password salah.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function verifyEmail(Request $request, string $token): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Email sudah terverifikasi.');
        }

        $expired = now()->subMinutes((int) config('psb.link_verifikasi_expiry'))->gte($user->created_at);

        if (hash_equals(sha1($user->getEmailForVerification()), $token) && ! $expired) {
            $user->markEmailAsVerified();

            return redirect()->route('login')->with('status', 'Email berhasil diverifikasi.');
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Link verifikasi tidak valid atau sudah kedaluwarsa.',
        ]);
    }
}
