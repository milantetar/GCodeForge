<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],

            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    /**
     * Return the verified registration payload with a concrete application type.
     *
     * @return array{name: string, email: string, password: string}
     */
    public function registrationData(): array
    {
        $validated = $this->validated();

        if (! is_string($validated['name'] ?? null)
            || ! is_string($validated['email'] ?? null)
            || ! is_string($validated['password'] ?? null)) {
            throw new \LogicException('The registration request contains an invalid payload.');
        }

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];
    }
}
