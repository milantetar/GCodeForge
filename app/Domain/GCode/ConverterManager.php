<?php

namespace App\Domain\GCode;

use App\Domain\GCode\Contracts\GCodeConverter;
use App\Domain\GCode\Converters\HexagonalFlatConverter;
use App\Domain\GCode\Converters\PatternRepeaterConverter;
use App\Domain\GCode\Converters\TwoAxisConverter;
use App\Domain\GCode\Data\ConversionResult;
use App\Enums\ConverterType;

class ConverterManager
{
    public function __construct(
        private PatternRepeaterConverter $patternRepeater,
        private HexagonalFlatConverter $hexagonalFlat,
        private TwoAxisConverter $twoAxis,
    ) {}

    /** @param array<string, mixed> $options */
    public function convert(ConverterType $type, string $gcode, array $options): ConversionResult
    {
        $options = app(ConversionOptions::class)->normalize($type, $gcode, $options);

        return $this->converter($type)->convert($gcode, $options);
    }

    private function converter(ConverterType $type): GCodeConverter
    {
        return match ($type) {
            ConverterType::PatternRepeater => $this->patternRepeater,
            ConverterType::HexagonalFlat => $this->hexagonalFlat,
            ConverterType::TwoAxis => $this->twoAxis,
        };
    }
}
