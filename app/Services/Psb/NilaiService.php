<?php

namespace App\Services\Psb;

use App\Exceptions\PengumumanSudahTerbit;
use App\Models\NilaiSeleksi;
use App\Models\User;

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
}
