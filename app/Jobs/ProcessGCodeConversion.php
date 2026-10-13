<?php

namespace App\Jobs;

use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Enums\ConversionStatus;
use App\Models\Conversion;
use App\Services\GCode\ConversionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ProcessGCodeConversion implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public int $conversionId)
    {
        $this->onQueue('conversions');
    }

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('conversion:'.$this->conversionId))->releaseAfter(10)->expireAfter(150)];
    }

    public function failed(?Throwable $exception): void
    {
        Conversion::query()->whereKey($this->conversionId)->where('status', '!=', ConversionStatus::Completed)
            ->update(['status' => ConversionStatus::Failed, 'error_message' => 'Conversion failed or timed out. Please submit a smaller workload.', 'completed_at' => now()]);
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
