<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TahapJadwal;
use App\Http\Controllers\Controller;
use App\Models\JadwalPsb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(): View
    {
        return view('admin.jadwal.index', [
            'jadwal' => JadwalPsb::all()->keyBy('tahap'),
            'tahap' => TahapJadwal::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mulai.*' => ['required', 'date'],
            'selesai.*' => ['required', 'date', 'after:mulai.*'],
        ]);

        foreach (TahapJadwal::cases() as $tahap) {
            JadwalPsb::where('tahap', $tahap)->update([
                'mulai' => $validated['mulai'][$tahap->value] ?? null,
                'selesai' => $validated['selesai'][$tahap->value] ?? null,
            ]);
        }

        return back()->with('status', 'Jadwal PSB disimpan.');
    }
}
