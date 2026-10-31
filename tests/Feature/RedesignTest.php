<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test redesign UX 2026-10-31 (terpusat di file ini, ditambah per task).
 */
class RedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ---------- Task 1: auth tanpa verifikasi email ----------

    public function test_register_aktiflangsung_otomatis_login_ke_form(): void
    {
        $r = $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@mail.com',
            'nisn' => '1234567890',
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
        ]);
        $r->assertRedirect('/pendaftaran');
        $this->assertAuthenticated();
        $this->assertSame('calon_siswa', User::where('email', 'budi@mail.com')->first()->role);
    }

    public function test_user_belum_verifikasi_tetap_bisa_buka_portal(): void
    {
        $u = User::factory()->unverified()->create();
        $this->actingAs($u)->get('/status')->assertOk();
        $this->actingAs($u)->get('/pendaftaran')->assertOk();
    }

    public function test_status_guest_diarahkan_login(): void
    {
        $this->get('/status')->assertRedirect('/login');
    }

    public function test_route_verifikasi_email_dihapus(): void
    {
        $u = User::factory()->unverified()->create();
        $this->actingAs($u)->get('/verify-email')->assertNotFound();
        $this->actingAs($u)->get('/verify-email/abc')->assertNotFound();
    }

    // ---------- Task 2: form 1 halaman + upload XHR ----------

    public function test_form_render_untuk_user_baru(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/pendaftaran')
            ->assertOk()
            ->assertSee('Pilihan Jurusan')
            ->assertSee('Data Diri')
            ->assertSee('Kemajuan');
    }

    public function test_upload_xhr_berhasil_json(): void
    {
        $this->bukaPendaftaran();
        $p = $this->buatPendaftarDraf();

        $r = $this->actingAs($p->pengguna)->post('/pendaftaran/dokumen', [
            'jenis' => 'ktp',
            'file' => \Illuminate\Http\UploadedFile::fake()->image('ktp.jpg'),
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $r->assertOk();
        $this->assertSame(['ok' => true, 'jenis' => 'ktp'], collect($r->json())->only('ok', 'jenis')->all());
    }

    public function test_upload_xhr_3mb_ditolak_json(): void
    {
        $this->bukaPendaftaran();
        $p = $this->buatPendaftarDraf();

        $r = $this->actingAs($p->pengguna)->post('/pendaftaran/dokumen', [
            'jenis' => 'kk',
            'file' => \Illuminate\Http\UploadedFile::fake()->create('kk.pdf', 3072, 'application/pdf'),
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $r->assertStatus(422);
        $this->assertFalse($r->json('ok'));
    }

    public function test_upload_xhr_mime_spoof_ditolak_json(): void
    {
        $this->bukaPendaftaran();
        $p = $this->buatPendaftarDraf();
        $tmp = tempnam(sys_get_temp_dir(), 'psb').'.pdf';
        imagejpeg(\imagecreate(10, 10), $tmp);
        try {
            $r = $this->actingAs($p->pengguna)->post('/pendaftaran/dokumen', [
                'jenis' => 'ijazah',
                'file' => new \Illuminate\Http\UploadedFile($tmp, 'ijazah.pdf', null, null, true),
            ], ['X-Requested-With' => 'XMLHttpRequest']);
            $r->assertStatus(422);
            $this->assertFalse($r->json('ok'));
        } finally {
            @unlink($tmp);
        }
    }
}
