<?php

namespace App\Http\Requests\GCode;

use App\Enums\ConverterType;
use Illuminate\Foundation\Http\FormRequest;

class StoreConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gcode.use') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = $this->converterType();
        $maxBytes = (int) config('gcode.max_input_bytes', 2 * 1024 * 1024);
        $maxRepetitions = (int) config('gcode.max_repetitions', 1000);
        $decimal = ['sometimes', 'regex:/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/'];

        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'gcode' => ['required', 'string', 'max:'.$maxBytes],
            'source_filename' => ['nullable', 'string', 'max:255'],
            'async' => ['sometimes', 'boolean'],
            'options' => ['required', 'array'],
            'options.repetitions' => ['sometimes', 'integer', 'between:1,'.$maxRepetitions],
            'options.y_distance' => in_array($type, [ConverterType::PatternRepeater, ConverterType::HexagonalFlat], true)
                ? ['sometimes', 'regex:/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/']
                : ['prohibited'],
            'options.b_increment' => $type === ConverterType::TwoAxis ? $decimal : ['prohibited'],
            'options.rapid_feed_rate' => $type === ConverterType::TwoAxis
                ? ['sometimes', 'integer', 'between:0,1000000']
                : ['prohibited'],
            'options.engraving_feed_rate' => $type === ConverterType::TwoAxis
                ? ['sometimes', 'integer', 'between:0,1000000']
                : ['prohibited'],
        ];
    }

    public function converterType(): ConverterType
    {
        return ConverterType::from((string) $this->route('converter'));
    }

    /**
     * @return array{gcode: string, options: array<string, mixed>, async?: bool, source_filename?: string|null}
     */
    public function conversionData(): array
    {
        $validated = $this->validated();

        return [
            'gcode' => (string) $validated['gcode'],
            'options' => is_array($validated['options']) ? $validated['options'] : [],
            'async' => (bool) ($validated['async'] ?? false),
            'source_filename' => is_string($validated['source_filename'] ?? null)
                ? $validated['source_filename']
                : null,
        ];
    }
}
