<?php

namespace App\Domain\GCode;

use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Domain\GCode\Parsing\GCodeTokenizer;
use App\Domain\GCode\Support\DecimalNumber;
use App\Enums\ConverterType;

class ConversionOptions
{
    /** @param array<string, mixed> $options
     * @return array{repetitions: int, y_distance?: string, b_increment?: string, rapid_feed_rate?: int, engraving_feed_rate?: int}
     */
    public function normalize(ConverterType $type, string $input, array $options): array
    {
        $lines = app(GCodeTokenizer::class)->lines($input);
        $repetitions = filter_var($options['repetitions'] ?? 1, FILTER_VALIDATE_INT);
        if ($repetitions === false || $repetitions < 1 || $repetitions > config('gcode.max_repetitions', 1000)) {
            throw new InvalidGCode('Invalid repetition count.');
        }
        // Conservative upper bound before allocating repeated PHP strings and arrays.
        if (count($lines) * $repetitions > config('gcode.max_output_lines', 100000)
            || (strlen($input) + count($lines) * 96 + 128) * $repetitions > config('gcode.max_output_bytes', 16 * 1024 * 1024)) {
            throw new InvalidGCode('This conversion exceeds the output budget. Reduce input size or repetitions.');
        }
        $result = ['repetitions' => $repetitions];
        $distance = $type === ConverterType::TwoAxis ? 'b_increment' : 'y_distance';
        $result[$distance] = DecimalNumber::from($options[$distance] ?? '0', $distance)->format();
        if ($type === ConverterType::TwoAxis) {
            foreach (['rapid_feed_rate' => 3000, 'engraving_feed_rate' => 300] as $key => $default) {
                $feed = filter_var($options[$key] ?? $default, FILTER_VALIDATE_INT);
                if ($feed === false || $feed < 1 || $feed > 1000000) {
                    throw new InvalidGCode($key.' must be between 1 and 1000000.');
                }
                $result[$key] = $feed;
            }
        }

        return $result;
    }
}
