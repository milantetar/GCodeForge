<?php

namespace App\Repositories\Cms;

use App\Enums\CmsPageStatus;
use App\Models\CmsPage;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;

/** @extends CoreRepository<CmsPage> */
class CmsPageRepository extends CoreRepository
{
    /** @return Builder<CmsPage> */
    protected function newModelQuery(): Builder
    {
        return CmsPage::query();
    }

    public function findPublishedBySlug(string $slug): ?CmsPage
    {
        return $this->query()
            ->with(['sections' => fn ($query) => $query->where('is_visible', true)])
            ->where('slug', $slug)
            ->where('status', CmsPageStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();
    }

    public function findForManagement(CmsPage $page): CmsPage
    {
        return $page->load(['sections', 'revisions.creator']);
    }
}
