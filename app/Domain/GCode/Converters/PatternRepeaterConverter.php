<?php

namespace App\Domain\GCode\Converters;

use App\Domain\GCode\Contracts\GCodeConverter;
use App\Domain\GCode\Data\ConversionResult;
use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Domain\GCode\Support\GCodeLine;

class PatternRepeaterConverter extends AbstractGCodeConverter implements GCodeConverter
{
    /** @param array<string, mixed> $options */
    public function convert(string $gcode, array $options): ConversionResult
    {
        $startedAt = hrtime(true);
        $lines = $this->tokenizer->lines($gcode);
        $repetitions = $this->repetitions($options);
        $distance = $this->decimalOption($options, 'y_distance');
        $patternIndexes = [];

        foreach ($lines as $index => $line) {
            if (GCodeLine::startsWithZ4($line) && GCodeLine::hasAxes($line, 'X', 'Y')) {
                $patternIndexes[] = $index;
            }
        }

        if ($patternIndexes === []) {
            throw new InvalidGCode('Pattern Repeater requires at least one Z4 line containing X and Y coordinates.');
        }

        $first = $patternIndexes[0];
        $last = $patternIndexes[array_key_last($patternIndexes)];
        $header = array_slice($lines, 0, $first);
        $body = array_slice($lines, $first, $last - $first + 1);
        $footer = array_slice($lines, $last + 1);
        $output = $header;

        for ($copy = 0; $copy < $repetitions; $copy++) {
            $offset = $distance->times($copy);
            $output[] = sprintf('( Repetition %d | Y offset %s )', $copy + 1, $offset->format());

            foreach ($body as $line) {
                if (! GCodeLine::startsWithZ4($line) || ! GCodeLine::hasAxes($line, 'X', 'Y')) {
                    $output[] = $line;

                    continue;
                }

                $sourceX = GCodeLine::axis($line, 'X');
                $sourceY = GCodeLine::axis($line, 'Y');

                if ($sourceX === null || $sourceY === null) {
                    throw new InvalidGCode('A pattern coordinate could not be parsed.');
                }

                $transformed = GCodeLine::replaceAxis($line, 'Z', $sourceY->format());
                $transformed = GCodeLine::replaceAxis($transformed, 'X', $sourceX->negate()->format());
                $output[] = GCodeLine::replaceAxis($transformed, 'Y', $sourceX->plus($offset)->format());
            }
        }

        array_push($output, ...$footer);

        return $this->result($output, count($lines), $startedAt, extra: [
            'converter' => 'pattern-repeater',
            'repetitions' => $repetitions,
        ]);
    }
}
