<?php

namespace App\Repositories\GCode;

use App\Models\Conversion;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;

/** @extends CoreRepository<Conversion> */
class ConversionRepository extends CoreRepository
{
    /** @return Builder<Conversion> */
    protected function newModelQuery(): Builder
    {
        return Conversion::query();
    }
}
