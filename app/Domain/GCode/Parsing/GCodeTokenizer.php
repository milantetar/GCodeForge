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

        foreach ($lines as $index => $line) {
            // This dialect accepts numeric letter-address words, comments, block skip and program delimiters.
            // Expressions, checksums and controller macros require an explicit dialect adapter.
            $command = explode(';', $line, 2)[0];
            $command = preg_replace('/\([^()]*\)/', ' ', $command) ?? '';
            if (! preg_match('/^\s*(?:%|\/?\s*(?:[A-Z]\s*[+-]?(?:\d+(?:\.\d*)?|\.\d+)\s*)*)\s*$/iD', $command)) {
                throw new InvalidGCode('Invalid or unsupported G-code word on line '.($index + 1).'. Use numeric letter-address commands.');
            }
            preg_match_all('/([XYZABCUVWIJKR])\s*[+-]?(?:\d+(?:\.\d*)?|\.\d+)/i', $command, $words);
            $axes = array_map(strtoupper(...), $words[1]);
            if (count($axes) !== count(array_unique($axes))) {
                throw new InvalidGCode('Duplicate coordinate on line '.($index + 1).'.');
            }
        }

        return $lines;
    }
}
