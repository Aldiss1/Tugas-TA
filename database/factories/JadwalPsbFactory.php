<?php

namespace Database\Factories;

use App\Enums\TahapJadwal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JadwalPsb>
 */
class JadwalPsbFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tahap' => fake()->unique()->randomElement(TahapJadwal::cases()),
            'mulai' => now()->subDay(),
            'selesai' => now()->addDay(),
        ];
    }
}
