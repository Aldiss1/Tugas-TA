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
}
