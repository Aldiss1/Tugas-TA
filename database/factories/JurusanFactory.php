<?php

namespace Database\Factories;

use App\Models\Jurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jurusan>
 */
class JurusanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->bothify('??#?')),
            'nama' => fake()->unique()->words(3, true),
            'deskripsi' => fake()->sentence(),
            'rombel' => fake()->numberBetween(1, 5),
            'aktif' => true,
        ];
    }
}
