<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_authentication_pages_render_the_expected_inertia_components(): void
    {
        $this->get('/login')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('auth/login')
        );

        $this->get('/register')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('auth/register')
        );

        $this->get('/forgot-password')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('auth/forgot-password')
        );
    }

    public function test_a_new_account_is_created_with_the_user_role(): void
    {
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Milan Maker',
            'email' => 'MILAN@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $user = User::query()->where('email', 'milan@example.com')->firstOrFail();

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('user'));
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => 'password',
            'status' => UserStatus::Suspended,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unverified_users_are_redirected_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/email/verify');
    }
}
