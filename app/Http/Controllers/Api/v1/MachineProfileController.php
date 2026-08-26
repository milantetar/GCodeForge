<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\GCode\SaveMachineProfileRequest;
use App\Http\Resources\MachineProfileResource;
use App\Models\MachineProfile;
use App\Models\User;
use App\Services\GCode\MachineProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MachineProfileController extends BaseController
{
    public function __construct(private MachineProfileService $profileService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            'Machine profiles retrieved.',
            MachineProfileResource::collection($this->profileService->paginateFor($this->user($request))),
        );
    }

    public function store(SaveMachineProfileRequest $request): JsonResponse
    {
        $profile = $this->profileService->create($this->user($request), $request->profileData());

        return $this->createdResponse('Machine profile created.', new MachineProfileResource($profile));
    }

    public function show(Request $request, MachineProfile $machineProfile): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('view', $machineProfile);

        return $this->successResponse('Machine profile retrieved.', new MachineProfileResource($machineProfile));
    }

    public function update(SaveMachineProfileRequest $request, MachineProfile $machineProfile): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('update', $machineProfile);

        return $this->successResponse(
            'Machine profile updated.',
            new MachineProfileResource($this->profileService->update($machineProfile, $request->profileData())),
        );
    }

    public function destroy(Request $request, MachineProfile $machineProfile): JsonResponse
    {
        Gate::forUser($this->user($request))->authorize('delete', $machineProfile);
        $machineProfile->delete();

        return $this->successResponse('Machine profile deleted.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
