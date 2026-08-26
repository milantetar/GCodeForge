<?php

namespace App\Http\Requests\GCode;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMachineProfileRequest extends FormRequest
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
            'units' => ['required', Rule::in(['mm', 'inch'])],
            'settings' => ['nullable', 'array', 'max:30'],
            'settings.repetitions' => ['sometimes', 'integer', 'between:1,'.(int) config('gcode.max_repetitions', 1000)],
            'settings.y_distance' => ['sometimes', 'regex:/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/'],
            'settings.b_increment' => ['sometimes', 'regex:/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/'],
            'settings.rapid_feed_rate' => ['sometimes', 'integer', 'between:0,1000000'],
            'settings.engraving_feed_rate' => ['sometimes', 'integer', 'between:0,1000000'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function profileData(): array
    {
        return $this->validated();
    }
}
