<?php

namespace Tests\Feature;

use App\Enums\JenisDokumen;
use App\Enums\StatusPendaftar;
use App\Exceptions\DokumenBelumLengkap;
use App\Exceptions\PendaftaranDitutup;
use App\Models\Dokumen;
use App\Models\Pendaftar;
use App\Models\User;
use App\Services\Psb\PendaftaranService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PendaftaranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_submit_lengkap_menerbitkan_nomor_dan_status_menunggu_verifikasi(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');
        $this->bukaPendaftaran();

        $u = User::factory()->create();
        $p = app(PendaftaranService::class)->simpanDraf($u, $this->dataLengkap());
        foreach (JenisDokumen::cases() as $jenis) {
            Dokumen::factory()->create(['pendaftar_id' => $p->id, 'jenis' => $jenis]);
        }
        $p = app(PendaftaranService::class)->kirim($p);

        $this->assertSame('PSB-2026-000001', $p->nomor_pendaftaran);
        $this->assertSame(StatusPendaftar::MENUNGGU_VERIFIKASI, $p->status);
    }

    public function test_submit_ditolak_jika_periode_pendaftaran_ditutup(): void
    {
        $this->bukaPendaftaran(mulai: now()->subDay(), selesai: now()->addMinute());

        $u = User::factory()->create();
        $p = app(PendaftaranService::class)->simpanDraf($u, $this->dataLengkap());

        Carbon::setTestNow(now()->addMinutes(5)); // lewat saat submit

        $this->expectException(PendaftaranDitutup::class);
        app(PendaftaranService::class)->kirim($p);
    }

    public function test_nisn_duplikat_menghasilkan_pesan_ramah_bukan_500(): void
    {
        $this->bukaPendaftaran();
        Pendaftar::factory()->create(['pengguna_id' => null]);

        $u = User::factory()->create();
        $p = app(PendaftaranService::class)->simpanDraf($u, $this->dataLengkap(nisn: Pendaftar::first()->nisn));

        $r = $this->actingAs($u)->post('/pendaftaran/kirim', ['pendaftar_id' => $p->id]);

        $r->assertSessionHasErrors('nisn');
        $this->assertNotSame(500, $r->status());
    }

    public function test_file_lebih_2mb_ditolak_pdf_berisi_binary_gambar_ditolak_jpg_kecil_diterima(): void
    {
        $p = $this->buatPendaftarDraf();

        // jpg kecil diterima
        $this->actingAs($p->pengguna)
            ->post('/pendaftaran/dokumen', [
                'jenis' => 'ktp',
                'file' => UploadedFile::fake()->image('ktp.jpg'),
            ])->assertSessionHasNoErrors();

        // 3MB ditolak
        $this->actingAs($p->pengguna)
            ->post('/pendaftaran/dokumen', [
                'jenis' => 'kk',
                'file' => UploadedFile::fake()->create('kk.pdf', 3072, 'application/pdf'),
            ])->assertSessionHasErrors('file');

        // .pdf yang isinya binary JPEG ditolak (cek MIME)
        $tmp = tempnam(sys_get_temp_dir(), 'psb_mime').'.pdf';
        try {
            imagecreate(10, 10);
            imagejpeg(imagecreate(10, 10), $tmp);
            $this->actingAs($p->pengguna)
                ->post('/pendaftaran/dokumen', [
                    'jenis' => 'ijazah',
                    'file' => new UploadedFile($tmp, 'ijazah.pdf', null, null, true),
                ])->assertSessionHasErrors('file');
        } finally {
            @unlink($tmp);
        }
    }

    public function test_submit_ditolak_jika_dokumen_wajib_belum_terunggah(): void
    {
        $this->bukaPendaftaran();
        $p = $this->buatPendaftarDraf(); // tanpa dokumen

        $this->expectException(DokumenBelumLengkap::class);
        app(PendaftaranService::class)->kirim($p);
    }
}
