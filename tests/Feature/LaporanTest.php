<?php

namespace Tests\Feature;

use App\Services\Psb\PengumumanService;
use App\Services\Psb\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_dashboard_menampilkan_statistik_sesuai_data(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
            'B' => ['nilai' => 80, 'p1' => 'J1'],
        ], kuota: ['J1' => 1], cadangan: ['J1' => 0]);
        app(RankingService::class)->proses($this->admin());
        app(PengumumanService::class)->terbitkan($this->admin()); // status diterapkan saat publish

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Total Pendaftar')
            ->assertViewHas('stat', fn (array $stat) => $stat['total'] === 2 && $stat['diterima'] === 1);
    }

    public function test_ekspor_csv_dan_xlsx_berisi_pendaftar_terpilih(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
            'B' => ['nilai' => 80, 'p1' => 'J1'],
        ], kuota: ['J1' => 1], cadangan: ['J1' => 0]);
        app(RankingService::class)->proses($this->admin());

        $r1 = $this->actingAs($this->admin())->get('/admin/laporan/export/csv?status=diterima');
        $r1->assertOk();
        $this->assertStringContainsString('attachment', $r1->headers->get('Content-Disposition'));

        $r2 = $this->actingAs($this->admin())->get('/admin/laporan/export/xlsx?status=diterima');
        $r2->assertOk();
        $this->assertStringContainsString('attachment', $r2->headers->get('Content-Disposition'));
    }

    public function test_halaman_audit_log_menampilkan_aksi_publish(): void
    {
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1]);
        app(RankingService::class)->proses($this->admin());
        app(PengumumanService::class)->terbitkan($this->admin());

        $this->actingAs($this->admin())->get('/admin/audit')
            ->assertOk()
            ->assertSee('publish');
    }

    public function test_laporan_rekap_per_jurusan(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
            'B' => ['nilai' => 80, 'p1' => 'J1'],
        ], kuota: ['J1' => 1], cadangan: ['J1' => 0]);
        app(RankingService::class)->proses($this->admin());
        app(PengumumanService::class)->terbitkan($this->admin()); // status diterapkan saat publish

        $this->actingAs($this->admin())->get('/admin/laporan')
            ->assertOk()
            ->assertSee('J1')
            ->assertViewHas('rekap', fn (array $rekap) => isset($rekap['J1']) && $rekap['J1']['terima'] === 1);
    }
}
