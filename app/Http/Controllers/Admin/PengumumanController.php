<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'jurusan' => Jurusan::with('kuota')->orderBy('nama')->get(),
            'stat' => [
                'diterima' => Pendaftar::where('status', 'diterima')->count(),
                'cadangan' => Pendaftar::where('status', 'cadangan')->count(),
                'tidak_diterima' => Pendaftar::where('status', 'tidak_diterima')->count(),
            ],
        ]);
    }

    public function terbit(Request $request, PengumumanService $service): RedirectResponse
    {
        $service->terbitkan($request->user());

        return back()->with('status', 'Pengumuman PSB diterbitkan. Data seleksi kini terkunci.');
    }
}
