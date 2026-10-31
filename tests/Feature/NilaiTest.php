<?php

namespace Tests\Feature;

use App\Enums\TahapJadwal;
use App\Models\AuditLog;
use App\Models\NilaiSeleksi;
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

    public function test_batch_simpan_dua_baris_satu_kali(): void
    {
        $p1 = $this->buatPendaftarLulus();
        $p2 = $this->buatPendaftarLulus();

        $r = $this->actingAs($this->admin())->post('/admin/nilai/batch', [
            'rows' => [
                ['id' => $p1->id, 'rapor' => 80, 'prestasi' => 90, 'kunjungan' => 100],
                ['id' => $p2->id, 'rapor' => 70, 'prestasi' => 70, 'kunjungan' => 70],
            ],
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $r->assertOk();
        $this->assertSame(2, $r->json('tersimpan'));
        $this->assertSame(89.0, (float) $p1->fresh()->nilai->total);
        $this->assertSame(70.0, (float) $p2->fresh()->nilai->total);
        $this->assertSame(2, AuditLog::whereIn('aksi', ['nilai_input', 'nilai_ubah'])->count());
        $this->assertSame(0, NilaiSeleksiRiwayat::count()); // baru pertama kali diisi
    }

    public function test_batch_ubah_nilai_membuat_riwayat(): void
    {
        $p = $this->buatPendaftarLulus();
        NilaiSeleksi::factory()->for($p)->create([
            'nilai_rapor' => 80, 'nilai_prestasi' => 90, 'nilai_kunjungan' => 100, 'total' => 89.0,
        ]);

        $this->actingAs($this->admin())->post('/admin/nilai/batch', [
            'rows' => [['id' => $p->id, 'rapor' => 85, 'prestasi' => 90, 'kunjungan' => 100]],
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $n = $p->fresh()->nilai;
        $this->assertSame(91.0, (float) $n->total); // 0.4x85 + 0.3x90 + 0.3x100
        $this->assertSame(1, NilaiSeleksiRiwayat::where('nilai_seleksi_id', $n->id)->count());
    }

    public function test_batch_nilai_invalid_ditolak_per_baris(): void
    {
        $p1 = $this->buatPendaftarLulus();
        $p2 = $this->buatPendaftarLulus();

        $r = $this->actingAs($this->admin())->post('/admin/nilai/batch', [
            'rows' => [
                ['id' => $p1->id, 'rapor' => 150, 'prestasi' => 0, 'kunjungan' => 0],
                ['id' => 999999, 'rapor' => 80, 'prestasi' => 0, 'kunjungan' => 0],
                ['id' => $p2->id, 'rapor' => 80, 'prestasi' => 80, 'kunjungan' => 80],
            ],
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $r->assertStatus(422);
        $this->assertFalse($r->json('ok'));
        $this->assertSame(2, count($r->json('baris'))); // 2 baris bermasalah
        $this->assertNull($p2->fresh()->nilai); // all-or-nothing: baris valid tidak disimpan
    }

    public function test_batch_tidak_membuat_riwayat_bila_nilai_tidak_berubah(): void
    {
        $p = $this->buatPendaftarLulus();
        NilaiSeleksi::factory()->for($p)->create([
            'nilai_rapor' => 80, 'nilai_prestasi' => 90, 'nilai_kunjungan' => 100, 'total' => 89.0,
        ]);

        $this->actingAs($this->admin())->post('/admin/nilai/batch', [
            'rows' => [['id' => $p->id, 'rapor' => 80, 'prestasi' => 90, 'kunjungan' => 100]],
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $this->assertSame(0, NilaiSeleksiRiwayat::count());
        $this->assertSame(0, AuditLog::where('aksi', 'nilai_ubah')->count());
    }

    public function test_route_put_nilai_lama_dihapus(): void
    {
        $p = $this->buatPendaftarLulus();
        $this->actingAs($this->admin())
            ->put('/admin/nilai/'.$p->id, ['rapor' => 1, 'prestasi' => 1, 'kunjungan' => 1])
            ->assertNotFound();
    }

    public function test_batch_diluar_tahap_seleksi_diblokir(): void
    {
        $this->tutupTahap(TahapJadwal::SELEKSI);
        $p = $this->buatPendaftarLulus();

        $this->actingAs($this->admin())
            ->post('/admin/nilai/batch', ['rows' => [['id' => $p->id, 'rapor' => 80, 'prestasi' => 80, 'kunjungan' => 80]]])
            ->assertForbidden();
    }

    public function test_batch_pendaftar_belum_lulus_ditolak(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        $r = $this->actingAs($this->admin())->post('/admin/nilai/batch', [
            'rows' => [['id' => $p->id, 'rapor' => 80, 'prestasi' => 0, 'kunjungan' => 0]],
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $r->assertStatus(422);
        $this->assertNull($p->fresh()->nilai);
    }
}
