<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use Illuminate\Http\JsonResponse;

class AuthUserController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function registerUser(RegisterUserRequest $request)
    {
        $validated = $request->validated();
        dd($validated);
    }
}
