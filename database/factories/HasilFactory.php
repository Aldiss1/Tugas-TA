<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Pendaftar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hasil>
 */
class HasilFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pendaftar_id' => Pendaftar::factory(),
            'jurusan_id' => Jurusan::factory(),
            'perankingan' => 1,
            'status_akhir' => 'diterima',
            'via_pilihan' => 1,
            'diproses_at' => now(),
        ];
    }
}
