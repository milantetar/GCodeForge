<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Cms\SaveCmsPageRequest;
use App\Http\Resources\CmsPageResource;
use App\Models\CmsPage;
use App\Models\User;
use App\Services\Cms\CmsPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsPageController extends BaseController
{
    public function __construct(private CmsPageService $pageService) {}

    public function index(): JsonResponse
    {
        return $this->successResponse(
            'Pages retrieved.',
            CmsPageResource::collection($this->pageService->paginateForManagement()),
        );
    }

    public function store(SaveCmsPageRequest $request): JsonResponse
    {
        $page = $this->pageService->create($request->cmsData(), $this->actor($request));

        return $this->createdResponse('Page created.', new CmsPageResource($page));
    }

    public function show(CmsPage $cmsPage): JsonResponse
    {
        return $this->successResponse(
            'Page retrieved.',
            new CmsPageResource($cmsPage->load(['sections', 'revisions.creator'])),
        );
    }

    public function update(SaveCmsPageRequest $request, CmsPage $cmsPage): JsonResponse
    {
        $page = $this->pageService->update($cmsPage, $request->cmsData(), $this->actor($request));

        return $this->successResponse('Page updated.', new CmsPageResource($page));
    }

    public function publish(Request $request, CmsPage $cmsPage): JsonResponse
    {
        $request->user()?->can('cms.publish') || abort(403);
        $page = $this->pageService->publish($cmsPage, $this->actor($request));

        return $this->successResponse('Page published.', new CmsPageResource($page));
    }

    public function destroy(Request $request, CmsPage $cmsPage): JsonResponse
    {
        $request->user()?->can('cms.manage') || abort(403);
        $this->pageService->delete($cmsPage);

        return $this->successResponse('Page archived.');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
