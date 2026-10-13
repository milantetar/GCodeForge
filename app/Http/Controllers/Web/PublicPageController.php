<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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
        return $this->pageService->publishedContentBySlug($slug);
    }

    public function show(Request $request, string $slug): Response
    {
        $page = $this->resolvePage($request, $slug);
        abort_if($page === null, 404);

        return Inertia::render('welcome', ['cmsPage' => $page]);
    }
}
