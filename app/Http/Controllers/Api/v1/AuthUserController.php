<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Web_Api\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthUserController extends BaseController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function registerUser(RegisterUserRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->registrationData());
        $user->sendApiEmailVerificationNotification();

        return $this->successResponse(
            message: 'User registered successfully. Check your email to verify the account.',
            data: [
                'user' => new UserResource($user),
                'token' => $this->authService->issueToken($user, 'api-registration'),
                'token_type' => 'Bearer',
                'verification_required' => true,
            ],
            statusCode: 201,
        );
    }

    public function loginUser(LoginUserRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->credentials(), $request->ip());

        if ($result['two_factor_required'] ?? false) {
            return $this->successResponse('Two-factor authentication required.', $result);
        }

        return $this->successResponse('Authenticated successfully.', [
            'user' => new UserResource($result['user'] ?? null),
            'token' => $result['token'] ?? null,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse('Authenticated user retrieved.', [
            'user' => new UserResource($this->user($request)),
        ]);
    }

    public function twoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string', 'size:64'],
            'code' => ['nullable', 'required_without:recovery_code', 'digits:6', 'prohibits:recovery_code'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:100'],
        ]);
        $result = $this->authService->completeTwoFactor($request->string('challenge_token')->toString(),
            $request->filled('code') ? $request->string('code')->toString() : null,
            $request->filled('recovery_code') ? $request->string('recovery_code')->toString() : null, $request->ip());

        return $this->successResponse('Authenticated successfully.', [
            'user' => new UserResource($result['user']), 'token' => $result['token'], 'token_type' => 'Bearer',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        Password::sendResetLink(['email' => Str::lower(trim($request->string('email')->toString()))]);

        return $this->successResponse('If an account exists, password reset instructions will be sent.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);
        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return $this->successResponse('Password reset. Sign in with your new password.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->user($request)->tokens()->delete();

        return $this->successResponse('All API tokens revoked.');
    }

    public function logoutUser(Request $request): JsonResponse
    {
        $this->authService->revokeCurrentToken($this->user($request), $request->bearerToken());

        return $this->successResponse('Logged out successfully.');
    }

    public function verificationStatus(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return $this->successResponse('Email verification status retrieved.', [
            'email' => $user->email,
            'verified' => $user->hasVerifiedEmail(),
            'verified_at' => $user->email_verified_at,
        ]);
    }

    public function sendVerificationNotification(Request $request): JsonResponse
    {
        $user = $this->user($request);

        if ($user->hasVerifiedEmail()) {
            return $this->conflictResponse('This email address is already verified.');
        }

        $user->sendApiEmailVerificationNotification();

        return $this->successResponse(
            message: 'A fresh verification link has been queued for delivery.',
            statusCode: 202,
        );
    }

    public function verifyEmail(string $id, string $hash): JsonResponse
    {
        $user = User::query()->find($id);

        if (! $user instanceof User) {
            return $this->notFoundResponse('The verification account was not found.');
        }
        if (! $user->isActive()) {
            return $this->forbiddenResponse('This account is not active.');
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->forbiddenResponse('The email verification link is invalid.');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse('Email address is already verified.', [
                'verified' => true,
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->successResponse('Email address verified successfully.', [
            'verified' => true,
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
