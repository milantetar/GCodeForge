<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_user_with_the_default_role(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Milan Maker',
            'email' => 'milan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User registered successfully. Check your email to verify the account.')
            ->assertJsonPath('data.user.email', 'milan@example.com')
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.verification_required', true)
            ->assertJsonStructure(['data' => ['token']]);

        $user = User::query()->where('email', 'milan@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('user'));
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_user_can_login_fetch_their_profile_and_logout_with_a_token(): void
    {
        $user = User::factory()->create([
            'email' => 'maker@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/v1/login', [
            'email' => 'maker@example.com',
            'password' => 'password',
            'device_name' => 'Postman',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
