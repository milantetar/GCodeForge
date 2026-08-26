<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $avatar_url
 * @property string|null $password
 * @property string|null $headline
 * @property string|null $bio
 * @property string|null $phone_number
 * @property string|null $portfolio_slug
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property string|null $zip_code
 * @property string|null $address
 * @property string|null $website_url
 * @property array<string, string>|null $social_links
 * @property bool $is_public
 * @property string|null $remember_token
 * @property string $preferred_theme
 * @property UserStatus|null $status
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, SocialAccount> $socialAccounts
 */
#[Fillable([
    'name',
    'first_name',
    'last_name',
    'email',
    'avatar_url',
    'password',
    'headline',
    'bio',
    'phone_number',
    'portfolio_slug',
    'country',
    'state',
    'city',
    'zip_code',
    'address',
    'website_url',
    'social_links',
    'is_public',
    'preferred_theme',
    'status',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;
    use PasskeyAuthenticatable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * Get the social identities linked to this user.
     *
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<Conversion, $this> */
    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    /** @return HasMany<MachineProfile, $this> */
    public function machineProfiles(): HasMany
    {
        return $this->hasMany(MachineProfile::class);
    }

    /**
     * Determine whether the account may authenticate and use the application.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Send the browser-based email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Send a verification notification whose signed link returns JSON.
     */
    public function sendApiEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification('api.v1.verification.verify'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_public' => 'boolean',
            'password' => 'hashed',
            'social_links' => 'array',
            'status' => UserStatus::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
