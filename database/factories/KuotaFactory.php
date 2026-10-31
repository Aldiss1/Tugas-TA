<?php

namespace Database\Factories;

use App\Models\Jurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kuota>
 */
class KuotaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jurusan_id' => Jurusan::factory(),
            'kuota' => 36,
            'cadangan' => 4,
            'terisi' => 0,
        ];
    }
}
