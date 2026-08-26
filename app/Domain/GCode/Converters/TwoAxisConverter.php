<?php

namespace App\Domain\GCode\Converters;

use App\Domain\GCode\Contracts\GCodeConverter;
use App\Domain\GCode\Data\ConversionResult;
use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Domain\GCode\Support\GCodeLine;

class TwoAxisConverter extends AbstractGCodeConverter implements GCodeConverter
{
    /** @param array<string, mixed> $options */
    public function convert(string $gcode, array $options): ConversionResult
    {
        $startedAt = hrtime(true);
        $lines = $this->tokenizer->lines($gcode);
        $repetitions = $this->repetitions($options);
        $increment = $this->decimalOption($options, 'b_increment');
        $rapidFeed = $this->feedRate($options, 'rapid_feed_rate');
        $engravingFeed = $this->feedRate($options, 'engraving_feed_rate');
        $m3Index = null;

        foreach ($lines as $index => $line) {
            if (strcasecmp(trim($line), 'M3') === 0) {
                $m3Index = $index;

                break;
            }
        }

        if ($m3Index === null) {
            throw new InvalidGCode('Two Axis Converter requires an exact M3 header marker.');
        }

        $terminalIndex = null;
        for ($index = count($lines) - 1; $index > $m3Index; $index--) {
            if (GCodeLine::startsWithZ4($lines[$index])) {
                $terminalIndex = $index;

                break;
            }
        }

        if ($terminalIndex === null || $terminalIndex <= $m3Index) {
            throw new InvalidGCode('Two Axis Converter requires a repeatable block ending with Z4.');
        }

        $header = array_slice($lines, 0, $m3Index + 1);
        $body = array_slice($lines, $m3Index + 1, $terminalIndex - $m3Index);
        $footer = array_slice($lines, $terminalIndex + 1);

        if ($body === []) {
            throw new InvalidGCode('Two Axis Converter repeatable body is empty.');
        }

        $output = $header;

        for ($copy = 0; $copy < $repetitions; $copy++) {
            $b = $increment->times($copy)->format();
            $feedIndex = 0;
            $output[] = sprintf('( Repetition %d | B axis %s )', $copy + 1, $b);

            foreach ($body as $line) {
                if (GCodeLine::hasAxes($line, 'X', 'Y')) {
                    $line = GCodeLine::axis($line, 'B') === null
                        ? GCodeLine::appendToken($line, 'B'.$b)
                        : GCodeLine::replaceAxis($line, 'B', $b);
                }

                if (GCodeLine::axis($line, 'F') !== null) {
                    $line = GCodeLine::replaceAxis(
                        $line,
                        'F',
                        (string) ($feedIndex === 0 ? $rapidFeed : $engravingFeed),
                    );
                    $feedIndex++;
                }

                $output[] = $line;
            }
        }

        array_push($output, ...$footer);

        return $this->result($output, count($lines), $startedAt, extra: [
            'converter' => 'two-axis',
            'repetitions' => $repetitions,
        ]);
    }

    /** @param array<string, mixed> $options */
    private function feedRate(array $options, string $key): int
    {
        $value = filter_var($options[$key] ?? null, FILTER_VALIDATE_INT);

        if ($value === false || $value < 0 || $value > 1_000_000) {
            throw new InvalidGCode("{$key} must be an integer between 0 and 1000000.");
        }

        return $value;
    }
}
