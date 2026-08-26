<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_check_status_and_resend_the_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/email/verification-status')
            ->assertOk()
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.verified_at', null);

        $this->postJson('/api/v1/email/verification-notification')
            ->assertAccepted()
            ->assertJsonPath('success', true);

        Notification::assertSentTo(
            $user,
            VerifyEmailNotification::class,
            fn (VerifyEmailNotification $notification): bool => $notification->toMail($user)
                ->actionUrl === URL::temporarySignedRoute(
                    'api.v1.verification.verify',
                    now()->addMinutes((int) config('auth.verification.expire', 60)),
                    ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
                ),
        );
    }

    public function test_a_valid_signed_api_link_verifies_the_email_address(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.user.email_verified', true);

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_an_invalid_or_tampered_api_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson("/api/v1/email/verify/{$user->id}/invalid-hash")
            ->assertForbidden();

        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_a_verified_user_cannot_request_another_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/email/verification-notification')
            ->assertConflict()
            ->assertJsonPath('success', false);

        Notification::assertNothingSent();
    }
}
