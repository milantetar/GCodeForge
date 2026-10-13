<?php

namespace App\Domain\GCode\Converters;

use App\Domain\GCode\Data\ConversionResult;
use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Domain\GCode\Parsing\GCodeTokenizer;
use App\Domain\GCode\Support\DecimalNumber;
use App\Domain\GCode\Support\GCodeLine;

abstract class AbstractGCodeConverter
{
    public function __construct(protected GCodeTokenizer $tokenizer) {}

    /** @param array<string, mixed> $options */
    protected function repetitions(array $options): int
    {
        $repetitions = filter_var($options['repetitions'] ?? null, FILTER_VALIDATE_INT);
        $maximum = (int) config('gcode.max_repetitions', 1000);

        if ($repetitions === false || $repetitions < 1 || $repetitions > $maximum) {
            throw new InvalidGCode("repetitions must be between 1 and {$maximum}.");
        }

        return $repetitions;
    }

    /** @param array<string, mixed> $options */
    protected function decimalOption(array $options, string $key): DecimalNumber
    {
        return DecimalNumber::from($options[$key] ?? null, $key);
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $warnings
     * @param  array<string, int|string|bool>  $extra
     */
    protected function result(array $lines, int $inputLines, int $startedAt, array $warnings = [], array $extra = []): ConversionResult
    {
        $output = implode("\n", $lines)."\n";
        if (strlen($output) > (int) config('gcode.max_output_bytes', 16 * 1024 * 1024)) {
            throw new InvalidGCode('Generated output exceeds the configured size limit. Reduce repetitions.');
        }

        return new ConversionResult($output, $warnings, [
            'input_lines' => $inputLines,
            'output_lines' => GCodeLine::lineCount(rtrim($output, "\n")),
            'duration_ms' => max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)),
            ...$extra,
        ]);
    }
}
