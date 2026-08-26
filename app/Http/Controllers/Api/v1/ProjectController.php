<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\GCode\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\MachineProfile;
use App\Models\Project;
use App\Models\User;
use App\Services\GCode\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends BaseController
{
    public function __construct(private ProjectService $projectService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            'Projects retrieved.',
            ProjectResource::collection($this->projectService->paginateFor($this->user($request))),
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorizeMachineProfile($request);
        $project = $this->projectService->create($this->user($request), $request->projectData());

        return $this->createdResponse('Project created.', new ProjectResource($project));
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('view', $project);

        return $this->successResponse(
            'Project retrieved.',
            new ProjectResource($project->load('machineProfile')->loadCount('conversions')),
        );
    }

    public function update(StoreProjectRequest $request, Project $project): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('update', $project);
        $this->authorizeMachineProfile($request);

        return $this->successResponse(
            'Project updated.',
            new ProjectResource($this->projectService->update($project, $request->projectData())),
        );
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('delete', $project);

        return $this->successResponse(
            'Project archived.',
            new ProjectResource($this->projectService->archive($project)),
        );
    }

    private function authorizeMachineProfile(StoreProjectRequest $request): void
    {
        $profileId = $request->validated('machine_profile_id');

        if ($profileId !== null) {
            $profile = MachineProfile::query()->findOrFail((int) $profileId);
            Gate::forUser($this->user($request))->authorize('view', $profile);
        }
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
