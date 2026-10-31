<?php

namespace Tests\Feature;

use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusRevisiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_status_menampilkan_dokumen_dan_status_verifikasi(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee($p->nomor_pendaftaran)
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('KTP');
    }

    public function test_pendaftar_gugur_dapat_memperbaiki_dan_kirim_ulang_saat_tahap_perbaikan(): void
    {
        $p = $this->buatPendaftarGugur();
        $this->bukaTahap(TahapJadwal::PERBAIKAN);

        $this->actingAs($p->pengguna)
            ->post('/pendaftaran/revisi', ['pendaftar_id' => $p->id])
            ->assertRedirect('/status');

        $this->assertSame(StatusPendaftar::MENUNGGU_VERIFIKASI, $p->fresh()->status);
    }

    public function test_revisi_diblokir_setelah_lulus_verifikasi(): void
    {
        $p = $this->buatPendaftarLulus();

        $this->actingAs($p->pengguna)
            ->post('/pendaftaran/revisi', ['pendaftar_id' => $p->id])
            ->assertForbidden();
    }
}
