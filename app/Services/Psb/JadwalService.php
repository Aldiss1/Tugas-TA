<?php

namespace App\Services\Psb;

use App\Enums\TahapJadwal;
use App\Models\JadwalPsb;

class JadwalService
{
    /**
     * Tahap yang sedang aktif (berdasarkan now), atau null jika tidak ada.
     * Tahap boleh saling tumpang tindih (mis. verifikasi & perbaikan).
     */
    public function tahapAktif(): ?TahapJadwal
    {
        $order = [
            TahapJadwal::PENDAFTARAN,
            TahapJadwal::VERIFIKASI,
            TahapJadwal::PERBAIKAN,
            TahapJadwal::SELEKSI,
            TahapJadwal::PENGUMUMAN,
        ];

        foreach ($order as $tahap) {
            if ($this->isBuka($tahap)) {
                return $tahap;
            }
        }

        return null;
    }

    public function isBuka(TahapJadwal $tahap): bool
    {
        $j = JadwalPsb::where('tahap', $tahap)->first();

        if (! $j) {
            return false;
        }

        return $j->mulai->lessThanOrEqualTo(now()) && now()->lessThanOrEqualTo($j->selesai);
    }

    /**
     * @return array<string, array{label: string, mulai: ?string, selesai: ?string, aktif: bool}>
     */
    public function statusSemua(): array
    {
        $out = [];
        $now = now();

        foreach (TahapJadwal::cases() as $tahap) {
            $j = JadwalPsb::where('tahap', $tahap)->first();

            $out[$tahap->value] = [
                'label' => $tahap->label(),
                'mulai' => $j?->mulai?->format('d M Y H:i'),
                'selesai' => $j?->selesai?->format('d M Y H:i'),
                'aktif' => $j ? ($j->mulai->lessThanOrEqualTo($now) && $now->lessThanOrEqualTo($j->selesai)) : false,
            ];
        }

        return $out;
    }
}
