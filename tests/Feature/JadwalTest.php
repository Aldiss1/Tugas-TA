<?php

namespace Tests\Feature;

use App\Enums\TahapJadwal;
use App\Models\JadwalPsb;
use App\Models\Pendaftar;
use App\Services\Psb\JadwalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_is_buka_true_hanya_saat_now_dalam_periode_tahap(): void
    {
        $s = app(JadwalService::class);

        JadwalPsb::where('tahap', 'pendaftaran')->update([
            'mulai' => now()->subDay(),
            'selesai' => now()->addDay(),
        ]);
        $this->assertTrue($s->isBuka(TahapJadwal::PENDAFTARAN));

        JadwalPsb::where('tahap', 'pendaftaran')->update([
            'mulai' => now()->subDays(3),
            'selesai' => now()->subDay(),
        ]);
        $this->assertFalse($s->isBuka(TahapJadwal::PENDAFTARAN));
    }

    public function test_simpan_jadwal_lalu_halaman_tetap_render(): void
    {
        $data = [];
        foreach (['pendaftaran', 'verifikasi', 'perbaikan', 'seleksi', 'pengumuman'] as $t) {
            $data['mulai'][$t] = '2026-11-01T07:00';
            $data['selesai'][$t] = '2026-11-30T23:59';
        }

        $this->actingAs($this->admin())->post('/admin/jadwal', $data)->assertRedirect();

        $this->actingAs($this->admin())->get('/admin/jadwal')
            ->assertOk()
            ->assertSee('Pendaftaran')
            ->assertSee('Pengumuman');
    }

    public function test_beranda_menampilkan_tahap_aktif_dan_7_jurusan(): void
    {
        JadwalPsb::where('tahap', 'pendaftaran')->update([
            'mulai' => now()->subDay(),
            'selesai' => now()->addDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Penerimaan Siswa Baru')
            ->assertSee('TKJ')
            ->assertSee('Rekayasa Perangkat Lunak');
    }

    public function test_cek_status_publik_nomor_dan_tgl_lahir_cocok_menampilkan_status(): void
    {
        $p = Pendaftar::factory()->create();

        $this->post('/cek-status', [
            'nomor' => $p->nomor_pendaftaran,
            'tgl_lahir' => $p->tanggal_lahir->format('Y-m-d'),
        ])->assertSee($p->status->label());

        $this->post('/cek-status', [
            'nomor' => $p->nomor_pendaftaran,
            'tgl_lahir' => '1999-01-01',
        ])->assertSessionHasErrors('nomor');
    }
}
