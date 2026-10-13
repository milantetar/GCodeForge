<?php

namespace App\Services\Cms;

use App\Enums\CmsPageStatus;
use App\Http\Resources\CmsPageResource;
use App\Http\Resources\CmsSectionResource;
use App\Models\CmsPage;
use App\Models\User;
use App\Repositories\Cms\CmsPageRepository;
use App\Services\CoreService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @extends CoreService<CmsPage, CmsPageRepository> */
class CmsPageService extends CoreService
{
    private const CACHE_PREFIX = 'cms:published-page:v2:';

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

    /** @return array<string, mixed>|null */
    public function publishedContentBySlug(string $slug): ?array
    {
        return Cache::remember(self::CACHE_PREFIX.'content:'.$slug, now()->addMinutes(15), function () use ($slug): ?array {
            $page = CmsPage::query()->with('sections')
                ->where('status', CmsPageStatus::Published)
                ->where('published_at', '<=', now())
                ->where(fn ($query) => $query->where('published_slug', $slug)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('published_slug')->where('slug', $slug)))
                ->first();

            return $page === null ? null : ($page->published_content ?? $this->publicSnapshot($page));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CmsPage
    {
        return $this->transaction(function () use ($data, $actor): CmsPage {
            /** @var CmsPage $page */
            $page = $this->pageRepository->create([
                ...Arr::except($data, ['sections', 'change_note', 'lock_version']),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'published_at' => ($data['status'] ?? null) === CmsPageStatus::Published->value ? now() : null,
            ]);

            $this->syncSections($page, $data['sections'] ?? []);
            if ($page->status === CmsPageStatus::Published) {
                $this->capturePublication($page);
            }
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
            $page = $this->lockedPage($page, $data['lock_version'] ?? null);
            if (isset($data['slug']) && CmsPage::withTrashed()->whereKeyNot($page->id)
                ->where(fn ($query) => $query->where('slug', $data['slug'])->orWhere('published_slug', $data['slug']))->exists()) {
                throw ValidationException::withMessages(['slug' => 'This URL is reserved by another page. Choose another slug.']);
            }
            // Freeze legacy live content before any draft edits.
            if ($page->isPublished() && $page->published_content === null) {
                $this->capturePublication($page);
            }
            $oldSlug = $page->slug;
            $attributes = Arr::except($data, ['sections', 'change_note', 'lock_version', 'status']);
            $attributes['updated_by'] = $actor->id;
            $this->pageRepository->update($page, $attributes);
            $page->forceFill(['lock_version' => $page->lock_version + 1])->save();
            $this->syncSections($page, $data['sections'] ?? []);
            $this->createRevision($page, $actor, $this->changeNote($data));
            $this->forgetPublishedPage($oldSlug);
            $this->forgetPublishedPage($page->slug);

            return $this->pageRepository->findForManagement($page->refresh());
        });
    }

    public function publish(CmsPage $page, User $actor, int $version): CmsPage
    {
        return $this->transaction(function () use ($page, $actor, $version): CmsPage {
            $page = $this->lockedPage($page, $version);
            $this->forgetPublishedPage($page->published_slug ?? $page->slug);
            if (CmsPage::query()->whereKeyNot($page->id)->where('published_slug', $page->slug)->exists()) {
                throw ValidationException::withMessages(['slug' => 'This URL is already published by another page.']);
            }
            $page->forceFill([
                'status' => CmsPageStatus::Published,
                'published_at' => now(),
                'updated_by' => $actor->id,
                'lock_version' => $page->lock_version + 1,
            ])->save();
            $this->capturePublication($page);
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
        $this->forgetPublishedPage($page->published_slug ?? $slug);
    }

    public function restoreRevision(CmsPage $page, int $revisionId, int $version, User $actor): CmsPage
    {
        return $this->transaction(function () use ($page, $revisionId, $version, $actor): CmsPage {
            $page = $this->lockedPage($page, $version);
            $revision = $page->revisions()->findOrFail($revisionId);
            $snapshot = $revision->snapshot;

            return $this->update($page, [
                ...Arr::only($snapshot['page'], ['title', 'slug', 'type', 'excerpt', 'seo_title', 'seo_description', 'settings']),
                'sections' => $snapshot['sections'],
                'lock_version' => $version,
                'change_note' => 'Restored revision '.$revision->version.' as a draft',
            ], $actor);
        });
    }

    private function lockedPage(CmsPage $page, mixed $version): CmsPage
    {
        $locked = CmsPage::query()->lockForUpdate()->findOrFail($page->id);
        if (! is_numeric($version) || (int) $version !== $locked->lock_version) {
            throw ValidationException::withMessages(['lock_version' => 'Another editor changed this page. Reload before saving.']);
        }

        return $locked;
    }

    private function capturePublication(CmsPage $page): void
    {
        $page->forceFill([
            'published_slug' => $page->slug,
            'published_content' => $this->publicSnapshot($page),
        ])->save();
    }

    /** @return array<string, mixed> */
    private function publicSnapshot(CmsPage $page): array
    {
        $content = CmsPageResource::make($page->load('sections'))->resolve();
        unset($content['revisions']);
        $content['sections'] = $page->sections->where('is_visible', true)
            ->map(fn ($section) => CmsSectionResource::make($section)->resolve())->values()->all();

        return $content;
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
                'page' => Arr::except($page->toArray(), ['sections', 'revisions', 'published_content']),
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
        DB::afterCommit(function () use ($slug): void {
            Cache::forget(self::CACHE_PREFIX.$slug);
            Cache::forget(self::CACHE_PREFIX.'content:'.$slug);
        });
    }
}
