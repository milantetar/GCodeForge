<?php

namespace App\Jobs;

use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Enums\ConversionStatus;
use App\Models\Conversion;
use App\Services\GCode\ConversionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessGCodeConversion implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public int $conversionId)
    {
        $this->onQueue('conversions');
    }

    public function handle(ConversionService $conversionService): void
    {
        $conversion = Conversion::query()->find($this->conversionId);

        if (! $conversion instanceof Conversion) {
            return;
        }

        if ($conversion->status === ConversionStatus::Completed) {
            return;
        }

        try {
            $conversionService->process($conversion);
        } catch (InvalidGCode) {
            // Invalid source is deterministic and has already been marked failed.
        }
    }
}
