<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GCode\StoreProjectRequest;
use App\Http\Resources\ConversionResource;
use App\Http\Resources\MachineProfileResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\GCode\ConversionService;
use App\Services\GCode\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projects, private ConversionService $conversions) {}

    public function dashboard(Request $request): Response
    {
        $user = $this->user($request);

        return Inertia::render('dashboard', [
            'stats' => [
                'projects' => $user->projects()->where('status', 'active')->count(),
                'conversions' => $user->conversions()->count(),
                'completed' => $user->conversions()->where('status', 'completed')->count(),
                'profiles' => $user->machineProfiles()->count(),
            ],
            'recentProjects' => ProjectResource::collection($user->projects()->with('machineProfile')->withCount('conversions')->latest('last_activity_at')->limit(6)->get())->resolve($request),
        ]);
    }

    public function index(Request $request): Response
    {
        $pages = $this->projects->paginateFor($this->user($request));

        return Inertia::render('projects/index', [
            'projects' => ['data' => ProjectResource::collection($pages)->resolve($request), 'links' => $pages->linkCollection()->all()],
            'profiles' => MachineProfileResource::collection($this->user($request)->machineProfiles()->orderBy('name')->get())->resolve($request),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->projects->create($this->user($request), $request->projectData());

        return to_route('projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);
        $history = $this->conversions->paginateForProject($project);

        return Inertia::render('projects/show', [
            'project' => ProjectResource::make($project->load('machineProfile')->loadCount('conversions'))->resolve($request),
            'profiles' => MachineProfileResource::collection($project->user->machineProfiles()->orderBy('name')->get())->resolve($request),
            'conversions' => ['data' => ConversionResource::collection($history)->resolve($request), 'links' => $history->linkCollection()->all()],
            'maxInputBytes' => config('gcode.max_input_bytes'),
        ]);
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $this->projects->update($project, $request->projectData());

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);
        $this->projects->archive($project);

        return back()->with('success', 'Project archived. History remains available.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
