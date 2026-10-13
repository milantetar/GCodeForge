<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array{email: string, password: string, device_name?: string|null} */
    public function credentials(): array
    {
        $validated = $this->validated();

        return [
            'email' => is_string($validated['email'] ?? null) ? $validated['email'] : '',
            'password' => is_string($validated['password'] ?? null) ? $validated['password'] : '',
            'device_name' => is_string($validated['device_name'] ?? null) ? $validated['device_name'] : null,
        ];
    }
}
