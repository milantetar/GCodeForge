<?php

namespace App\Services\GCode;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Repositories\GCode\ProjectRepository;
use App\Services\CoreService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/** @extends CoreService<Project, ProjectRepository> */
class ProjectService extends CoreService
{
    public function __construct(protected ProjectRepository $projectRepository)
    {
        parent::__construct($projectRepository);
    }

    /** @return LengthAwarePaginator<int, Project> */
    public function paginateFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->query()
            ->where('user_id', $user->id)
            ->with('machineProfile')
            ->withCount('conversions')
            ->latest('last_activity_at')
            ->latest('id')
            ->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Project
    {
        /** @var Project $project */
        $project = $this->projectRepository->create([
            ...Arr::except($data, ['slug']),
            'user_id' => $user->id,
            'slug' => $this->uniqueSlug($user, (string) ($data['slug'] ?? $data['name'])),
            'status' => ProjectStatus::Active,
            'last_activity_at' => now(),
        ]);

        return $project->load('machineProfile')->loadCount('conversions');
    }

    /** @param array<string, mixed> $data */
    public function update(Project $project, array $data): Project
    {
        if (isset($data['slug']) || (isset($data['name']) && $data['name'] !== $project->name)) {
            $data['slug'] = $this->uniqueSlug(
                $project->user,
                (string) ($data['slug'] ?? $data['name']),
                $project->id,
            );
        }

        $data['last_activity_at'] = now();
        $this->projectRepository->update($project, $data);

        return $project->refresh()->load('machineProfile')->loadCount('conversions');
    }

    public function archive(Project $project): Project
    {
        $project->forceFill([
            'status' => ProjectStatus::Archived,
            'last_activity_at' => now(),
        ])->save();

        return $project->refresh();
    }

    private function uniqueSlug(User $user, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'project';
        $slug = $base;
        $suffix = 2;

        while ($this->projectRepository->query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
