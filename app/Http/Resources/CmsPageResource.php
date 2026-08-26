<?php

namespace App\Http\Resources;

use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CmsPage */
class CmsPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'status' => $this->status->value,
            'excerpt' => $this->excerpt,
            'seo' => [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
            ],
            'settings' => $this->settings ?? [],
            'published_at' => $this->published_at,
            'sections' => CmsSectionResource::collection($this->whenLoaded('sections') ?? []),
            'revisions' => $this->whenLoaded('revisions', fn () => $this->revisions->map(fn ($revision) => [
                'id' => $revision->id,
                'version' => $revision->version,
                'change_note' => $revision->change_note,
                'created_at' => $revision->created_at,
                'created_by' => $revision->creator?->name,
            ])),
            'sections_count' => $this->whenCounted('sections'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
