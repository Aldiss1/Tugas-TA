<?php

namespace Tests\Feature;

use App\Models\Pendaftar;
use App\Models\User;
use App\Services\Psb\PengumumanService;
use App\Services\Psb\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengumumanSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_yang_diterima_melihat_hasil_dan_surat_sedangkan_lainnya_statusnya(): void
    {
        // A punya akun (bisa login), B manual (cek via publik)
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
            'B' => ['nilai' => 80, 'p1' => 'J1'],
        ], kuota: ['J1' => 1], cadangan: ['J1' => 0]);

        $a = Pendaftar::where('nama_lengkap', 'A')->first();
        $a->update(['pengguna_id' => User::factory()->create()->id]);

        app(RankingService::class)->proses($this->admin());
        app(PengumumanService::class)->terbitkan($this->admin());

        $a = $a->fresh();

        $this->actingAs($a->pengguna)->get('/status')
            ->assertSee('Diterima')
            ->assertSee('Cetak');

        $this->actingAs($a->pengguna)->get('/surat/'.$a->nomor_pendaftaran)
            ->assertOk()
            ->assertSee('SMK Negeri 2 Singosari')
            ->assertSee($a->nomor_pendaftaran)
            ->assertSee('A');

        $b = Pendaftar::where('nama_lengkap', 'B')->first();
        $bUser = User::factory()->create();
        $b->update(['pengguna_id' => $bUser->id]);
        $this->actingAs($bUser)->get('/status')->assertSee('Tidak Diterima');
    }

    public function test_surat_ditolak_untuk_pendaftar_orang_lain(): void
    {
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1]);
        $a = Pendaftar::where('nama_lengkap', 'A')->first();
        $a->update(['pengguna_id' => User::factory()->create()->id]);
        app(RankingService::class)->proses($this->admin());
        app(PengumumanService::class)->terbitkan($this->admin());

        $orangLain = User::factory()->create();
        $this->actingAs($orangLain)->get('/surat/'.$a->fresh()->nomor_pendaftaran)->assertForbidden();
    }

    public function test_cek_status_publik_menampilkan_status_akhir_setelah_publish(): void
    {
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1]);
        $a = Pendaftar::where('nama_lengkap', 'A')->first();

        app(RankingService::class)->proses($this->admin());
        app(PengumumanService::class)->terbitkan($this->admin());

        $this->post('/cek-status', [
            'nomor' => $a->nomor_pendaftaran,
            'tgl_lahir' => $a->tanggal_lahir->format('Y-m-d'),
        ])->assertSee('Diterima');
    }
}
