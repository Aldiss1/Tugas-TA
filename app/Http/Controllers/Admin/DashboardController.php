<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stat' => [
                'total' => Pendaftar::count(),
                'menunggu' => Pendaftar::where('status', StatusPendaftar::MENUNGGU_VERIFIKASI)->count(),
                'lulus' => Pendaftar::where('status', StatusPendaftar::LULUS_VERIFIKASI)->count(),
                'diterima' => Pendaftar::where('status', StatusPendaftar::DITERIMA)->count(),
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
