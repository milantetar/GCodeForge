<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BaseController;
use App\Services\Cms\CmsPageService;
use Illuminate\Http\JsonResponse;

class PublicContentController extends BaseController
{
    public function __construct(private CmsPageService $pageService) {}

    public function show(string $slug): JsonResponse
    {
        $page = $this->pageService->publishedContentBySlug($slug);

        if ($page === null) {
            return $this->notFoundResponse('Published page not found.');
        }

        return $this->successResponse('Page retrieved.', $page);
    }
}
