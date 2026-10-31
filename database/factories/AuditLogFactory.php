<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pengguna_id' => User::factory(),
            'target' => 'pendaftar:1',
            'aksi' => 'verifikasi_lulus',
            'detail' => [],
            'created_at' => now(),
        ];
    }
}
