<?php

namespace App\Http\Requests\GCode;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gcode.use') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:5000'],
            'machine_profile_id' => ['nullable', 'integer', 'exists:machine_profiles,id'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'settings' => ['nullable', 'array', 'max:30'],
        ];
    }

    /** @return array<string, mixed> */
    public function projectData(): array
    {
        return $this->validated();
    }
}
