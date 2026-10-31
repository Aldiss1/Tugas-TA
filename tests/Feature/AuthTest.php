<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_membuat_akun_calon_siswa_dan_mengirim_link_verifikasi(): void
    {
        Notification::fake();
        $r = $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@mail.com',
            'nisn' => '1234567890',
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
        ]);
        $r->assertRedirect('/login');

        $u = User::where('email', 'budi@mail.com')->first();
        $this->assertNotNull($u);
        $this->assertSame('calon_siswa', $u->role);
        $this->assertFalse($u->hasVerifiedEmail());
        Notification::assertSentTo($u, VerifyEmail::class);
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

    public function test_token_verifikasi_aktivasi_akun_token_kedaluwarsa_7_hari_ditolak(): void
    {
        $u = User::factory()->unverified()->create();
        $token = sha1($u->getEmailForVerification());

        $this->actingAs($u)->get('/verify-email/'.$token)->assertSessionHasNoErrors();
        $this->assertTrue($u->fresh()->hasVerifiedEmail());

        $u2 = User::factory()->unverified()->create();
        $u2->forceFill(['created_at' => now()->subDays(8)])->save();
        $this->actingAs($u2)->get('/verify-email/'.sha1($u2->getEmailForVerification()))
            ->assertSessionHasErrors('email');
        $this->assertFalse($u2->fresh()->hasVerifiedEmail());
    }
}
