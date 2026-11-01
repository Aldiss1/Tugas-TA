<?php

namespace Tests\Feature;

use App\Models\Pendaftar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard regresi string UI (§5 design spec) — memastikan string kunci
#  pada tiap halaman tidak hilang saat UI di-touch.
 */
class UiStringGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_portal_form_pendaftaran_strings(): void
    {
        $u = User::factory()->create();
        $r = $this->actingAs($u)->get('/pendaftaran');
        $r->assertOk()
            ->assertSee('Formulir Pendaftaran')
            ->assertSee('Data Diri')
            ->assertSee('Orang Tua / Wali')
            ->assertSee('Pilihan Jurusan')
            ->assertSee('Dokumen Persyaratan')
            ->assertSee('Simpan Draf')
            ->assertSee('Kirim Pendaftaran')
            ->assertSee('Kemajuan')
            ->assertSee('Belum');
    }

    public function test_portal_status_empty_state_strings(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/status')
            ->assertOk()
            ->assertSee('Belum Ada Pendaftaran')
            ->assertSee('Mulai Pendaftaran');
    }

    public function test_admin_nilai_strings(): void
    {
        $this->actingAs($this->admin())->get('/admin/nilai')
            ->assertOk()
            ->assertSee('Nilai & Ranking')
            ->assertSee('Proses Ranking')
            ->assertSee('Input Nilai (pendaftar lulus verifikasi)')
            ->assertSee('Simpan Semua')
            ->assertSee('Batal')
            ->assertSee('Belum ada pendaftar lulus verifikasi');
    }

    public function test_admin_verifikasi_strings(): void
    {
        Pendaftar::factory()->denganUser()->menungguVerifikasi()->create();
        $this->actingAs($this->admin())->get('/admin/verifikasi')
            ->assertOk()
            ->assertSee('Verifikasi Dokumen')
            ->assertSee('Luluskan Terpilih')
            ->assertSee('Gugurkan Terpilih')
            ->assertSee('Bersihkan')
            ->assertSee('Alasan (wajib)')
            ->assertSee('Ya, gugur')
            ->assertSee('Gugurkan', false);
    }

    public function test_admin_pengumuman_laporan_jadwal_audit_jurusan_strings(): void
    {
        $this->actingAs($this->admin())->get('/admin/pengumuman')
            ->assertOk()
            ->assertSee('Pengumuman PSB')
            ->assertSee('Terbitkan Pengumuman')
            ->assertSee('Rekap per Jurusan')
            ->assertSee('DITERIMA')
            ->assertSee('CADANGAN')
            ->assertSee('TIDAK DITERIMA');

        $this->actingAs($this->admin())->get('/admin/laporan')
            ->assertOk()
            ->assertSee('Laporan')
            ->assertSee('Rekap per Jurusan')
            ->assertSee('Ekspor CSV')
            ->assertSee('Ekspor Excel');

        $this->actingAs($this->admin())->get('/admin/jadwal')
            ->assertOk()
            ->assertSee('Jadwal PSB')
            ->assertSee('Simpan Jadwal')
            ->assertSee('Pendaftaran')
            ->assertSee('Seleksi');

        $this->actingAs($this->admin())->get('/admin/audit')
            ->assertOk()
            ->assertSee('Audit Log');

        $this->actingAs($this->admin())->get('/admin/jurusan')
            ->assertOk()
            ->assertSee('Jurusan & Kuota')
            ->assertSee('Tambah Jurusan');

        $this->actingAs($this->admin())->get('/admin/pendaftar')
            ->assertOk()
            ->assertSee('Data Pendaftar')
            ->assertSee('Input Manual')
            ->assertSee('Semua status');
    }
}
