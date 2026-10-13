<?php

namespace App\Services\Web_Api;

use App\Models\User;
use App\Repositories\Web_Api\UserRepository;
use App\Services\CoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

/**
 * @extends CoreService<User, UserRepository>
 */
class AuthService extends CoreService
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {
        parent::__construct($userRepository);
    }

    /**
     * Register a new user and return the created model.
     *
     * @param  array{name: string, email: string, password: string}  $validated
     */
    public function register(array $validated): User
    {
        return $this->transaction(function () use ($validated): User {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => trim($validated['name']),
                'email' => Str::lower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole(Role::findOrCreate('user', 'web'));

            return $user;
        });
    }

    /**
     * @param  array{email: string, password: string, device_name?: string|null}  $credentials
     * @return array{user?: User, token?: string, two_factor_required?: bool, challenge_token?: string, expires_in?: int}
     */
    public function login(array $credentials, ?string $ipAddress = null): array
    {
        $user = $this->userRepository->findByEmail(Str::lower(trim($credentials['email'])));

        if ($user === null || ! Hash::check($credentials['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This account is not currently active.'],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $challenge = Str::random(64);
            Cache::put('auth:challenge:'.hash('sha256', $challenge), [
                'user_id' => $user->id,
                'device_name' => $credentials['device_name'] ?? null,
                'password_fingerprint' => hash('sha256', (string) $user->password),
            ], now()->addMinutes(5));

            return ['two_factor_required' => true, 'challenge_token' => $challenge, 'expires_in' => 300];
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ])->save();

        return [
            'user' => $user->refresh(),
            'token' => $this->issueToken($user, $credentials['device_name'] ?? null),
        ];
    }

    public function issueToken(User $user, ?string $deviceName = null): string
    {
        return $user->createToken($deviceName ?: 'api-client', ['*'], now()->addMinutes(config('sanctum.expiration', 1440)))->plainTextToken;
    }

    public function revokeCurrentToken(User $user, ?string $bearerToken): void
    {
        if ($bearerToken === null) {
            return;
        }
        $token = PersonalAccessToken::findToken($bearerToken);
        if ($token !== null) {
            $user->tokens()->whereKey($token->getKey())->delete();
        }
    }

    /** @return array{user: User, token: string} */
    public function completeTwoFactor(string $challenge, ?string $code, ?string $recoveryCode, ?string $ip): array
    {
        $key = 'auth:challenge:'.hash('sha256', $challenge);

        return Cache::lock($key.':lock', 15)->block(3, function () use ($key, $code, $recoveryCode, $ip): array {
            $data = Cache::get($key);
            if (! is_array($data) || ! is_int($data['user_id'] ?? null)) {
                throw ValidationException::withMessages(['challenge_token' => 'Challenge expired. Sign in again.']);
            }
            // Consume before verification: even an invalid response requires a fresh password login.
            Cache::forget($key);

            return $this->transaction(function () use ($data, $code, $recoveryCode, $ip): array {
                $user = User::query()->lockForUpdate()->find($data['user_id']);
                if ($user === null || ! $user->isActive() || ! $user->hasEnabledTwoFactorAuthentication()
                    || ! hash_equals($data['password_fingerprint'], hash('sha256', (string) $user->password))) {
                    throw ValidationException::withMessages(['challenge_token' => 'Challenge expired. Sign in again.']);
                }
                $valid = false;
                if ($recoveryCode !== null && $user->two_factor_recovery_codes !== null) {
                    foreach ($user->recoveryCodes() as $candidate) {
                        if (is_string($candidate) && hash_equals($candidate, $recoveryCode)) {
                            $user->replaceRecoveryCode($candidate);
                            $valid = true;
                            break;
                        }
                    }
                } elseif ($code !== null) {
                    $valid = app(TwoFactorAuthenticationProvider::class)->verify(
                        Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code,
                    );
                }
                if (! $valid) {
                    throw ValidationException::withMessages(['code' => 'Invalid verification code. Sign in again to retry.']);
                }
                $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $ip])->save();

                return ['user' => $user, 'token' => $this->issueToken($user, $data['device_name'])];
            });
        });
    }
}
