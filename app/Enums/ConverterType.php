<?php

namespace App\Enums;

enum ConverterType: string
{
    case PatternRepeater = 'pattern-repeater';
    case HexagonalFlat = 'hexagonal-flat';
    case TwoAxis = 'two-axis';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
