<?php

namespace Tests\Feature\Models;

use App\Enums\UserStatus;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_exposes_authentication_and_authorization_capabilities(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'user']);

        $user->assignRole($role);
        $token = $user->createToken('test-device', ['gcode:process']);

        $this->assertInstanceOf(PasskeyUser::class, $user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->hasRole('user'));
        $this->assertTrue($token->accessToken->can('gcode:process'));
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_user_can_have_social_accounts(): void
    {
        $user = User::factory()->create();

        $socialAccount = $user->socialAccounts()->create([
            'provider' => 'github',
            'provider_user_id' => 'github-user-123',
            'avatar_url' => 'https://example.com/avatar.png',
        ]);

        $this->assertInstanceOf(SocialAccount::class, $socialAccount);
        $this->assertTrue($socialAccount->user->is($user));
        $this->assertTrue($user->socialAccounts->contains($socialAccount));
    }
}
