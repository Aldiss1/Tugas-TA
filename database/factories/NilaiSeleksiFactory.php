<?php

namespace Database\Factories;

use App\Models\NilaiSeleksi;
use App\Models\Pendaftar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NilaiSeleksi>
 */
class NilaiSeleksiFactory extends Factory
{
    public function definition(): array
    {
        $rapor = 70;
        $prestasi = 60;
        $kunjungan = 80;

        return [
            'pendaftar_id' => Pendaftar::factory(),
            'nilai_rapor' => $rapor,
            'nilai_prestasi' => $prestasi,
            'nilai_kunjungan' => $kunjungan,
            // 0.4 x 70 + 0.3 x 60 + 0.3 x 80 = 70
            'total' => 0.4 * $rapor + 0.3 * $prestasi + 0.3 * $kunjungan,
            'diinput_at' => now(),
        ];
    }
}
