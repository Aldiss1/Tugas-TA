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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    /**
     * Unduh template CSV import nilai.
     */
    public function template(): Response
    {
        $csv = "nisn,rapor,prestasi,kunjungan\n1234567890,85.5,90,88\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-nilai.csv"',
        ]);
    }

    /**
     * Import nilai dari CSV (nisn,rapor,prestasi,kunjungan).
     * All-or-nothing, konsisten dengan batch; error dilaporkan per baris.
     */
    public function import(Request $request, NilaiService $service, JadwalService $jadwal): RedirectResponse
    {
        if (! $jadwal->isBuka(TahapJadwal::SELEKSI)) {
            abort(403, 'Tahap seleksi tidak sedang dibuka.');
        }

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $fh = fopen($request->file('file')->getRealPath(), 'r');

        try {
            $header = fgetcsv($fh, null, ',', '"', '');

            if ($header === false) {
                return $this->balasImporError(['pesan' => 'File CSV kosong.', 'baris' => []]);
            }

            $map = [];
            foreach ($header as $i => $sel) {
                $nama = mb_strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $sel)));

                if ($nama !== '') {
                    $map[$nama] = $i;
                }
            }

            foreach (['nisn', 'rapor', 'prestasi', 'kunjungan'] as $wajib) {
                if (! isset($map[$wajib])) {
                    return $this->balasImporError([
                        'pesan' => 'Header CSV harus berisi kolom: nisn,rapor,prestasi,kunjungan.',
                        'baris' => [],
                    ]);
                }
            }

            $jumlahKolom = count(array_keys($map));
            $baris = [];
            $errors = [];
            $no = 1;

            while (($row = fgetcsv($fh, null, ',', '"', '')) !== false) {
                $no++;

                // Baris kosong (semua sel kosong) dilewati
                if (count(array_filter(array_map(fn ($v) => trim((string) ($v ?? '')), $row), fn ($v) => $v !== '')) === 0) {
                    continue;
                }

                if (count($baris) + count($errors) >= 1000) {
                    return $this->balasImporError(['pesan' => 'Maksimal 1000 baris data.', 'baris' => []]);
                }

                // Lebih banyak sel dari header: kemungkinan desimal pakai koma
                if (count($row) > $jumlahKolom) {
                    $errors[] = [
                        'baris' => $no,
                        'nisn' => trim((string) ($row[$map['nisn']] ?? '')),
                        'error' => 'Jumlah kolom tidak sesuai header — desimal harus memakai titik (contoh: 85.5), bukan koma.',
                    ];

                    continue;
                }

                $nisn = trim((string) ($row[$map['nisn']] ?? ''));
                $err = null;

                if (! preg_match('/^\d{10}$/', $nisn)) {
                    $err = 'NISN harus 10 digit angka.';
                }

                if (! $err) {
                    foreach (['rapor', 'prestasi', 'kunjungan'] as $kolom) {
                        $v = trim((string) ($row[$map[$kolom]] ?? ''));

                        if (str_contains($v, ',')) {
                            $err = 'Desimal harus memakai titik (contoh: 85.5).';

                            break;
                        }

                        if (! is_numeric($v)) {
                            $err = 'Tidak berupa angka.';

                            break;
                        }

                        if ((float) $v < 0 || (float) $v > 100) {
                            $err = 'Nilai harus antara 0 dan 100.';

                            break;
                        }
                    }
                }

                if ($err) {
                    $errors[] = ['baris' => $no, 'nisn' => $nisn, 'error' => $err];

                    continue;
                }

                $baris[] = [
                    'nisn' => $nisn,
                    'rapor' => (float) $row[$map['rapor']],
                    'prestasi' => (float) $row[$map['prestasi']],
                    'kunjungan' => (float) $row[$map['kunjungan']],
                    'baris' => $no,
                ];
            }

            if ($errors) {
                return $this->balasImporError([
                    'pesan' => 'Ada '.count($errors).' baris bermasalah.',
                    'baris' => $errors,
                ]);
            }

            if ($baris === []) {
                return $this->balasImporError(['pesan' => 'Tidak ada baris data di file.', 'baris' => []]);
            }

            try {
                $r = $service->impor($baris, $request->user());
            } catch (PengumumanSudahTerbit $e) {
                return $this->balasImporError(['pesan' => $e->getMessage(), 'baris' => []]);
            }

            if ($r['ok']) {
                return back()->with('status', $r['tersimpan'].' nilai berhasil diimpor.');
            }

            return $this->balasImporError($r);
        } finally {
            fclose($fh);
        }
    }

    private function balasImporError(array $payload): RedirectResponse
    {
        return back()->with('imporError', $payload);
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
