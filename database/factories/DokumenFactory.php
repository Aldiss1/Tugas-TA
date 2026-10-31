<?php

namespace Database\Factories;

use App\Models\Dokumen;
use App\Models\Pendaftar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dokumen>
 */
class DokumenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pendaftar_id' => Pendaftar::factory(),
            'jenis' => 'ktp',
            'path' => 'dokumen/dummy.pdf',
            'tipe_file' => 'application/pdf',
            'ukuran_bytes' => 1024,
            'diunggah_at' => now(),
        ];
    }
}
