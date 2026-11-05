<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_call_the_api_with_the_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => ' Маслов ',
            'email' => 'Maslov@Example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'device_name' => 'pixel',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.name', 'Маслов')
            ->assertJsonPath('user.email', 'maslov@example.com')
            ->assertJsonMissingPath('user.password');

        $user = User::query()->where('email', 'maslov@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secret-password', $user->password));

        $this->withToken((string) $response->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'maslov@example.com');
    }

    public function test_register_rejects_a_short_password_and_a_duplicate_email(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Маслов',
            'email' => 'maslov@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        User::factory()->create(['email' => 'maslov@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Маслов',
            'email' => 'maslov@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_user_can_login_with_a_different_email_case(): void
    {
        User::factory()->create(['email' => 'maslov@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'Maslov@Example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'maslov@example.com');

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        User::factory()->create(['email' => 'maslov@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'maslov@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Неверный email или пароль.');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('phone')->plainTextToken;
        $other = $user->createToken('tablet')->plainTextToken;

        $this->withToken($current)->postJson('/api/auth/logout')->assertNoContent();

        $this->withToken($current)
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Требуется авторизация.');

        $this->withToken($other)
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_notes_require_a_token(): void
    {
        $this->getJson('/api/notes')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Требуется авторизация.');

        $this->getJson('/api/auth/me')->assertUnauthorized();
    }
}
