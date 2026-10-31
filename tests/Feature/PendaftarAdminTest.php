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

    public function test_form_input_manual_render(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/pendaftar/create')
            ->assertOk()
            ->assertSee('Input Pendaftar Manual')
            ->assertSee('NISN');
    }

    public function test_input_manual_dengan_file_dokumen(): void
    {
        $this->bukaPendaftaran();
        $this->actingAs($this->admin())->post('/admin/pendaftar', [
            'nisn' => '2222333344',
            'nama_lengkap' => 'Candra Manual',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2008-07-07',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'asal_sekolah' => 'SMPN 2 Singosari',
            'alamat' => 'Jl. Manual 2',
            'nik' => '3201070708080002',
            'nama_orang_tua' => 'Bapak Candra',
            'pekerjaan_orang_tua' => 'Wiraswasta',
            'telepon_orang_tua' => '0812999',
            'pilihan_1' => Jurusan::where('kode', 'TKJ')->value('id'),
            'dokumen_ktp' => \Illuminate\Http\UploadedFile::fake()->create('ktp.pdf', 10, 'application/pdf'),
        ]);

        $p = Pendaftar::where('nisn', '2222333344')->first();
        $this->assertNotNull($p);
        $this->assertSame('manual', $p->sumber);
        $this->assertMatchesRegularExpression('/^PSB-\d{4}-\d{6}$/', $p->nomor_pendaftaran);
        $this->assertSame(1, $p->dokumen()->where('jenis', 'ktp')->whereNotNull('diunggah_at')->count());
    }
}
