<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\SaveCmsPageRequest;
use App\Http\Resources\CmsPageResource;
use App\Models\CmsPage;
use App\Models\User;
use App\Services\Cms\CmsPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsPageController extends Controller
{
    public function __construct(private CmsPageService $pageService) {}

    public function index(): Response
    {
        $pages = $this->pageService->paginateForManagement();

        return Inertia::render('admin/cms/index', [
            'pages' => [
                'data' => collect($pages->items())
                    ->map(fn (CmsPage $page) => CmsPageResource::make($page)->resolve())
                    ->all(),
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ],
        ]);
    }

    public function store(SaveCmsPageRequest $request): RedirectResponse
    {
        $page = $this->pageService->create($request->cmsData(), $this->actor($request));

        return redirect()->route('admin.cms.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(Request $request, CmsPage $cmsPage): Response
    {
        return Inertia::render('admin/cms/edit', [
            'cmsPage' => CmsPageResource::make(
                $cmsPage->load(['sections', 'revisions.creator']),
            )->resolve($request),
        ]);
    }

    public function update(SaveCmsPageRequest $request, CmsPage $cmsPage): RedirectResponse
    {
        $this->pageService->update($cmsPage, $request->cmsData(), $this->actor($request));

        return back()->with('success', 'Draft saved.');
    }

    public function publish(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $request->user()?->can('cms.publish') || abort(403);
        $request->validate(['lock_version' => ['required', 'integer', 'min:0']]);
        $this->pageService->publish($cmsPage, $this->actor($request), $request->integer('lock_version'));

        return back()->with('success', 'Page published.');
    }

    public function destroy(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $request->user()?->can('cms.manage') || abort(403);
        $this->pageService->delete($cmsPage);

        return redirect()->route('admin.cms.pages.index')->with('success', 'Page archived.');
    }

    public function preview(Request $request, CmsPage $cmsPage): Response
    {
        return Inertia::render('welcome', [
            'cmsPage' => CmsPageResource::make($cmsPage->load('sections'))->resolve($request),
            'preview' => true,
        ]);
    }

    public function restore(Request $request, CmsPage $cmsPage, int $revision): RedirectResponse
    {
        $request->validate(['lock_version' => ['required', 'integer', 'min:0']]);
        $this->pageService->restoreRevision($cmsPage, $revision, $request->integer('lock_version'), $this->actor($request));

        return back()->with('success', 'Revision restored as a draft. Publish when ready.');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
