<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Exports\PendaftarExport;
use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $rekap = [];

        foreach (Jurusan::with('kuota')->orderBy('nama')->get() as $j) {
            $rekap[$j->kode] = [
                'nama' => $j->nama,
                'pendaftar' => Pendaftar::whereHas('pilihan', fn ($q) => $q->where('jurusan_id', $j->id))->count(),
                'terima' => Pendaftar::where('status', StatusPendaftar::DITERIMA)
                    ->whereHas('hasil', fn ($q) => $q->where('jurusan_id', $j->id))
                    ->count(),
                'cadangan' => Pendaftar::where('status', StatusPendaftar::CADANGAN)
                    ->whereHas('hasil', fn ($q) => $q->where('jurusan_id', $j->id))
                    ->count(),
                'kuota' => (int) ($j->kuota->kuota ?? 0),
                'sisa' => $j->kuota->sisa() ?? 0,
            ];
        }

        return view('admin.laporan', [
            'rekap' => $rekap,
            'status' => StatusPendaftar::cases(),
            'total' => [
                'pendaftar' => Pendaftar::count(),
                'diterima' => Pendaftar::where('status', StatusPendaftar::DITERIMA)->count(),
                'cadangan' => Pendaftar::where('status', StatusPendaftar::CADANGAN)->count(),
                'tidak_diterima' => Pendaftar::where('status', StatusPendaftar::TIDAK_DITERIMA)->count(),
            ],
        ]);
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $status = $request->input('status');
        $file = 'pendaftar-'.($status ?: 'semua').'.'.$format;

        return Excel::download(new PendaftarExport(['status' => $status]), $file);
    }
}
