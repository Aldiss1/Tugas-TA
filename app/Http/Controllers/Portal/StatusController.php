<?php

namespace App\Http\Controllers\Portal;

use App\Exceptions\DokumenBelumLengkap;
use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Services\Psb\PendaftaranService;
use App\Services\Psb\PengumumanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusController extends Controller
{
    public function index(Request $request, PendaftaranService $service, PengumumanService $pengumuman): View
    {
        $p = Pendaftar::where('pengguna_id', $request->user()->id)->first();

        return view('portal.status.index', [
            'p' => $p,
            'bolehRevisi' => $service->bolehRevisi($p),
            'sudahTerbit' => $pengumuman->sudahTerbit(),
        ]);
    }

    public function revisi(Request $request, PendaftaranService $service): RedirectResponse
    {
        $request->validate(['pendaftar_id' => ['required', 'integer']]);

        $p = Pendaftar::where('pengguna_id', $request->user()->id)
            ->findOrFail($request->input('pendaftar_id'));

        if (! $service->bolehRevisi($p)) {
            abort(403);
        }

        if ($p->status->value === 'gugur') {
            try {
                $service->kirimUlang($p, $request->user());
            } catch (DokumenBelumLengkap $e) {
                return back()->withErrors(['pendaftar_id' => $e->getMessage()]);
            }

            return redirect('/status')->with('status', 'Pendaftaran dikirim ulang untuk diverifikasi.');
        }

        return redirect()->route('pendaftaran');
    }
}
