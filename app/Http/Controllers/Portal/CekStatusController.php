<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Services\Psb\PengumumanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CekStatusController extends Controller
{
    public function show(PengumumanService $pengumuman): View
    {
        return view('public.cek-status', ['sudahTerbit' => $pengumuman->sudahTerbit()]);
    }

    public function cek(Request $request, PengumumanService $pengumuman): View|RedirectResponse
    {
        $validated = $request->validate([
            'nomor' => ['required', 'string'],
            'tgl_lahir' => ['required', 'date'],
        ]);

        $pendaftar = Pendaftar::where('nomor_pendaftaran', $validated['nomor'])
            ->whereDate('tanggal_lahir', $validated['tgl_lahir'])
            ->first();

        if (! $pendaftar) {
            return back()->withErrors([
                'nomor' => 'Pendaftaran tidak ditemukan. Periksa nomor pendaftaran dan tanggal lahir.',
            ]);
        }

        return view('public.cek-status', [
            'pendaftar' => $pendaftar,
            'sudahTerbit' => $pengumuman->sudahTerbit(),
        ]);
    }
}
