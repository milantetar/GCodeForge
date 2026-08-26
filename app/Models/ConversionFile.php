<?php

namespace App\Models;

use App\Enums\ConversionFileKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $conversion_id
 * @property ConversionFileKind $kind
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $checksum
 */
#[Fillable(['conversion_id', 'kind', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'checksum'])]
class ConversionFile extends Model
{
    /** @return BelongsTo<Conversion, $this> */
    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['kind' => ConversionFileKind::class];
    }
}
