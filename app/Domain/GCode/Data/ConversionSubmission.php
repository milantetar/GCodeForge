<?php

namespace App\Domain\GCode\Data;

use App\Models\Conversion;

final readonly class ConversionSubmission
{
    public function __construct(
        public Conversion $conversion,
        public ?ConversionResult $result,
        public bool $queued,
    ) {}
}
