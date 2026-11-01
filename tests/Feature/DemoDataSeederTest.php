<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Hasil;
use App\Models\NilaiSeleksi;
use App\Models\Pendaftar;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_membuat_pendaftar_dalam_berbagai_status(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThan(0, Pendaftar::count());
        $this->assertGreaterThan(0, Pendaftar::where('status', 'menunggu_verifikasi')->count());
        $this->assertGreaterThan(0, Pendaftar::where('status', 'diterima')->count());
        $this->assertGreaterThan(0, Pendaftar::where('status', 'cadangan')->count());
        $this->assertGreaterThan(0, Pendaftar::where('status', 'tidak_diterima')->count());
        $this->assertGreaterThan(0, Pendaftar::where('status', 'gugur')->count());
    }

    public function test_demo_seeder_membuat_dokumen_nilai_hasil(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThan(0, Dokumen::count());
        $this->assertGreaterThan(0, NilaiSeleksi::count());
        $this->assertGreaterThan(0, Hasil::count());
    }

    public function test_demo_seeder_idempoten(): void
    {
        $this->seed(DemoDataSeeder::class);
        $count1 = Pendaftar::count();

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($count1, Pendaftar::count(), 'Demo seeder harus idempoten (tidak menduplikasi data)');
    }

    public function test_demo_seeder_tidak_menghapus_data_asli(): void
    {
        // Buat 1 pendaftar "asli" (bukan demo)
        $asli = Pendaftar::factory()->create(['nama_lengkap' => 'Pendaftar Asli']);

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class); // jalankan 2x

        $this->assertDatabaseHas('pendaftar', ['id' => $asli->id, 'nama_lengkap' => 'Pendaftar Asli']);
    }
}
