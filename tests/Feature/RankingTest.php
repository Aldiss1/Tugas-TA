<?php

namespace Tests\Feature;

use App\Enums\TahapJadwal;
use App\Exceptions\AdaPendaftarTanpaNilai;
use App\Exceptions\PengumumanSudahTerbit;
use App\Models\AuditLog;
use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Services\Psb\NilaiService;
use App\Services\Psb\PengumumanService;
use App\Services\Psb\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bukaTahap(TahapJadwal::PENGUMUMAN);
    }

    public function test_ranking_mengisi_kuota_per_jurusan_prioritas_1(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
            'P2' => ['nilai' => 90, 'p1' => 'J1'],
            'P3' => ['nilai' => 88, 'p1' => 'J2'],
        ], kuota: ['J1' => 2, 'J2' => 2], cadangan: ['J1' => 1, 'J2' => 1]);

        $r = app(RankingService::class)->proses($this->admin());

        $this->assertSame(3, $r['diterima']);
        $this->assertSame('diterima', Pendaftar::where('nama_lengkap', 'A')->first()->hasil->status_akhir);
    }

    public function test_overflow_prioritas_1_jatuh_ke_pilihan_2_hanya_sisa_kuota(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
            'B' => ['nilai' => 90, 'p1' => 'J1', 'p2' => 'J2'],
        ], kuota: ['J1' => 1, 'J2' => 1], cadangan: ['J1' => 0, 'J2' => 0]);

        app(RankingService::class)->proses($this->admin());

        $b = Pendaftar::where('nama_lengkap', 'B')->first();
        $this->assertSame('diterima', $b->hasil->status_akhir);
        $this->assertSame(2, (int) $b->hasil->via_pilihan);
        $this->assertSame(
            Jurusan::where('kode', 'J2')->value('id'),
            $b->hasil->jurusan_id
        );
    }

    public function test_setelah_kuota_cadangan_s_d_batas_sisanya_tidak_diterima(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
            'B' => ['nilai' => 90, 'p1' => 'J1'],
            'C' => ['nilai' => 80, 'p1' => 'J1'],
        ], kuota: ['J1' => 1], cadangan: ['J1' => 1]);

        app(RankingService::class)->proses($this->admin());

        $status = collect(['A', 'B', 'C'])
            ->map(fn ($n) => Pendaftar::where('nama_lengkap', $n)->first()->hasil->status_akhir);

        $this->assertSame(
            ['diterima', 'cadangan', 'tidak_diterima'],
            $status->values()->all()
        );
    }

    public function test_tie_break_total_sama_rapor_desc(): void
    {
        // Total sama 90: X rapor 85, Y rapor 90 → Y peringkat 1
        $this->buatSkenario([
            'X' => ['nilai' => 90, 'rapor' => 85, 'p1' => 'J1'],
            'Y' => ['nilai' => 90, 'rapor' => 90, 'p1' => 'J1'],
        ], kuota: ['J1' => 2], cadangan: ['J1' => 0]);

        app(RankingService::class)->proses($this->admin());

        $this->assertSame(1, (int) Pendaftar::where('nama_lengkap', 'Y')->first()->hasil->perankingan);
        $this->assertSame(2, (int) Pendaftar::where('nama_lengkap', 'X')->first()->hasil->perankingan);
    }

    public function test_proses_diblokir_jika_ada_pendaftar_lulus_tanpa_nilai(): void
    {
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1]);
        $this->buatPendaftarLulus(); // lulus verifikasi tanpa nilai

        $this->expectException(AdaPendaftarTanpaNilai::class);
        app(RankingService::class)->proses($this->admin());
    }

    public function test_nilai_dan_ranking_terkunci_setelah_publish(): void
    {
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1]);
        app(RankingService::class)->proses($this->admin());

        // proses ulang (sebelum publish) tidak memperbarui baris hasil lama
        app(RankingService::class)->proses($this->admin());
        $this->assertSame(1, Hasil::count());

        app(PengumumanService::class)->terbitkan($this->admin());

        $this->assertTrue(app(PengumumanService::class)->sudahTerbit());
        $a = Pendaftar::where('nama_lengkap', 'A')->first();

        try {
            app(NilaiService::class)->simpan($a->nilai, ['rapor' => 100, 'prestasi' => 0, 'kunjungan' => 0], $this->admin());
            $this->fail('Simpan nilai harus terblokir setelah publish');
        } catch (PengumumanSudahTerbit) {
            // ok
        }

        try {
            app(RankingService::class)->proses($this->admin());
            $this->fail('Proses ranking harus terblokir setelah publish');
        } catch (PengumumanSudahTerbit) {
            // ok
        }

        $this->assertSame(1, AuditLog::where('aksi', 'publish')->count());
        $this->assertSame(1, Hasil::count());
    }
}
