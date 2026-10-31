<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Exceptions\PengumumanSudahTerbit;
use App\Http\Controllers\Controller;
use App\Models\Hasil;
use App\Models\Pendaftar;
use App\Services\Psb\JadwalService;
use App\Services\Psb\NilaiService;
use App\Services\Psb\PengumumanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class NilaiController extends Controller
{
    public function index(): View
    {
        return view('admin.nilai.index', [
            'pendaftar' => Pendaftar::where('status', StatusPendaftar::LULUS_VERIFIKASI)
                ->with(['pilihan.jurusan', 'nilai'])
                ->orderBy('id')
                ->paginate(50),
            'hasil' => Hasil::with(['pendaftar', 'jurusan'])
                ->orderBy('jurusan_id')
                ->orderBy('perankingan')
                ->get(),
            'sudahTerbit' => app(PengumumanService::class)->sudahTerbit(),
        ]);
    }

    /**
     * Simpan banyak baris nilai sekaligus (spreadsheet). All-or-nothing:
     * bila ada baris invalid, tidak ada yang disimpan.
     */
    public function batch(Request $request, NilaiService $service, JadwalService $jadwal)
    {
        if (! $jadwal->isBuka(TahapJadwal::SELEKSI)) {
            abort(403, 'Tahap seleksi tidak sedang dibuka.');
        }

        $rules = [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['required', 'integer', 'exists:pendaftar,id'],
            'rows.*.rapor' => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.prestasi' => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.kunjungan' => ['required', 'numeric', 'min:0', 'max:100'],
        ];

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $baris = [];
                foreach ($validator->errors()->messages() as $field => $msgs) {
                    if (preg_match('/^rows\.(\d+)\./', $field, $m)) {
                        $idx = (int) $m[1];
                        $baris[$idx] ??= ['id' => null, 'error' => ''];
                        $baris[$idx]['error'] = $msgs[0];
                    }
                }
                foreach (array_keys($baris) as $idx) {
                    $baris[$idx]['id'] = $request->input('rows.'.$idx.'.id');
                }

                return response()->json([
                    'ok' => false,
                    'error' => $validator->errors()->first(),
                    'baris' => array_values($baris),
                ], 422);
            }
        } else {
            $request->validate($rules);
        }

        // Validasi per baris: pendaftar harus ada & LULUS_VERIFIKASI
        $barisError = [];
        foreach ($request->input('rows', []) as $i => $row) {
            $p = Pendaftar::find($row['id']);

            if (! $p) {
                $barisError[] = ['id' => $row['id'], 'error' => 'Pendaftar tidak ditemukan.'];

                continue;
            }
            if ($p->status !== StatusPendaftar::LULUS_VERIFIKASI) {
                $barisError[] = ['id' => $row['id'], 'error' => 'Bukan pendaftar lulus verifikasi.'];
            }
        }

        if ($barisError) {
            return $this->balasBatch(['ok' => false, 'error' => 'Ada baris yang bermasalah.', 'baris' => $barisError], $request);
        }

        try {
            $tersimpan = 0;
            foreach ($request->input('rows', []) as $row) {
                $p = Pendaftar::find($row['id']);
                $n = $p->nilai()->firstOrNew();
                $service->simpan($n, [
                    'rapor' => (float) $row['rapor'],
                    'prestasi' => (float) $row['prestasi'],
                    'kunjungan' => (float) $row['kunjungan'],
                ], $request->user());
                $tersimpan++;
            }
        } catch (PengumumanSudahTerbit $e) {
            return $this->balasBatch(['ok' => false, 'error' => $e->getMessage()], $request);
        }

        return $this->balasBatch(['ok' => true, 'tersimpan' => $tersimpan], $request);
    }

    private function balasBatch(array $payload, Request $request)
    {
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json($payload, $payload['ok'] ? 200 : 422);
        }

        if (! $payload['ok']) {
            return back()->withErrors(['rows' => $payload['error']]);
        }

        return back()->with('status', $payload['tersimpan'].' nilai disimpan.');
    }
}
