<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\User;
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

        $this->prosesSatu($pendaftar, $validated['aksi'], $validated['alasan'] ?? null, $request->user(), false);

        $pesan = $validated['aksi'] === 'lulus'
            ? 'Pendaftar '.$pendaftar->nama_lengkap.' lulus verifikasi.'
            : 'Pendaftar '.$pendaftar->nama_lengkap.' digugurkan.';

        return back()->with('status', $pesan);
    }

    /**
     * Verifikasi massal: satu aksi (opsi dengan satu alasan) untuk banyak pendaftar.
     */
    public function batch(Request $request, JadwalService $jadwal, AuditService $audit): RedirectResponse
    {
        if (! $jadwal->isBuka(TahapJadwal::VERIFIKASI)) {
            abort(403, 'Tahap verifikasi tidak sedang dibuka.');
        }

        $validated = $request->validate([
            'aksi' => ['required', 'in:lulus,gugur'],
            'alasan' => ['required_if:aksi,gugur', 'nullable', 'string', 'max:500'],
            'ids' => ['array'],
            'ids.*' => ['integer'],
        ], [
            'alasan.required_if' => 'Alasan wajib diisi untuk penguguran massal.',
        ]);

        $diproses = 0;

        foreach (($validated['ids'] ?? []) as $id) {
            $p = Pendaftar::find($id);

            if (! $p || $p->status !== StatusPendaftar::MENUNGGU_VERIFIKASI) {
                continue;
            }

            $this->prosesSatu($p, $validated['aksi'], $validated['alasan'] ?? null, $request->user(), true);
            $diproses++;
        }

        return back()->with('status', $diproses.' pendaftar diproses.');
    }

    private function prosesSatu(Pendaftar $p, string $aksi, ?string $alasan, ?User $by, bool $massal): void
    {
        $detail = $massal ? ['massal' => true] : [];

        if ($aksi === 'lulus') {
            $p->update(['status' => StatusPendaftar::LULUS_VERIFIKASI]);
            app(AuditService::class)->catat($by, 'pendaftar:'.$p->id, 'verifikasi_lulus', $detail);
        } else {
            $p->update([
                'status' => StatusPendaftar::GUGUR,
                'alasan_gugur' => $alasan,
            ]);
            app(AuditService::class)->catat($by, 'pendaftar:'.$p->id, 'verifikasi_gugur', array_merge($detail, ['alasan' => $alasan]));
        }
    }
}
