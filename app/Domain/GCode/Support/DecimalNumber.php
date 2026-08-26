<?php

namespace App\Domain\GCode\Support;

use App\Domain\GCode\Exceptions\InvalidGCode;

final readonly class DecimalNumber
{
    private const FACTOR = 1_000_000;

    private function __construct(private int $scaled) {}

    public static function from(mixed $value, string $field = 'coordinate'): self
    {
        if (is_int($value)) {
            $value = (string) $value;
        } elseif (is_float($value) && is_finite($value)) {
            $value = sprintf('%.12F', $value);
        }

        if (! is_string($value)) {
            throw new InvalidGCode("{$field} must be a finite decimal number.");
        }

        $value = trim($value);

        if (! preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/', $value, $matches)
            || ($matches[2] === '' && ($matches[3] ?? '') === '')) {
            throw new InvalidGCode("{$field} must be a finite decimal number.");
        }

        $whole = ltrim($matches[2] === '' ? '0' : $matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;

        if (strlen($whole) > 9) {
            throw new InvalidGCode("{$field} is outside the supported coordinate range.");
        }

        $fraction = str_pad($matches[3] ?? '', 7, '0');
        $scaled = ((int) $whole * self::FACTOR) + (int) substr($fraction, 0, 6);

        if ((int) $fraction[6] >= 5) {
            $scaled++;
        }

        if ($matches[1] === '-') {
            $scaled *= -1;
        }

        return new self($scaled);
    }

    public function plus(self $other): self
    {
        return new self($this->scaled + $other->scaled);
    }

    public function times(int $multiplier): self
    {
        return new self($this->scaled * $multiplier);
    }

    public function negate(): self
    {
        return new self(-$this->scaled);
    }

    public function format(): string
    {
        $sign = $this->scaled < 0 ? '-' : '';
        $absolute = abs($this->scaled);

        return sprintf('%s%d.%06d', $sign, intdiv($absolute, self::FACTOR), $absolute % self::FACTOR);
    }
}
