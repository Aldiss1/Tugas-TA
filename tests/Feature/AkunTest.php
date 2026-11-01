<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/akun')->assertRedirect('/login');
    }

    public function test_calon_siswa_melihat_data_akun(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/akun')
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_ganti_password_ditolak_jika_password_lama_salah(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/akun/password', [
            'password_lama' => 'salah',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertSessionHasErrors('password_lama');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_ganti_password_berhasil_memaksa_login_ulang(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/akun/password', [
            'password_lama' => 'password',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('passwordbaru1', $user->fresh()->password));

        $this->post('/login', ['email' => $user->email, 'password' => 'passwordbaru1'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_password_baru_kurang_8_karakter_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/akun/password', [
            'password_lama' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');
    }

    public function test_konfirmasi_password_tidak_sama_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/akun/password', [
            'password_lama' => 'password',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbeda2',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
