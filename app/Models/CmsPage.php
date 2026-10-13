<?php

namespace App\Models;

use App\Enums\CmsPageStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $type
 * @property CmsPageStatus $status
 * @property string|null $excerpt
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property array<string, mixed>|null $published_content
 * @property string|null $published_slug
 * @property int $lock_version
 */
#[Fillable([
    'title',
    'slug',
    'type',
    'status',
    'excerpt',
    'seo_title',
    'seo_description',
    'settings',
    'published_at',
    'created_by',
    'updated_by',
])]
class CmsPage extends Model
{
    use SoftDeletes;

    /** @return HasMany<CmsSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class)->orderBy('sort_order');
    }

    /** @return HasMany<CmsPageRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(CmsPageRevision::class)->latest('version');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === CmsPageStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CmsPageStatus::class,
            'settings' => 'array',
            'published_at' => 'immutable_datetime',
            'published_content' => 'array',
            'lock_version' => 'integer',
        ];
    }
}
