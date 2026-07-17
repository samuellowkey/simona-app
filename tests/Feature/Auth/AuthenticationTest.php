<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // 1. Buat user dengan password yang didefinisikan secara eksplisit
        $user = User::factory()->create([
            'username' => 'operator_simona',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        // 2. Kirim request POST ke form login dengan menyertakan field email DAN username
        // Ini memastikan request memenuhi aturan validasi field apa pun yang digunakan sistem
        $response = $this->post('/login', [
            'email'    => $user->email,
            'username' => $user->username,
            'password' => 'password',
        ]);

        // 3. Pastikan user berhasil masuk dan dialihkan ke dashboard
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
