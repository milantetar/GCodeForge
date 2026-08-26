<?php

namespace App\Services\Cms;

use App\Enums\CmsPageStatus;
use App\Models\CmsPage;
use App\Models\User;
use App\Repositories\Cms\CmsPageRepository;
use App\Services\CoreService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/** @extends CoreService<CmsPage, CmsPageRepository> */
class CmsPageService extends CoreService
{
    private const CACHE_PREFIX = 'cms:published-page:';

    public function __construct(
        protected CmsPageRepository $pageRepository,
    ) {
        parent::__construct($pageRepository);
    }

    /** @return LengthAwarePaginator<int, CmsPage> */
    public function paginateForManagement(int $perPage = 15): LengthAwarePaginator
    {
        return $this->pageRepository->query()
            ->withCount('sections')
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function publishedBySlug(string $slug): ?CmsPage
    {
        $key = self::CACHE_PREFIX.$slug;
        $cached = Cache::get($key);

        if ($cached instanceof CmsPage) {
            return $cached;
        }

        $page = $this->pageRepository->findPublishedBySlug($slug);

        if ($page !== null) {
            Cache::put($key, $page, now()->addHour());
        }

        return $page;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CmsPage
    {
        return $this->transaction(function () use ($data, $actor): CmsPage {
            /** @var CmsPage $page */
            $page = $this->pageRepository->create([
                ...Arr::except($data, ['sections', 'change_note']),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'published_at' => ($data['status'] ?? null) === CmsPageStatus::Published->value ? now() : null,
            ]);

            $this->syncSections($page, $data['sections'] ?? []);
            $this->createRevision($page, $actor, $this->changeNote($data));
            $this->forgetPublishedPage($page->slug);

            return $this->pageRepository->findForManagement($page);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CmsPage $page, array $data, User $actor): CmsPage
    {
        return $this->transaction(function () use ($page, $data, $actor): CmsPage {
            $oldSlug = $page->slug;
            $attributes = Arr::except($data, ['sections', 'change_note']);
            $attributes['updated_by'] = $actor->id;

            if (($data['status'] ?? null) !== CmsPageStatus::Published->value) {
                $attributes['published_at'] = null;
            }

            $this->pageRepository->update($page, $attributes);
            $this->syncSections($page, $data['sections'] ?? []);
            $this->createRevision($page, $actor, $this->changeNote($data));
            $this->forgetPublishedPage($oldSlug);
            $this->forgetPublishedPage($page->slug);

            return $this->pageRepository->findForManagement($page->refresh());
        });
    }

    public function publish(CmsPage $page, User $actor): CmsPage
    {
        return $this->transaction(function () use ($page, $actor): CmsPage {
            $page->forceFill([
                'status' => CmsPageStatus::Published,
                'published_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->createRevision($page, $actor, 'Published page');
            $this->forgetPublishedPage($page->slug);

            return $this->pageRepository->findForManagement($page->refresh());
        });
    }

    public function delete(CmsPage $page): void
    {
        $slug = $page->slug;
        $this->pageRepository->delete($page);
        $this->forgetPublishedPage($slug);
    }

    private function syncSections(CmsPage $page, mixed $sections): void
    {
        if (! is_array($sections)) {
            return;
        }

        $keys = [];

        foreach (array_values($sections) as $index => $section) {
            if (! is_array($section) || ! is_string($section['key'] ?? null)) {
                continue;
            }

            $key = $section['key'];
            $keys[] = $key;

            $page->sections()->updateOrCreate(
                ['key' => $key],
                [
                    'type' => $section['type'],
                    'name' => $section['name'],
                    'content' => $section['content'],
                    'settings' => $section['settings'] ?? null,
                    'sort_order' => $index,
                    'is_visible' => $section['is_visible'] ?? true,
                ],
            );
        }

        $query = $page->sections();

        if ($keys === []) {
            $query->delete();
        } else {
            $query->whereNotIn('key', $keys)->delete();
        }
    }

    private function createRevision(CmsPage $page, User $actor, ?string $changeNote): void
    {
        $page->load('sections');
        $version = ((int) $page->revisions()->max('version')) + 1;

        $page->revisions()->create([
            'version' => $version,
            'snapshot' => [
                'page' => Arr::except($page->toArray(), ['sections', 'revisions']),
                'sections' => $page->sections->values()->toArray(),
            ],
            'change_note' => $changeNote,
            'created_by' => $actor->id,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function changeNote(array $data): ?string
    {
        $note = $data['change_note'] ?? null;

        return is_string($note) && $note !== '' ? $note : null;
    }

    private function forgetPublishedPage(string $slug): void
    {
        Cache::forget(self::CACHE_PREFIX.$slug);
    }
}
