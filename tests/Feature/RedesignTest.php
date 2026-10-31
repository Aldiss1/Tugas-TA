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
}
