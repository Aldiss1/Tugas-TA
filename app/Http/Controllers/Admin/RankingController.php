<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AdaPendaftarTanpaNilai;
use App\Exceptions\PengumumanSudahTerbit;
use App\Http\Controllers\Controller;
use App\Services\Psb\RankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function proses(Request $request, RankingService $service): RedirectResponse
    {
        try {
            $r = $service->proses($request->user());
        } catch (AdaPendaftarTanpaNilai $e) {
            return back()->withErrors([
                'ranking' => 'Proses diblokir: masih ada pendaftar lulus verifikasi tanpa nilai (ID: '.implode(', ', $e->ids).').',
            ]);
        } catch (PengumumanSudahTerbit) {
            return back()->withErrors(['ranking' => 'Pengumuman sudah terbit - proses ranking terkunci.']);
        }

        $pesan = 'Ranking selesai: '.$r['diterima'].' diterima, '.$r['cadangan'].' cadangan, '.$r['tidak_diterima'].' tidak diterima.';

        return redirect()->route('admin.nilai.index')->with('status', $pesan);
    }
}
