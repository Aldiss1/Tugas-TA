<?php

namespace Tests\Feature;

use App\Enums\TahapJadwal;
use App\Models\NilaiSeleksiRiwayat;
use App\Services\Psb\NilaiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NilaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bukaTahap(TahapJadwal::SELEKSI);
    }

    public function test_total_40_30_30(): void
    {
        $this->assertSame(89.0, app(NilaiService::class)->hitungTotal(80, 90, 100));
    }

    public function test_input_nilai_menyimpan_dan_membuat_riwayat_saat_diubah(): void
    {
        $p = $this->buatPendaftarLulus();

        $this->actingAs($this->admin())->put('/admin/nilai/'.$p->id, [
            'rapor' => 80, 'prestasi' => 90, 'kunjungan' => 100,
        ]);
        $this->actingAs($this->admin())->put('/admin/nilai/'.$p->id, [
            'rapor' => 85, 'prestasi' => 90, 'kunjungan' => 100,
        ]);

        $n = $p->fresh()->nilai;
        $this->assertNotNull($n);
        $this->assertSame(91.0, (float) $n->total); // 0.4x85 + 0.3x90 + 0.3x100
        $this->assertSame(1, NilaiSeleksiRiwayat::where('nilai_seleksi_id', $n->id)->count());
    }

    public function test_nilai_di_atas_100_ditolak(): void
    {
        $p = $this->buatPendaftarLulus();

        $this->actingAs($this->admin())
            ->put('/admin/nilai/'.$p->id, ['rapor' => 150, 'prestasi' => 0, 'kunjungan' => 0])
            ->assertSessionHasErrors('rapor');
    }

    public function test_pendaftar_belum_lulus_verifikasi_tidak_bisa_diinput(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        $this->actingAs($this->admin())
            ->put('/admin/nilai/'.$p->id, ['rapor' => 80, 'prestasi' => 0, 'kunjungan' => 0])
            ->assertForbidden();
    }
}
