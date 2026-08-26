<?php

namespace App\Domain\GCode\Parsing;

use App\Domain\GCode\Exceptions\InvalidGCode;

class GCodeTokenizer
{
    /** @return list<string> */
    public function lines(string $gcode): array
    {
        if (strlen($gcode) > (int) config('gcode.max_input_bytes', 2 * 1024 * 1024)) {
            throw new InvalidGCode('The G-code input exceeds the configured size limit.');
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $gcode);
        $lines = array_values(array_filter(
            array_map(trim(...), explode("\n", $normalized)),
            static fn (string $line): bool => $line !== '',
        ));

        if ($lines === []) {
            throw new InvalidGCode('G-code input cannot be empty.');
        }

        return $lines;
    }
}
