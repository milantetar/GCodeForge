<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Enums\ProjectStatus;
use App\Http\Controllers\BaseController;
use App\Http\Requests\GCode\StoreConversionRequest;
use App\Http\Resources\ConversionResource;
use App\Models\Conversion;
use App\Models\Project;
use App\Models\User;
use App\Services\GCode\ConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversionController extends BaseController
{
    public function __construct(private ConversionService $conversionService) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('view', $project);

        return $this->successResponse(
            'Conversions retrieved.',
            ConversionResource::collection($this->conversionService->paginateForProject($project)),
        );
    }

    public function store(StoreConversionRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $project = Project::query()->findOrFail($request->integer('project_id'));
        Gate::forUser($user)->authorize('update', $project);

        if ($project->status === ProjectStatus::Archived) {
            return $this->conflictResponse('Archived projects cannot accept new conversions.');
        }

        try {
            $submission = $this->conversionService->submit(
                $user,
                $project,
                $request->converterType(),
                $request->conversionData(),
            );
        } catch (InvalidGCode $exception) {
            return $this->validationErrorResponse(
                'The G-code program could not be converted.',
                ['gcode' => [$exception->getMessage()]],
            );
        }

        return $this->successResponse(
            message: $submission->queued ? 'Conversion queued.' : 'Conversion completed.',
            data: [
                'conversion' => new ConversionResource($submission->conversion),
                'output' => $submission->result?->output,
            ],
            statusCode: $submission->queued ? 202 : 201,
        );
    }

    public function show(Request $request, Conversion $conversion): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('view', $conversion);

        return $this->successResponse(
            'Conversion retrieved.',
            new ConversionResource($conversion->load(['project', 'machineProfile', 'files'])),
        );
    }

    public function download(Request $request, Conversion $conversion): StreamedResponse
    {
        Gate::forUser($this->user($request))->authorize('download', $conversion);
        $file = $this->conversionService->outputFile($conversion);

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name,
            ['Content-Type' => $file->mime_type],
        );
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
