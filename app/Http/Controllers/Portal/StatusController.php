<?php

namespace App\Http\Controllers\Portal;

use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Exceptions\DokumenBelumLengkap;
use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Services\Psb\JadwalService;
use App\Services\Psb\PendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusController extends Controller
{
    public function index(Request $request): View
    {
        $p = Pendaftar::where('pengguna_id', $request->user()->id)->first();

        return view('portal.status.index', [
            'p' => $p,
            'bolehRevisi' => $this->bolehRevisi($p),
            'jadwal' => app(JadwalService::class)->statusSemua(),
        ]);
    }

    public function revisi(Request $request, PendaftaranService $service): RedirectResponse
    {
        $request->validate(['pendaftar_id' => ['required', 'integer']]);

        $p = Pendaftar::where('pengguna_id', $request->user()->id)
            ->findOrFail($request->input('pendaftar_id'));

        if (! $this->bolehRevisi($p)) {
            abort(403);
        }

        if ($p->status === StatusPendaftar::GUGUR) {
            try {
                $service->kirimUlang($p);
            } catch (DokumenBelumLengkap $e) {
                return back()->withErrors(['pendaftar_id' => $e->getMessage()]);
            }

            return redirect('/status')->with('status', 'Pendaftaran dikirim ulang untuk diverifikasi.');
        }

        return redirect()->route('pendaftaran');
    }

    private function bolehRevisi(?Pendaftar $p): bool
    {
        if (! $p) {
            return false;
        }

        $jadwal = app(JadwalService::class);

        if ($p->status === StatusPendaftar::DRAF) {
            return $jadwal->isBuka(TahapJadwal::PENDAFTARAN);
        }

        if ($p->status === StatusPendaftar::GUGUR) {
            return $jadwal->isBuka(TahapJadwal::PERBAIKAN);
        }

        return false;
    }
}
