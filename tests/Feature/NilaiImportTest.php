<?php

namespace Tests\Feature;

use App\Exceptions\PengumumanSudahTerbit;
use App\Models\AuditLog;
use App\Models\Hasil;
use App\Models\NilaiSeleksi;
use App\Models\Pendaftar;
use App\Services\Psb\NilaiService;
use App\Services\Psb\PengumumanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NilaiImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bukaTahap(\App\Enums\TahapJadwal::SELEKSI);
    }

    private function baris(Pendaftar $p, float $r = 85, float $pr = 90, float $k = 88, int $no = 2): array
    {
        return ['nisn' => $p->nisn, 'rapor' => $r, 'prestasi' => $pr, 'kunjungan' => $k, 'baris' => $no];
    }

    public function test_impor_valid_menyimpan_semua_dan_mencatat_audit(): void
    {
        [$a, $b] = [$this->buatPendaftarLulus(), $this->buatPendaftarLulus()];
        $service = app(NilaiService::class);

        $r = $service->impor([$this->baris($a), $this->baris($b)], $this->admin());

        $this->assertTrue($r['ok']);
        $this->assertSame(2, $r['tersimpan']);
        $this->assertSame(2, NilaiSeleksi::count());
        $this->assertSame($service->hitungTotal(85, 90, 88), (float) $a->nilai->total);
        $this->assertSame(1, AuditLog::where('aksi', 'nilai_impor')->count());
    }

    public function test_impor_all_or_nothing_jika_ada_baris_rusak(): void
    {
        $a = $this->buatPendaftarLulus();

        $r = app(NilaiService::class)->impor([
            $this->baris($a),
            ['nisn' => '0000000000', 'rapor' => 80, 'prestasi' => 80, 'kunjungan' => 80, 'baris' => 3],
        ]);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Pendaftar tidak ditemukan', $r['baris'][0]['error']);
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_impor_ditolak_jika_nisn_bukan_lulus_verifikasi(): void
    {
        $p = Pendaftar::factory()->denganUser()->menungguVerifikasi()->create();

        $r = app(NilaiService::class)->impor([$this->baris($p)]);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('lulus verifikasi', $r['baris'][0]['error']);
    }

    public function test_impor_ditolak_jika_nisn_duplikat_dalam_file(): void
    {
        $a = $this->buatPendaftarLulus();

        $r = app(NilaiService::class)->impor([$this->baris($a, 80, 80, 80, 2), $this->baris($a, 90, 90, 90, 3)]);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('duplikat', $r['baris'][0]['error']);
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_impor_ditolak_setelah_pengumuman_terbit(): void
    {
        $a = $this->buatPendaftarLulus();
        Hasil::factory()->for($a)->create(['status_akhir' => 'diterima']);
        app(PengumumanService::class)->terbitkan($this->admin());

        $this->expectException(PengumumanSudahTerbit::class);
        app(NilaiService::class)->impor([$this->baris($a)]);
    }
}
