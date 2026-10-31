<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendaftarPilihan>
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
