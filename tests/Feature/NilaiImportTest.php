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

    public function test_template_csv_terunduh(): void
    {
        $response = $this->actingAs($this->admin())
            ->get('/admin/nilai/import/template');

        $response->assertOk()->assertDownload('template-nilai.csv');

        $this->assertStringContainsString('nisn,rapor,prestasi,kunjungan', $response->getContent());
    }

    public function test_import_csv_valid_berhasil(): void
    {
        [$a, $b] = [$this->buatPendaftarLulus(), $this->buatPendaftarLulus()];
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},85,90,88\n{$b->nisn},80,70,60\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect()
            ->assertSessionHas('status', '2 nilai berhasil diimpor.');

        $this->assertSame(2, NilaiSeleksi::count());
    }

    public function test_import_csv_dengan_bom_dan_crlf_berhasil(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "\xEF\xBB\xBF" . "nisn,rapor,prestasi,kunjungan\r\n{$a->nisn},85,90,88\r\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect()
            ->assertSessionHas('status', '1 nilai berhasil diimpor.');
    }

    public function test_import_csv_lebih_1000_baris_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $baris = "nisn,rapor,prestasi,kunjungan\n" . str_repeat("{$a->nisn},85,90,88\n", 1001);

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $baris)])
            ->assertRedirect();

        $this->assertStringContainsString('Maksimal 1000 baris data', session('imporError')['pesan'] ?? '');
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_import_csv_desimal_koma_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        // "85,5" tanpa kutip → baris jadi 5 sel (lebih dari 4 kolom header) → ditolak
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},85,5,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $err = session('imporError')['baris'][0]['error'] ?? '';
        $this->assertMatchesRegularExpression('/(koma|titik|angka|kolom)/', $err);
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_import_csv_header_salah_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "nama,rapor\n{$a->nama_lengkap},85\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $this->assertStringContainsString('Header CSV', session('imporError')['pesan'] ?? '');
    }

    public function test_import_csv_kolom_tambahan_diabaikan(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "ket,nisn,rapor,prestasi,kunjungan\nok,{$a->nisn},85,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect()
            ->assertSessionHas('status', '1 nilai berhasil diimpor.');
    }

    public function test_import_diblokir_diluar_tahap_seleksi(): void
    {
        $this->tutupTahap(\App\Enums\TahapJadwal::SELEKSI);
        $csv = "nisn,rapor,prestasi,kunjungan\n1234567890,85,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertForbidden();
    }

    public function test_import_csv_setelah_publish_diberi_error_ramah(): void
    {
        $a = $this->buatPendaftarLulus();
        Hasil::factory()->for($a)->create(['status_akhir' => 'diterima']);
        app(PengumumanService::class)->terbitkan($this->admin());
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},85,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $this->assertNotEmpty(session('imporError')['pesan'] ?? '');
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_import_csv_nilai_lebih_100_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},150,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $this->assertSame('Nilai harus antara 0 dan 100.', session('imporError')['baris'][0]['error'] ?? '');
        $this->assertSame(0, NilaiSeleksi::count());
    }

    public function test_import_csv_nilai_tidak_angka_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},abc,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $this->assertSame('Tidak berupa angka.', session('imporError')['baris'][0]['error'] ?? '');
    }

    public function test_import_csv_nisn_tidak_10_digit_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "nisn,rapor,prestasi,kunjungan\n123456789,85,90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $this->assertSame('NISN harus 10 digit angka.', session('imporError')['baris'][0]['error'] ?? '');
    }

    public function test_import_csv_desimal_koma_terkutip_ditolak(): void
    {
        $a = $this->buatPendaftarLulus();
        $csv = "nisn,rapor,prestasi,kunjungan\n{$a->nisn},\"85,5\",90,88\n";

        $this->actingAs($this->admin())
            ->post('/admin/nilai/import', ['file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertRedirect();

        $this->assertSame('Desimal harus memakai titik (contoh: 85.5).', session('imporError')['baris'][0]['error'] ?? '');
    }
}
