<?php

namespace Database\Factories;

use App\Models\NilaiSeleksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NilaiSeleksiRiwayat>
 */
class NilaiSeleksiRiwayatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nilai_seleksi_id' => NilaiSeleksi::factory(),
            'snapshot' => [
                'nilai_rapor' => 70,
                'nilai_prestasi' => 60,
                'nilai_kunjungan' => 80,
                'total' => 70,
            ],
            'diubah_oleh' => User::factory(),
            'changed_at' => now(),
        ];
    }
}
