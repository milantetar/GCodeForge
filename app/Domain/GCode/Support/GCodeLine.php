<?php

namespace App\Domain\GCode\Support;

final class GCodeLine
{
    private const NUMBER = '[+-]?(?:\d+(?:\.\d*)?|\.\d+)';

    public static function axis(string $line, string $axis): ?DecimalNumber
    {
        $command = explode(';', $line, 2)[0];
        $command = (string) preg_replace('/\([^)]*\)/', '', $command);

        if (! preg_match('/(?<![A-Z0-9_])'.preg_quote($axis, '/').'\s*('.self::NUMBER.')/i', $command, $matches)) {
            return null;
        }

        return DecimalNumber::from($matches[1], strtoupper($axis).' coordinate');
    }

    public static function hasAxes(string $line, string ...$axes): bool
    {
        foreach ($axes as $axis) {
            if (self::axis($line, $axis) === null) {
                return false;
            }
        }

        return true;
    }

    public static function startsWithZ4(string $line): bool
    {
        return preg_match('/^Z\s*4(?:\.0+)?(?:\s|$)/i', trim($line)) === 1;
    }

    public static function replaceAxis(string $line, string $axis, string $value): string
    {
        [$command, $comment] = array_pad(explode(';', $line, 2), 2, null);
        $command = (string) preg_replace(
            '/\([^)]*\)(*SKIP)(*F)|(?<![A-Z0-9_])'.preg_quote($axis, '/').'\s*'.self::NUMBER.'/i',
            strtoupper($axis).$value,
            $command,
        );

        return $comment === null ? $command : $command.';'.$comment;
    }

    public static function appendToken(string $line, string $token): string
    {
        $commentPosition = strpos($line, ';');

        if ($commentPosition === false) {
            return rtrim($line).' '.$token;
        }

        $command = rtrim(substr($line, 0, $commentPosition));
        $comment = ltrim(substr($line, $commentPosition));

        return $command.' '.$token.' '.$comment;
    }

    public static function inlineComment(string $line): string
    {
        $position = strpos($line, ';');

        return $position === false ? '' : ' '.trim(substr($line, $position));
    }

    public static function lineCount(string $gcode): int
    {
        return $gcode === '' ? 0 : substr_count($gcode, "\n") + 1;
    }
}
