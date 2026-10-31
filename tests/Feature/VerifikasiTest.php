<?php

namespace Tests\Feature;

use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bukaTahap(TahapJadwal::VERIFIKASI);
    }

    public function test_verifikasi_lulus_ubah_status_dan_mencatat_audit(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        $this->actingAs($this->admin())->post('/admin/verifikasi/'.$p->id.'/proses', ['aksi' => 'lulus']);

        $this->assertSame(StatusPendaftar::LULUS_VERIFIKASI, $p->fresh()->status);
        $this->assertSame(
            1,
            AuditLog::where('target', 'pendaftar:'.$p->id)->where('aksi', 'verifikasi_lulus')->count()
        );
    }

    public function test_gugur_wajib_alasan_dan_calon_siswa_melihatnya(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/'.$p->id.'/proses', ['aksi' => 'gugur'])
            ->assertSessionHasErrors('alasan');

        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/'.$p->id.'/proses', ['aksi' => 'gugur', 'alasan' => 'NIK tidak cocok']);

        $this->assertSame(StatusPendaftar::GUGUR, $p->fresh()->status);
        $this->actingAs($p->pengguna)->get('/status')->assertSee('NIK tidak cocok');
    }

    public function test_verifikasi_hanya_bisa_saat_tahap_verifikasi_buka(): void
    {
        $this->tutupTahap(TahapJadwal::VERIFIKASI);
        $p = $this->buatPendaftarTerverifikasi();

        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/'.$p->id.'/proses', ['aksi' => 'lulus'])
            ->assertForbidden();
    }

    // ---------- Redesign: verifikasi massal ----------

    public function test_batch_luluskan_banyak(): void
    {
        $p1 = $this->buatPendaftarTerverifikasi();
        $p2 = $this->buatPendaftarTerverifikasi();

        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/batch', ['aksi' => 'lulus', 'ids' => [$p1->id, $p2->id]]);

        $this->assertSame(StatusPendaftar::LULUS_VERIFIKASI, $p1->fresh()->status);
        $this->assertSame(StatusPendaftar::LULUS_VERIFIKASI, $p2->fresh()->status);
        $this->assertSame(
            2,
            AuditLog::where('aksi', 'verifikasi_lulus')->where('detail->massal', 1)->count()
        );
    }

    public function test_batch_gugur_satu_alasan_untuk_semua(): void
    {
        $ps = [$this->buatPendaftarTerverifikasi(), $this->buatPendaftarTerverifikasi()];

        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/batch', [
                'aksi' => 'gugur',
                'alasan' => 'Dokumen kabur',
                'ids' => array_column($ps, 'id'),
            ]);

        foreach ($ps as $p) {
            $f = $p->fresh();
            $this->assertSame(StatusPendaftar::GUGUR, $f->status);
            $this->assertSame('Dokumen kabur', $f->alasan_gugur);
        }
    }

    public function test_batch_gugur_tanpa_alasan_ditolak(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/batch', ['aksi' => 'gugur', 'ids' => [$p->id]])
            ->assertSessionHasErrors('alasan');

        $this->assertSame(StatusPendaftar::MENUNGGU_VERIFIKASI, $p->fresh()->status);
    }

    public function test_batch_ids_kosong_atau_sudah_diproses_tidak_memproses_apa_apa(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        // ids kosong → tidak ada yang diproses
        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/batch', ['aksi' => 'lulus', 'ids' => []]);
        $this->assertSame(StatusPendaftar::MENUNGGU_VERIFIKASI, $p->fresh()->status);

        // proses $p lewat jalur individual, lalu batch (id sudah-diproses + id tak dikenal)
        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/'.$p->id.'/proses', ['aksi' => 'lulus']);
        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/batch', ['aksi' => 'gugur', 'alasan' => 'x', 'ids' => [$p->id, 999999]])
            ->assertRedirect();

        $this->assertSame(StatusPendaftar::LULUS_VERIFIKASI, $p->fresh()->status);
        $this->assertSame(0, AuditLog::where('detail->massal', 1)->count());
    }

    public function test_batch_diluar_tahap_verifikasi_diblokir(): void
    {
        $this->tutupTahap(TahapJadwal::VERIFIKASI);
        $p = $this->buatPendaftarTerverifikasi();

        $this->actingAs($this->admin())
            ->post('/admin/verifikasi/batch', ['aksi' => 'lulus', 'ids' => [$p->id]])
            ->assertForbidden();
    }
}
