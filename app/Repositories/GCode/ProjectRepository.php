<?php

namespace App\Repositories\GCode;

use App\Models\Project;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;

/** @extends CoreRepository<Project> */
class ProjectRepository extends CoreRepository
{
    /** @return Builder<Project> */
    protected function newModelQuery(): Builder
    {
        return Project::query();
    }
}
