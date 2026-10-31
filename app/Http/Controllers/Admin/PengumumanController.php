<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Exceptions\PengumumanBelumSiap;
use App\Http\Controllers\Controller;
use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Services\Psb\PengumumanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function index(PengumumanService $service): View
    {
        return view('admin.pengumuman', [
            'terbit' => $service->sudahTerbit(),
            'waktu' => $service->waktuTerbit(),
            'adaHasil' => Hasil::count() > 0,
            'jurusan' => Jurusan::with('kuota')->orderBy('nama')->get(),
            'stat' => [
                'diterima' => Pendaftar::where('status', StatusPendaftar::DITERIMA)->count(),
                'cadangan' => Pendaftar::where('status', StatusPendaftar::CADANGAN)->count(),
                'tidak_diterima' => Pendaftar::where('status', StatusPendaftar::TIDAK_DITERIMA)->count(),
            ],
        ]);
    }

    public function terbit(Request $request, PengumumanService $service): RedirectResponse
    {
        try {
            $service->terbitkan($request->user());
        } catch (PengumumanBelumSiap $e) {
            return back()->withErrors(['pengumuman' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengumuman PSB diterbitkan. Data seleksi kini terkunci.');
    }
}
