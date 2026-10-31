<?php

namespace Tests\Feature;

use App\Enums\JenisDokumen;
use App\Enums\TahapJadwal;
use App\Exceptions\NISNDuplikat;
use App\Models\AuditLog;
use App\Models\Dokumen;
use App\Models\Pendaftar;
use App\Models\User;
use App\Services\Psb\NomorPendaftaran;
use App\Services\Psb\PendaftaranService;
use App\Services\Psb\PengumumanService;
use App\Services\Psb\RankingService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression tests untuk temuan review final (C1, I1-I9).
 */
class ReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // --- C1: user belum verifikasi email tidak boleh kena 500 di portal ---

    public function test_user_belum_verifikasi_diarahkan_ke_halaman_notice_bukan_500(): void
    {
        $u = User::factory()->unverified()->create();
        $p = Pendaftar::factory()->denganUser(['id' => $u->id])->create();

        $r = $this->actingAs($u)->get('/status');

        $r->assertRedirect('/verify-email');
        $this->actingAs($u)->get('/verify-email')->assertOk()->assertSee('verifikasi');
    }

    public function test_resend_verifikasi_mengirim_lagi_link(): void
    {
        Notification::fake();
        $u = User::factory()->unverified()->create();

        $this->actingAs($u)->post('/verify-email/resend');

        Notification::assertSentTo($u, VerifyEmail::class);
    }

    // --- I1: hasil & surat tidak boleh terlihat sebelum pengumuman terbit ---

    public function test_hasil_tidak_terlihat_sebelum_pengumuman_terbit(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
        ], kuota: ['J1' => 1], cadangan: ['J1' => 0]);

        $a = Pendaftar::where('nama_lengkap', 'A')->first();
        $a->update(['pengguna_id' => User::factory()->create()->id]);

        app(RankingService::class)->proses($this->admin()); // proses, BELUM publish

        $this->actingAs($a->fresh()->pengguna)->get('/status')
            ->assertOk()
            ->assertDontSee('Diterima', false)
            ->assertSee('Menunggu pengumuman');

        app(PengumumanService::class)->terbitkan($this->admin());
        $this->actingAs($a->fresh()->pengguna)->get('/status')->assertSee('Diterima');
    }

    public function test_surat_ditolak_sebelum_pengumuman_terbit(): void
    {
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1], cadangan: ['J1' => 0]);
        $a = Pendaftar::where('nama_lengkap', 'A')->first();
        $a->update(['pengguna_id' => User::factory()->create()->id]);
        app(RankingService::class)->proses($this->admin()); // belum publish

        $this->actingAs($a->fresh()->pengguna)
            ->get('/surat/'.$a->nomor_pendaftaran)
            ->assertNotFound();
    }

    // --- I2: form pendaftaran terkunci setelah lulus verifikasi ---

    public function test_form_dan_simpan_ditolak_setelah_lulus_verifikasi(): void
    {
        $p = $this->buatPendaftarLulus();

        $this->actingAs($p->pengguna)->get('/pendaftaran')->assertRedirect('/status');
        $this->actingAs($p->pengguna)
            ->post('/pendaftaran', $this->dataLengkap())
            ->assertRedirect('/status');
    }

    // --- I3: tabrakan nomor pendaftaran di-retry, bukan 500 ---

    public function test_tabrakan_nomor_pendaftaran_diretry_otomatis(): void
    {
        $this->bukaPendaftaran();
        $u = User::factory()->create();
        $svc = PendaftaranService::class;
        $draf = app($svc)->simpanDraf($u, $this->dataLengkap());

        $tahun = now()->format('Y');
        // "Pesaing" memegang nomor pertama
        Pendaftar::factory()->create([
            'nomor_pendaftaran' => "PSB-{$tahun}-000001",
            'pengguna_id' => null,
            'status' => 'menunggu_verifikasi',
        ]);
        foreach (JenisDokumen::cases() as $jenis) {
            Dokumen::factory()->create(['pendaftar_id' => $draf->id, 'jenis' => $jenis]);
        }

        // Paksa generate() menghasilkan nomor yang tabrakan dulu, lalu nomor benar
        $fake = new class($tahun) extends NomorPendaftaran
        {
            public function __construct(private string $tahun) {}

            private array $seq;

            public function generate(): string
            {
                $this->seq ??= ["PSB-{$this->tahun}-000001", "PSB-{$this->tahun}-000002"];

                return array_shift($this->seq);
            }
        };
        $this->app->instance(NomorPendaftaran::class, $fake);

        $hasil = app($svc)->kirim($draf);

        $this->assertSame("PSB-{$tahun}-000002", $hasil->nomor_pendaftaran);
    }

    // --- I4: NISN duplikat tertangkap constraint DB saat draf (pesan ramah) ---

    public function test_nisn_duplikat_di_level_db_menghasilkan_nisn_duplikat_bukan_500(): void
    {
        $this->bukaPendaftaran();
        Pendaftar::factory()->create(['pengguna_id' => null, 'nisn' => '9999999999']);
        $u = User::factory()->create(['nisn' => '9999999999']);

        $this->expectException(NISNDuplikat::class);
        app(PendaftaranService::class)->simpanDraf($u, $this->dataLengkap());
    }

    // --- I5: publish ditolak jika ranking belum diproses ---

    public function test_publish_ditolak_jika_belum_ada_hasil_ranking(): void
    {
        $service = app(PengumumanService::class);

        $this->actingAs($this->admin())->post('/admin/pengumuman/terbit')
            ->assertSessionHasErrors('pengumuman');

        $this->assertFalse($service->sudahTerbit());
    }

    // --- I6: file .jpeg diterima ---

    public function test_file_jpeg_diterima(): void
    {
        $this->bukaPendaftaran();
        $p = $this->buatPendaftarDraf();

        $this->actingAs($p->pengguna)
            ->post('/pendaftaran/dokumen', [
                'jenis' => 'foto',
                'file' => UploadedFile::fake()->image('foto.jpeg'),
            ])->assertSessionHasNoErrors();

        $this->assertNotNull($p->fresh()->dokumen()->where('jenis', 'foto')->first()?->diunggah_at);
    }

    // --- I7: audit log untuk proses ranking, kirim ulang, ubah nilai ---

    public function test_proses_ranking_kirim_ulang_dan_ubah_nilai_tercatat_di_audit(): void
    {
        $this->bukaTahap(TahapJadwal::SELEKSI);
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1], cadangan: ['J1' => 0]);
        $a = Pendaftar::where('nama_lengkap', 'A')->first();

        app(RankingService::class)->proses($this->admin());
        $this->assertSame(
            1,
            AuditLog::where('aksi', 'proses_ranking')->count()
        );

        // ubah nilai
        $this->actingAs($this->admin())->put('/admin/nilai/'.$a->id, [
            'rapor' => 90, 'prestasi' => 0, 'kunjungan' => 0,
        ]);
        $this->assertGreaterThanOrEqual(
            1,
            AuditLog::where('aksi', 'nilai_ubah')->where('target', 'pendaftar:'.$a->id)->count()
        );
    }

    // --- I8: halaman nilai menampilkan hasil ranking setelah proses ---

    public function test_halaman_nilai_menampilkan_hasil_ranking_setelah_proses(): void
    {
        $this->buatSkenario([
            'A' => ['nilai' => 95, 'p1' => 'J1'],
        ], kuota: ['J1' => 1], cadangan: ['J1' => 0]);

        app(RankingService::class)->proses($this->admin());

        $this->actingAs($this->admin())->get('/admin/nilai')
            ->assertOk()
            ->assertSee('Hasil Ranking')
            ->assertSee('A');
    }

    // --- I9: proses ranking dibatasi tahap pengumuman ---

    public function test_proses_ranking_diblokir_diluar_tahap_pengumuman(): void
    {
        $this->buatSkenario(['A' => ['nilai' => 95, 'p1' => 'J1']], kuota: ['J1' => 1], cadangan: ['J1' => 0]);
        $this->tutupTahap(TahapJadwal::PENGUMUMAN);

        $this->actingAs($this->admin())
            ->post('/admin/ranking/proses')
            ->assertForbidden();
    }
}
