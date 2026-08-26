<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Web_Api\AuthService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return $this->successResponse('Authenticated successfully.', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse('Authenticated user retrieved.', [
            'user' => new UserResource($this->user($request)),
        ]);
    }

    public function logoutUser(Request $request): JsonResponse
    {
        $this->authService->revokeCurrentToken($this->user($request));

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

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->forbiddenResponse('The email verification link is invalid.');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse('Email address is already verified.', [
                'user' => new UserResource($user),
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->successResponse('Email address verified successfully.', [
            'user' => new UserResource($user->refresh()),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
