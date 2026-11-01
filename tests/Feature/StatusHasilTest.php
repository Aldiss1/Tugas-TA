<?php

namespace Tests\Feature;

use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\Kuota;
use App\Models\Pendaftar;
use App\Services\Psb\PengumumanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusHasilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Kuota::updateOrCreate(
            ['jurusan_id' => Jurusan::where('kode', 'TKJ')->value('id')],
            ['kuota' => 28, 'cadangan' => 3, 'terisi' => 3]
        );
    }

    private function pendaftarHasil(string $statusAkhir, ?int $perankingan = 3, ?int $via = 1, bool $terbit = true): Pendaftar
    {
        $p = Pendaftar::factory()
            ->denganUser()
            ->lulusVerifikasi()
            ->denganNilai(85, 90, 88)
            ->create();

        Hasil::factory()->for($p)->create([
            'status_akhir' => $statusAkhir,
            'jurusan_id' => $perankingan ? Jurusan::where('kode', 'TKJ')->value('id') : null,
            'perankingan' => $perankingan,
            'via_pilihan' => $via,
        ]);

        if ($terbit) {
            app(PengumumanService::class)->terbitkan($this->admin());
        }

        return $p;
    }

    public function test_diterima_melihat_rincian_lengkap(): void
    {
        $p = $this->pendaftarHasil('diterima', 3, 1);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Diterima')
            ->assertSee('Peringkat 3')
            ->assertSee('via pilihan P1')
            ->assertSee('Kuota 28')
            ->assertSee('85')
            ->assertSee('90')
            ->assertSee('88')
            ->assertSee('87.4');
    }

    public function test_cadangan_melihat_label_cadangan(): void
    {
        $p = $this->pendaftarHasil('cadangan', 30, 1);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Cadangan')
            ->assertSee('bg-amber-50')
            ->assertSee('Peringkat 30');
    }

    public function test_tidak_diterima_tanpa_jurusan_render_aman(): void
    {
        $p = $this->pendaftarHasil('tidak_diterima', null, null);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Tidak Diterima')
            ->assertDontSee('Peringkat')
            ->assertDontSee('Kuota');
    }

    public function test_hasil_yang_belum_terbit_tidak_tampil(): void
    {
        $p = $this->pendaftarHasil('diterima', 3, 1, terbit: false);

        $this->actingAs($p->pengguna)->get('/status')
            ->assertOk()
            ->assertSee('Menunggu pengumuman')
            ->assertDontSee('Peringkat');
    }

    public function test_pendaftar_tanpa_hasil_tidak_melihat_kartu_hasil(): void
    {
        $tanpaHasil = Pendaftar::factory()
            ->denganUser()
            ->lulusVerifikasi()
            ->denganNilai(85, 90, 88)
            ->create();

        // Pengumuman terbit lewat hasil pendaftar lain
        $denganHasil = Pendaftar::factory()->denganUser()->lulusVerifikasi()->create();
        Hasil::factory()->for($denganHasil)->create([
            'status_akhir' => 'diterima',
            'jurusan_id' => Jurusan::where('kode', 'TKJ')->value('id'),
            'perankingan' => 1,
            'via_pilihan' => 1,
        ]);
        app(PengumumanService::class)->terbitkan($this->admin());

        $this->actingAs($tanpaHasil->pengguna)->get('/status')
            ->assertOk()
            ->assertDontSee('Hasil Seleksi');
    }

    public function test_cek_status_publik_tidak_memuat_skor(): void
    {
        // Catatan: halaman publik menampilkan status + jurusan + peringkat (fitur
        // existing, di luar scope plan ini). Yang dikunci di sini: TIDAK ada skor.
        $p = $this->pendaftarHasil('diterima', 3, 1);

        $this->post('/cek-status', [
            'nomor' => $p->nomor_pendaftaran,
            'tgl_lahir' => $p->tanggal_lahir->format('Y-m-d'),
        ])
            ->assertOk()
            ->assertDontSee('87.4')
            ->assertDontSee('Kunjungan');
    }
}
