<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $machine_profile_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property ProjectStatus $status
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $last_activity_at
 */
#[Fillable(['user_id', 'machine_profile_id', 'name', 'slug', 'description', 'status', 'settings', 'last_activity_at'])]
class Project extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<MachineProfile, $this> */
    public function machineProfile(): BelongsTo
    {
        return $this->belongsTo(MachineProfile::class);
    }

    /** @return HasMany<Conversion, $this> */
    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'settings' => 'array',
            'last_activity_at' => 'immutable_datetime',
        ];
    }
}
