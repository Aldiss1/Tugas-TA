<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurusanKuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_calon_siswa_tidak_bisa_membuka_admin(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->get('/admin')->assertForbidden();
    }

    public function test_update_jurusan_cadangan_kosong_diisi_ceil_10_persen_kuota(): void
    {
        $j = Jurusan::where('kode', 'TKJ')->first();

        $this->actingAs($this->admin())->put('/admin/jurusan/'.$j->id, [
            'kode' => $j->kode,
            'nama' => $j->nama,
            'deskripsi' => $j->deskripsi,
            'rombel' => 2,
            'kuota' => 72,
            'cadangan' => '',
            'aktif' => 1,
        ]);

        $this->assertSame(72, $j->fresh()->kuota->kuota);
        $this->assertSame(8, $j->fresh()->kuota->cadangan); // ceil(72 x 0.1)
    }

    public function test_jurusan_yang_sudah_punya_pendaftar_hanya_dinonaktifkan(): void
    {
        $j = Jurusan::where('kode', 'TKJ')->first();
        $p = Pendaftar::factory()->create();
        PendaftarPilihan::create([
            'pendaftar_id' => $p->id,
            'jurusan_id' => $j->id,
            'prioritas' => 1,
        ]);

        $this->actingAs($this->admin())->delete('/admin/jurusan/'.$j->id);

        $this->assertFalse($j->fresh()->aktif);
        $this->assertNotNull($j->fresh());
    }
}
