<?php

namespace App\Services\Web_Api;

use App\Models\User;
use App\Repositories\Web_Api\UserRepository;
use App\Services\CoreService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
                'name' => Str::lower($validated['name']),
                'email' => Str::lower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole(Role::findOrCreate('user', 'web'));

            return $user;
        });
    }

    /**
     * @param  array{email: string, password: string, device_name?: string|null}  $credentials
     * @return array{user: User, token: string}
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
        return $user->createToken($deviceName ?: 'api-client', ['*'])->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
