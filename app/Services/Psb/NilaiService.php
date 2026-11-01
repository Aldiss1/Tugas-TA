<?php

namespace App\Services\Psb;

use App\Enums\StatusPendaftar;
use App\Exceptions\PengumumanSudahTerbit;
use App\Models\NilaiSeleksi;
use App\Models\Pendaftar;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NilaiService
{
    /**
     * Total berbobot sesuai config('psb.bobot').
     */
    public function hitungTotal(float $rapor, float $prestasi, float $kunjungan): float
    {
        $bobot = config('psb.bobot');

        return round($bobot['rapor'] * $rapor + $bobot['prestasi'] * $prestasi + $bobot['kunjungan'] * $kunjungan, 2);
    }

    /**
     * Simpan nilai (baru atau ubah). Nilai lama diarsipkan ke riwayat.
     */
    public function simpan(NilaiSeleksi $n, array $nilai, ?User $by = null): NilaiSeleksi
    {
        if (app(PengumumanService::class)->sudahTerbit()) {
            throw PengumumanSudahTerbit::make();
        }

        $baru = [
            'nilai_rapor' => (float) $nilai['rapor'],
            'nilai_prestasi' => (float) $nilai['prestasi'],
            'nilai_kunjungan' => (float) $nilai['kunjungan'],
        ];

        $perubahan = $n->exists && ($n->nilai_rapor != $baru['nilai_rapor']
            || $n->nilai_prestasi != $baru['nilai_prestasi']
            || $n->nilai_kunjungan != $baru['nilai_kunjungan']);
        $baruDibuat = ! $n->exists;

        if ($perubahan) {
            $n->riwayat()->create([
                'snapshot' => [
                    'nilai_rapor' => $n->nilai_rapor,
                    'nilai_prestasi' => $n->nilai_prestasi,
                    'nilai_kunjungan' => $n->nilai_kunjungan,
                    'total' => $n->total,
                ],
                'diubah_oleh' => $by?->id,
                'changed_at' => now(),
            ]);
        }

        $n->fill($baru);
        $n->total = $this->hitungTotal($baru['nilai_rapor'], $baru['nilai_prestasi'], $baru['nilai_kunjungan']);
        $n->diinput_at = now();
        $n->save();

        if ($baruDibuat || $perubahan) {
            app(AuditService::class)->catat($by, 'pendaftar:'.$n->pendaftar_id, $perubahan ? 'nilai_ubah' : 'nilai_input', [
                'rapor' => $baru['nilai_rapor'],
                'prestasi' => $baru['nilai_prestasi'],
                'kunjungan' => $baru['nilai_kunjungan'],
            ]);
        }

        return $n;
    }

    /**
     * Import nilai massal (all-or-nothing). Jika ada 1 baris error, TIDAK ada yang disimpan.
     *
     * @param array<int, array{nisn: string, rapor: float, prestasi: float, kunjungan: float, baris: int}> $baris
     * @return array{ok: true, tersimpan: int}|array{ok: false, pesan: string, baris: array<int, array{baris: int, nisn: string, error: string}>}
     */
    public function impor(array $baris, ?User $by = null): array
    {
        if (app(PengumumanService::class)->sudahTerbit()) {
            throw PengumumanSudahTerbit::make();
        }

        $lihat = [];
        $errors = [];
        $valid = [];

        foreach ($baris as $row) {
            $nisn = (string) $row['nisn'];

            if (isset($lihat[$nisn])) {
                $errors[] = ['baris' => $row['baris'], 'nisn' => $nisn, 'error' => 'NISN duplikat di file.'];

                continue;
            }
            $lihat[$nisn] = true;

            $p = Pendaftar::where('nisn', $nisn)->first();

            if (! $p) {
                $errors[] = ['baris' => $row['baris'], 'nisn' => $nisn, 'error' => 'Pendaftar tidak ditemukan.'];

                continue;
            }

            if ($p->status !== StatusPendaftar::LULUS_VERIFIKASI) {
                $errors[] = ['baris' => $row['baris'], 'nisn' => $nisn, 'error' => 'Bukan pendaftar lulus verifikasi.'];

                continue;
            }

            $valid[] = [$p, $row];
        }

        if ($errors) {
            return [
                'ok' => false,
                'pesan' => 'Ada '.count($errors).' baris bermasalah.',
                'baris' => $errors,
            ];
        }

        $tersimpan = 0;
        DB::transaction(function () use ($valid, $by, &$tersimpan) {
            foreach ($valid as [$p, $row]) {
                $n = $p->nilai()->firstOrNew();
                $this->simpan($n, [
                    'rapor' => (float) $row['rapor'],
                    'prestasi' => (float) $row['prestasi'],
                    'kunjungan' => (float) $row['kunjungan'],
                ], $by);
                $tersimpan++;
            }
        });

        app(AuditService::class)->catat($by, 'psb', 'nilai_impor', ['jumlah' => $tersimpan]);

        return ['ok' => true, 'tersimpan' => $tersimpan];
    }
}
