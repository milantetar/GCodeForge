<?php

namespace App\Domain\GCode\Contracts;

use App\Domain\GCode\Data\ConversionResult;

interface GCodeConverter
{
    /** @param array<string, mixed> $options */
    public function convert(string $gcode, array $options): ConversionResult;
}
