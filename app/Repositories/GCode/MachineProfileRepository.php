<?php

namespace App\Repositories\GCode;

use App\Models\MachineProfile;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;

/** @extends CoreRepository<MachineProfile> */
class MachineProfileRepository extends CoreRepository
{
    /** @return Builder<MachineProfile> */
    protected function newModelQuery(): Builder
    {
        return MachineProfile::query();
    }
}
