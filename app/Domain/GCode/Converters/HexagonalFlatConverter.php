<?php

namespace App\Domain\GCode\Converters;

use App\Domain\GCode\Contracts\GCodeConverter;
use App\Domain\GCode\Data\ConversionResult;
use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Domain\GCode\Support\DecimalNumber;
use App\Domain\GCode\Support\GCodeLine;

class HexagonalFlatConverter extends AbstractGCodeConverter implements GCodeConverter
{
    /** @param array<string, mixed> $options */
    public function convert(string $gcode, array $options): ConversionResult
    {
        $startedAt = hrtime(true);
        $sourceLines = $this->tokenizer->lines($gcode);
        $repetitions = $this->repetitions($options);
        $distance = $this->decimalOption($options, 'y_distance');
        $latestY = null;
        $hasPoint = false;
        $transformed = [];

        foreach ($sourceLines as $line) {
            if (strcasecmp(trim($line), 'Z0') === 0) {
                continue;
            }

            $lineY = GCodeLine::axis($line, 'Y');
            if ($lineY !== null) {
                $latestY = $lineY;
            }

            if (GCodeLine::startsWithZ4($line)) {
                if (! $latestY instanceof DecimalNumber) {
                    throw new InvalidGCode('Hexagonal Flat requires a Y coordinate before each Z4 move.');
                }

                $line = GCodeLine::replaceAxis(
                    $line,
                    'Z',
                    DecimalNumber::from('4')->plus($latestY)->format(),
                );
            }

            $x = GCodeLine::axis($line, 'X');
            $y = GCodeLine::axis($line, 'Y');

            if ($x !== null && $y !== null) {
                $hasPoint = true;
                $line = GCodeLine::replaceAxis($line, 'X', $y->format());
                $line = GCodeLine::replaceAxis($line, 'Y', $x->format());
            }

            if (GCodeLine::axis($line, 'X') !== null) {
                $line = GCodeLine::replaceAxis($line, 'X', '0');
            }

            $transformed[] = $line;
        }

        if (! $hasPoint) {
            throw new InvalidGCode('Hexagonal Flat requires at least one line containing X and Y coordinates.');
        }

        [$header, $body, $footer] = $this->partition($transformed);
        $output = $header;

        for ($copy = 0; $copy < $repetitions; $copy++) {
            $offset = $distance->times($copy);
            $output[] = sprintf('( Repetition %d | Y offset %s )', $copy + 1, $offset->format());

            foreach ($body as $line) {
                $y = GCodeLine::axis($line, 'Y');
                $output[] = $y === null
                    ? $line
                    : GCodeLine::replaceAxis($line, 'Y', $y->plus($offset)->format());
            }
        }

        array_push($output, ...$footer);

        return $this->result($output, count($sourceLines), $startedAt, extra: [
            'converter' => 'hexagonal-flat',
            'repetitions' => $repetitions,
        ]);
    }

    /**
     * @param  list<string>  $lines
     * @return array{list<string>, list<string>, list<string>}
     */
    private function partition(array $lines): array
    {
        $bodyStart = null;

        foreach ($lines as $index => $line) {
            if (strcasecmp(trim($line), 'M3') === 0) {
                $bodyStart = $index + 1;

                break;
            }
        }

        if ($bodyStart === null) {
            foreach ($lines as $index => $line) {
                if (GCodeLine::axis($line, 'Y') !== null || GCodeLine::axis($line, 'Z') !== null) {
                    $bodyStart = $index;

                    break;
                }
            }
        }

        if ($bodyStart === null) {
            throw new InvalidGCode('Hexagonal Flat could not identify a repeatable body.');
        }

        $footerStart = count($lines);
        for ($index = $bodyStart; $index < count($lines); $index++) {
            if (in_array(strtoupper(trim($lines[$index])), ['M5', 'M30'], true)) {
                $footerStart = $index;

                break;
            }
        }

        $body = array_slice($lines, $bodyStart, $footerStart - $bodyStart);

        if ($body === []) {
            throw new InvalidGCode('Hexagonal Flat repeatable body is empty.');
        }

        return [
            array_slice($lines, 0, $bodyStart),
            $body,
            array_slice($lines, $footerStart),
        ];
    }
}
