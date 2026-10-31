<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Pendaftar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PendaftarPilihan>
 */
class PendaftarPilihanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pendaftar_id' => Pendaftar::factory(),
            'jurusan_id' => Jurusan::factory(),
            'prioritas' => 1,
        ];
    }
}
