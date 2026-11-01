<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function index(): View
    {
        return view('portal.akun.index');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check((string) $request->input('password_lama'), $request->user()->password)) {
            return back()->withErrors(['password_lama' => 'Password lama salah.']);
        }

        $request->user()->update(['password' => $request->input('password')]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Password berhasil diubah. Silakan masuk kembali.');
    }
}
