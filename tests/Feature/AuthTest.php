<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_membuat_akun_calon_siswa_aktiflangsung(): void
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

        $u = User::where('email', 'budi@mail.com')->first();
        $this->assertNotNull($u);
        $this->assertSame('calon_siswa', $u->role);
    }

    public function test_register_menolak_nisn_duplikat(): void
    {
        User::factory()->create(['nisn' => '1234567890']);
        $this->post('/register', [
            'name' => 'Cici',
            'email' => 'cici@mail.com',
            'nisn' => '1234567890',
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
        ])->assertSessionHasErrors('nisn');
    }

    public function test_3x_login_salah_mengunci_akun_15_menit(): void
    {
        $u = User::factory()->create(); // password polos: 'password'

        foreach (range(1, 3) as $i) {
            $this->post('/login', ['email' => $u->email, 'password' => 'salah']);
        }

        $this->post('/login', ['email' => $u->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
    }

    public function test_login_calon_siswa_ke_status_admin_ke_dashboard(): void
    {
        $cs = User::factory()->create();
        $ad = User::factory()->admin()->create();

        $this->post('/login', ['email' => $cs->email, 'password' => 'password'])
            ->assertRedirect('/status');
        $this->post('/logout');

        $this->post('/login', ['email' => $ad->email, 'password' => 'password'])
            ->assertRedirect('/admin');
    }
}
