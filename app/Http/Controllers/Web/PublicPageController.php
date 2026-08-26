<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\CmsPageResource;
use App\Services\Cms\CmsPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __construct(private CmsPageService $pageService) {}

    public function home(Request $request): Response
    {
        return Inertia::render('welcome', [
            'cmsPage' => $this->resolvePage($request, 'home'),
        ]);
    }

    public function legal(Request $request, string $document): Response
    {
        abort_unless(in_array($document, ['terms', 'privacy'], true), 404);

        return Inertia::render('legal', [
            'document' => $document,
            'cmsPage' => $this->resolvePage($request, $document),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function resolvePage(Request $request, string $slug): ?array
    {
        $page = $this->pageService->publishedBySlug($slug);

        return $page === null ? null : CmsPageResource::make($page)->resolve($request);
    }
}
