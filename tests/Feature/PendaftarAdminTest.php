<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Pendaftar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendaftarAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_filter_status_jurusan_dan_teks_bekerja(): void
    {
        $this->buatDataPendaftar(20);

        $this->actingAs($this->admin())->get('/admin/pendaftar?status=lulus_verifikasi')->assertOk();
        $this->actingAs($this->admin())
            ->get('/admin/pendaftar?jurusan='.Jurusan::where('kode', 'TKJ')->value('id').'&q=Pendaftar')
            ->assertOk()
            ->assertSee('Pendaftar');
    }

    public function test_input_manual_menerbitkan_nomor_sumber_manual_nisn_unik_dicek(): void
    {
        $this->bukaPendaftaran();

        $this->actingAs($this->admin())->post('/admin/pendaftar', $this->dataManualLengkap());

        $p = Pendaftar::latest('id')->first();
        $this->assertSame('manual', $p->sumber);
        $this->assertMatchesRegularExpression('/^PSB-\d{4}-\d{6}$/', $p->nomor_pendaftaran);
        $this->assertNull($p->pengguna_id);

        $this->actingAs($this->admin())
            ->post('/admin/pendaftar', $this->dataManualLengkap(nisn: $p->nisn))
            ->assertSessionHasErrors('nisn');
    }

    public function test_preview_dokumen_admin_200_calon_siswa_403(): void
    {
        $p = $this->buatPendaftarTerverifikasi();

        // siapkan file dummy agar streaming berhasil
        $dok = $p->dokumen()->first();
        Storage::disk('local')->put($dok->path, '%PDF-1.4 dummy');

        $this->actingAs($this->admin())
            ->get('/admin/pendaftar/'.$p->id.'/dokumen/ktp')
            ->assertOk();

        $this->actingAs($p->pengguna)
            ->get('/admin/pendaftar/'.$p->id.'/dokumen/ktp')
            ->assertForbidden();
    }
}
