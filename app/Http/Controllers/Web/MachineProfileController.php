<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GCode\SaveMachineProfileRequest;
use App\Http\Resources\MachineProfileResource;
use App\Models\MachineProfile;
use App\Models\User;
use App\Services\GCode\MachineProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MachineProfileController extends Controller
{
    public function __construct(private MachineProfileService $profiles) {}

    public function index(Request $request): Response
    {
        $profiles = $this->profiles->paginateFor($this->user($request));

        return Inertia::render('machine-profiles/index', [
            'profiles' => ['data' => MachineProfileResource::collection($profiles)->resolve($request), 'links' => $profiles->linkCollection()->all()],
        ]);
    }

    public function store(SaveMachineProfileRequest $request): RedirectResponse
    {
        $this->profiles->create($this->user($request), $request->profileData());

        return back()->with('success', 'Machine profile created.');
    }

    public function update(SaveMachineProfileRequest $request, MachineProfile $machineProfile): RedirectResponse
    {
        Gate::authorize('update', $machineProfile);
        $this->profiles->update($machineProfile, $request->profileData());

        return back()->with('success', 'Machine profile saved.');
    }

    public function destroy(MachineProfile $machineProfile): RedirectResponse
    {
        Gate::authorize('delete', $machineProfile);
        $this->profiles->delete($machineProfile);

        return back()->with('success', 'Machine profile removed.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
