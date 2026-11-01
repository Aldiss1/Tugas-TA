<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_lupa_password_terbuka(): void
    {
        $this->get('/lupa-password')
            ->assertOk()
            ->assertViewIs('auth.forgot-password');
    }

    public function test_halaman_reset_password_terbuka_dengan_token(): void
    {
        $this->get('/reset-password/token-apa-saja?email=calon@mail.com')
            ->assertOk()
            ->assertViewIs('auth.reset-password');
    }

    public function test_kirim_tautan_reset_ke_email_terdaftar(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/lupa-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_kirim_tautan_reset_email_tidak_terdaftar_ditolak(): void
    {
        Notification::fake();

        $this->post('/lupa-password', ['email' => 'tidak-ada@mail.com'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_reset_password_dengan_token_valid(): void
    {
        [$user, $token] = $this->buatUserDanAmbilTokenReset();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password_baru1',
            'password_confirmation' => 'password_baru1',
        ])->assertRedirect(route('login'));

        // Password lama tidak bisa dipakai lagi
        $this->post('/login', ['email' => $user->email, 'password' => $user->password])
            ->assertSessionHasErrors('email');

        // Password baru bisa dipakai
        $this->post('/login', ['email' => $user->email, 'password' => 'password_baru1'])
            ->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_reset_password_dengan_token_tidak_valid_ditolak(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'token-tidak-valid',
            'email' => $user->email,
            'password' => 'password_baru1',
            'password_confirmation' => 'password_baru1',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('password_baru1', $user->fresh()->password));
    }

    public function test_reset_password_menolak_password_kurang_dari_8_karakter(): void
    {
        [$user, $token] = $this->buatUserDanAmbilTokenReset();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'pendek1',
            'password_confirmation' => 'pendek1',
        ])->assertSessionHasErrors('password');
    }

    /**
     * Buat user, ajukan reset password, ambil token dari notifikasinya.
     *
     * @return array{0: User, 1: string}
     */
    private function buatUserDanAmbilTokenReset(): array
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/lupa-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        return [$user, $token];
    }
}
