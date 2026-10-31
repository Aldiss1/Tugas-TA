<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Http\Controllers\Controller;
use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Services\Psb\PengumumanService;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(PengumumanService $pengumuman): View
    {
        $sudahTerbit = $pengumuman->sudahTerbit();

        return view('admin.dashboard', [
            'pipeline' => [
                'pendaftar' => [
                    'total' => Pendaftar::count(),
                    'draf' => Pendaftar::where('status', StatusPendaftar::DRAF)->count(),
                ],
                'verifikasi' => [
                    'menunggu' => Pendaftar::where('status', StatusPendaftar::MENUNGGU_VERIFIKASI)->count(),
                    'lulus' => Pendaftar::where('status', StatusPendaftar::LULUS_VERIFIKASI)->count(),
                    'gugur' => Pendaftar::where('status', StatusPendaftar::GUGUR)->count(),
                ],
                'nilai' => [
                    'ternilai' => Pendaftar::where('status', StatusPendaftar::LULUS_VERIFIKASI)->whereHas('nilai')->count(),
                    'belum' => Pendaftar::where('status', StatusPendaftar::LULUS_VERIFIKASI)->whereDoesntHave('nilai')->count(),
                ],
                'hasil' => [
                    'diterima' => Hasil::where('status_akhir', 'diterima')->count(),
                    'cadangan' => Hasil::where('status_akhir', 'cadangan')->count(),
                    'tidak_diterima' => Hasil::where('status_akhir', 'tidak_diterima')->count(),
                    'terbit' => $sudahTerbit,
                ],
            ],
            'jurusan' => Jurusan::with('kuota')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Data grafik pendaftar per hari (14 hari terakhir).
     */
    public function chart(): Response
    {
        $rows = Pendaftar::query()
            ->selectRaw('date(created_at) as d, count(*) as total')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $labels[] = $d;
            $data[] = (int) ($rows[$d] ?? 0);
        }

        return Response::json(['labels' => $labels, 'data' => $data]);
    }
}
