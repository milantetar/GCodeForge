<?php

namespace App\Http\Controllers\Web;

use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Enums\ConversionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\GCode\StoreConversionRequest;
use App\Http\Resources\ConversionResource;
use App\Models\Conversion;
use App\Models\Project;
use App\Models\User;
use App\Services\GCode\ConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversionController extends Controller
{
    public function __construct(private ConversionService $conversions) {}

    public function store(StoreConversionRequest $request): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->integer('project_id'));
        Gate::authorize('update', $project);
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        try {
            $submission = $this->conversions->submit($user, $project, $request->converterType(), $request->conversionData());
        } catch (InvalidGCode $exception) {
            throw ValidationException::withMessages(['gcode' => $exception->getMessage()]);
        }

        return to_route('conversions.show', $submission->conversion);
    }

    public function show(Request $request, Conversion $conversion): Response
    {
        Gate::authorize('view', $conversion);

        return Inertia::render('conversions/show', [
            'conversion' => ConversionResource::make($conversion->load('project'))->resolve($request),
            'outputPreview' => fn () => $conversion->status === ConversionStatus::Completed ? $this->conversions->outputPreview($conversion) : null,
        ]);
    }

    public function download(Conversion $conversion): StreamedResponse
    {
        Gate::authorize('view', $conversion);
        abort_unless($conversion->status === ConversionStatus::Completed, 409, 'Conversion is not complete.');
        $file = $this->conversions->outputFile($conversion);

        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
