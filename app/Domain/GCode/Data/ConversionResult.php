<?php

namespace App\Domain\GCode\Data;

final readonly class ConversionResult
{
    /**
     * @param  list<string>  $warnings
     * @param  array<string, int|string|bool>  $metadata
     */
    public function __construct(
        public string $output,
        public array $warnings,
        public array $metadata,
    ) {}
}
