<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Services\Psb\AuditService;
use App\Services\Psb\JadwalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function index(): View
    {
        return view('admin.verifikasi.index', [
            'pendaftar' => Pendaftar::where('status', StatusPendaftar::MENUNGGU_VERIFIKASI)
                ->with(['pilihan.jurusan', 'dokumen'])
                ->orderBy('id')
                ->paginate(20),
        ]);
    }

    public function proses(Request $request, Pendaftar $pendaftar, JadwalService $jadwal, AuditService $audit): RedirectResponse
    {
        if (! $jadwal->isBuka(TahapJadwal::VERIFIKASI)) {
            abort(403, 'Tahap verifikasi tidak sedang dibuka.');
        }

        $validated = $request->validate([
            'aksi' => ['required', 'in:lulus,gugur'],
            'alasan' => ['required_if:aksi,gugur', 'nullable', 'string', 'max:500'],
        ], [
            'alasan.required_if' => 'Alasan wajib diisi ketika pendaftar digugurkan.',
        ]);

        if ($pendaftar->status !== StatusPendaftar::MENUNGGU_VERIFIKASI) {
            return back()->withErrors(['pendaftar' => 'Pendaftar sudah tidak dalam antrian verifikasi.']);
        }

        if ($validated['aksi'] === 'lulus') {
            $pendaftar->update(['status' => StatusPendaftar::LULUS_VERIFIKASI]);
            $audit->catat($request->user(), 'pendaftar:'.$pendaftar->id, 'verifikasi_lulus');
            $pesan = 'Pendaftar '.$pendaftar->nama_lengkap.' lulus verifikasi.';
        } else {
            $pendaftar->update([
                'status' => StatusPendaftar::GUGUR,
                'alasan_gugur' => $validated['alasan'],
            ]);
            $audit->catat($request->user(), 'pendaftar:'.$pendaftar->id, 'verifikasi_gugur', ['alasan' => $validated['alasan']]);
            $pesan = 'Pendaftar '.$pendaftar->nama_lengkap.' digugurkan.';
        }

        return back()->with('status', $pesan);
    }
}
