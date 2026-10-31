<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Services\Psb\JadwalService;
use App\Services\Psb\NilaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiController extends Controller
{
    public function index(): View
    {
        return view('admin.nilai.index', [
            'pendaftar' => Pendaftar::where('status', StatusPendaftar::LULUS_VERIFIKASI)
                ->with(['pilihan.jurusan', 'nilai'])
                ->orderBy('id')
                ->paginate(20),
        ]);
    }

    public function simpan(Request $request, Pendaftar $pendaftar, NilaiService $service, JadwalService $jadwal): RedirectResponse
    {
        if (! $jadwal->isBuka(TahapJadwal::SELEKSI)) {
            abort(403, 'Tahap seleksi tidak sedang dibuka.');
        }

        if ($pendaftar->status !== StatusPendaftar::LULUS_VERIFIKASI) {
            abort(403, 'Pendaftar belum lulus verifikasi.');
        }

        $validated = $request->validate([
            'rapor' => ['required', 'numeric', 'min:0', 'max:100'],
            'prestasi' => ['required', 'numeric', 'min:0', 'max:100'],
            'kunjungan' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $n = $pendaftar->nilai()->firstOrNew();
        $service->simpan($n, $validated, $request->user());

        return back()->with('status', 'Nilai '.$pendaftar->nama_lengkap.' disimpan. Total: '.$n->total);
    }
}
