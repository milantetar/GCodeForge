<?php

namespace App\Services\GCode;

use App\Domain\GCode\ConversionOptions;
use App\Domain\GCode\ConverterManager;
use App\Domain\GCode\Data\ConversionResult;
use App\Domain\GCode\Data\ConversionSubmission;
use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Enums\ConversionFileKind;
use App\Enums\ConversionStatus;
use App\Enums\ConverterType;
use App\Enums\ProjectStatus;
use App\Jobs\ProcessGCodeConversion;
use App\Models\Conversion;
use App\Models\ConversionFile;
use App\Models\MachineProfile;
use App\Models\Project;
use App\Models\User;
use App\Repositories\GCode\ConversionRepository;
use App\Services\CoreService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** @extends CoreService<Conversion, ConversionRepository> */
class ConversionService extends CoreService
{
    public function __construct(
        protected ConversionRepository $conversionRepository,
        private ConverterManager $converterManager,
    ) {
        parent::__construct($conversionRepository);
    }

    /** @return LengthAwarePaginator<int, Conversion> */
    public function paginateForProject(Project $project, int $perPage = 20): LengthAwarePaginator
    {
        return $this->conversionRepository->query()
            ->where('project_id', $project->id)
            ->with(['project', 'machineProfile'])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @param  array{gcode: string, options: array<string, mixed>, async?: bool, source_filename?: string|null}  $data
     */
    public function submit(User $user, Project $project, ConverterType $type, array $data): ConversionSubmission
    {
        if ($project->user_id !== $user->id && ! $user->can('projects.manage')) {
            throw new AuthorizationException;
        }
        if ($project->status === ProjectStatus::Archived) {
            throw new InvalidGCode('Restore this project before creating a conversion.');
        }

        $gcode = $data['gcode'];
        $profile = $project->machine_profile_id === null
            ? null
            : MachineProfile::query()->find($project->machine_profile_id);
        $options = app(ConversionOptions::class)->normalize($type, $gcode, [
            ...($profile->settings ?? []),
            ...$data['options'],
        ]);
        $publicId = (string) Str::ulid();
        $disk = (string) config('gcode.disk', 'local');
        $inputPath = "gcode/{$user->id}/{$publicId}/input.nc";

        $shouldQueue = (bool) ($data['async'] ?? false)
            || strlen($gcode) >= (int) config('gcode.queue_threshold_bytes', 256 * 1024)
            || strlen($gcode) * $options['repetitions'] >= (int) config('gcode.queue_threshold_bytes', 256 * 1024);

        if (! Storage::disk($disk)->put($inputPath, $gcode)) {
            throw new RuntimeException('The source G-code could not be written to private storage.');
        }
        try {
            $conversion = $this->transaction(function () use ($publicId, $user, $project, $profile, $type, $options, $gcode, $disk, $inputPath, $data, $shouldQueue): Conversion {
                /** @var Conversion $conversion */
                $conversion = $this->conversionRepository->create([
                    'public_id' => $publicId,
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'machine_profile_id' => $profile?->id,
                    'type' => $type,
                    'status' => ConversionStatus::Pending,
                    'options' => $options,
                    'input_hash' => hash('sha256', $gcode),
                    'input_lines' => substr_count(str_replace(["\r\n", "\r"], "\n", $gcode), "\n") + 1,
                    'queued_at' => $shouldQueue ? now() : null,
                ]);

                $conversion->files()->create([
                    'kind' => ConversionFileKind::Input,
                    'disk' => $disk,
                    'path' => $inputPath,
                    'original_name' => $this->sourceFilename($data['source_filename'] ?? null),
                    'mime_type' => 'text/plain',
                    'size_bytes' => strlen($gcode),
                    'checksum' => hash('sha256', $gcode),
                ]);

                if ($shouldQueue) {
                    ProcessGCodeConversion::dispatch($conversion->id)->afterCommit();
                }

                return $conversion;
            });
        } catch (Throwable $exception) {
            if (! Conversion::query()->where('public_id', $publicId)->exists()) {
                Storage::disk($disk)->delete($inputPath);
            } else {
                Conversion::query()->where('public_id', $publicId)->update(['status' => ConversionStatus::Failed, 'error_message' => 'The conversion could not be queued. Please try again.', 'completed_at' => now()]);
            }
            throw $exception;
        }

        if ($shouldQueue) {
            return new ConversionSubmission($conversion->refresh(), null, true);
        }

        $result = $this->process($conversion);

        return new ConversionSubmission(
            $conversion->refresh()->load(['project', 'machineProfile', 'files']),
            $result,
            false,
        );
    }

    public function process(Conversion $conversion): ConversionResult
    {
        return Cache::lock('gcode:processing:'.$conversion->id, 150)->block(3, fn (): ConversionResult => $this->processLocked($conversion));
    }

    private function processLocked(Conversion $conversion): ConversionResult
    {
        $conversion->refresh();
        if ($conversion->status === ConversionStatus::Completed) {
            return new ConversionResult($this->fileContents($conversion, ConversionFileKind::Output), $conversion->warnings ?? [], [
                'input_lines' => $conversion->input_lines ?? 0, 'output_lines' => $conversion->output_lines ?? 0,
                'duration_ms' => $conversion->duration_ms ?? 0,
            ]);
        }
        $conversion->forceFill([
            'status' => ConversionStatus::Processing,
            'started_at' => now(),
            'error_message' => null,
        ])->save();

        try {
            $input = $this->fileContents($conversion, ConversionFileKind::Input);
            $result = $this->convertWithCache($conversion, $input);
            $this->storeOutput($conversion, $result->output);

            $conversion->forceFill([
                'status' => ConversionStatus::Completed,
                'warnings' => $result->warnings,
                'input_lines' => (int) $result->metadata['input_lines'],
                'output_lines' => (int) $result->metadata['output_lines'],
                'duration_ms' => (int) $result->metadata['duration_ms'],
                'completed_at' => now(),
            ])->save();

            $conversion->project()->update(['last_activity_at' => now()]);

            return $result;
        } catch (Throwable $exception) {
            $retryable = ! $exception instanceof InvalidGCode && $conversion->queued_at !== null;
            $conversion->forceFill([
                'status' => $retryable ? ConversionStatus::Pending : ConversionStatus::Failed,
                'error_message' => $exception instanceof InvalidGCode ? Str::limit($exception->getMessage(), 500, '') : 'Conversion could not be completed. Please retry or contact support with the conversion ID.',
                'completed_at' => $retryable ? null : now(),
            ])->save();

            throw $exception;
        }
    }

    public function outputFile(Conversion $conversion): ConversionFile
    {
        abort_unless($conversion->status === ConversionStatus::Completed, 409, 'The conversion is not complete.');
        /** @var ConversionFile|null $file */
        $file = $conversion->files()
            ->where('kind', ConversionFileKind::Output->value)
            ->first();

        if ($file === null || ! Storage::disk($file->disk)->exists($file->path)) {
            abort(404, 'The generated G-code file is not available.');
        }

        return $file;
    }

    public function outputPreview(Conversion $conversion): string
    {
        $file = $this->outputFile($conversion);
        $stream = Storage::disk($file->disk)->readStream($file->path);
        if (! is_resource($stream)) {
            return '';
        }
        try {
            $content = fread($stream, 64 * 1024);

            return $content === false ? '' : $content;
        } finally {
            fclose($stream);
        }
    }

    private function convertWithCache(Conversion $conversion, string $input): ConversionResult
    {
        $options = $conversion->options;
        ksort($options);
        $optionsHash = hash('sha256', json_encode($options, JSON_THROW_ON_ERROR));
        $version = (string) config('gcode.converter_version', '1');
        $key = "gcode:result:{$conversion->user_id}:{$version}:{$conversion->type->value}:{$conversion->input_hash}:{$optionsHash}";
        $seconds = (int) config('gcode.result_cache_seconds', 900);

        if ($seconds <= 0) {
            return $this->converterManager->convert($conversion->type, $input, $options);
        }

        return Cache::remember(
            $key,
            $seconds,
            fn (): ConversionResult => $this->converterManager->convert($conversion->type, $input, $options),
        );
    }

    private function storeOutput(Conversion $conversion, string $output): void
    {
        $disk = (string) config('gcode.disk', 'local');
        $path = "gcode/{$conversion->user_id}/{$conversion->public_id}/output.nc";

        if (! Storage::disk($disk)->put($path, $output)) {
            throw new RuntimeException('The generated G-code could not be written to private storage.');
        }

        $conversion->files()->updateOrCreate(
            ['kind' => ConversionFileKind::Output->value],
            [
                'disk' => $disk,
                'path' => $path,
                'original_name' => $conversion->type->value.'-'.$conversion->public_id.'.nc',
                'mime_type' => 'text/plain',
                'size_bytes' => strlen($output),
                'checksum' => hash('sha256', $output),
            ],
        );
    }

    private function fileContents(Conversion $conversion, ConversionFileKind $kind): string
    {
        /** @var ConversionFile|null $file */
        $file = $conversion->files()->where('kind', $kind->value)->first();

        if ($file === null || ! Storage::disk($file->disk)->exists($file->path)) {
            throw new RuntimeException("The {$kind->value} G-code file is not available.");
        }

        return Storage::disk($file->disk)->get($file->path);
    }

    private function sourceFilename(?string $filename): string
    {
        $filename = $filename !== null ? basename(str_replace('\\', '/', $filename)) : 'input.nc';

        return $filename !== '' ? Str::limit($filename, 255, '') : 'input.nc';
    }
}
