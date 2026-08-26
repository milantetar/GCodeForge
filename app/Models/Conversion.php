<?php

namespace App\Models;

use App\Enums\ConversionStatus;
use App\Enums\ConverterType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $project_id
 * @property int|null $machine_profile_id
 * @property ConverterType $type
 * @property ConversionStatus $status
 * @property array<string, mixed> $options
 * @property string $input_hash
 * @property list<string>|null $warnings
 * @property string|null $error_message
 * @property int $input_lines
 * @property int $output_lines
 * @property int|null $duration_ms
 * @property Carbon|null $queued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 */
#[Fillable([
    'public_id',
    'user_id',
    'project_id',
    'machine_profile_id',
    'type',
    'status',
    'options',
    'input_hash',
    'warnings',
    'error_message',
    'input_lines',
    'output_lines',
    'duration_ms',
    'queued_at',
    'started_at',
    'completed_at',
])]
class Conversion extends Model
{
    use SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<MachineProfile, $this> */
    public function machineProfile(): BelongsTo
    {
        return $this->belongsTo(MachineProfile::class);
    }

    /** @return HasMany<ConversionFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ConversionFile::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ConverterType::class,
            'status' => ConversionStatus::class,
            'options' => 'array',
            'warnings' => 'array',
            'queued_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
