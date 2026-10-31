<?php

namespace App\Services\Psb;

use App\Models\Pendaftar;

class NomorPendaftaran
{
    /**
     * Format: PSB-YYYY-XXXXXX (urut per tahun).
     */
    public function generate(): string
    {
        $year = now()->format('Y');
        $count = Pendaftar::where('nomor_pendaftaran', 'like', 'PSB-'.$year.'-%')->count();

        return sprintf('PSB-%s-%06d', $year, $count + 1);
    }
}
