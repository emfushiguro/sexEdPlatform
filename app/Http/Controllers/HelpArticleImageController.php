<?php

namespace App\Http\Controllers;

use App\Models\Connector;
use App\Models\HelpArticle;
use App\Models\HelpArticleSection;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Support\HelpArticleVisibilityService;
use App\Services\Support\HelpAudienceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HelpArticleImageController extends Controller
{
    public function __construct(
        private readonly HelpArticleVisibilityService $visibility,
        private readonly HelpAudienceResolver $audiences,
        private readonly ConnectorAccessService $connectorAccess,
    ) {}

    public function show(Request $request, HelpArticle $helpArticle, HelpArticleSection $section): BinaryFileResponse
    {
        $article = $this->visibility->published($helpArticle, $this->audiences->resolve($request->user()));

        return $this->serve($article, $section, $this->visibility->isPubliclyCacheable($article));
    }

    public function connectorShow(Request $request, Connector $connector, HelpArticle $helpArticle, HelpArticleSection $section): BinaryFileResponse
    {
        if (! $request->user()->hasRole('admin')) {
            $this->connectorAccess->abortUnlessWorkspace($request->user(), $connector);
        }

        $article = $this->visibility->published($helpArticle, 'connector');

        return $this->serve($article, $section, false);
    }

    public function adminShow(HelpArticle $helpArticle, HelpArticleSection $section): BinaryFileResponse
    {
        return $this->serve($this->visibility->admin($helpArticle), $section, false);
    }

    private function serve(HelpArticle $article, HelpArticleSection $section, bool $public): BinaryFileResponse
    {
        abort_unless((int) $section->help_article_id === (int) $article->getKey(), 404);

        $path = trim((string) $section->image_path, '/');
        $prefix = 'help/articles/'.$article->getKey().'/';
        abort_unless($path !== '' && Str::startsWith($path, $prefix) && ! Str::contains($path, '..'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $response = response()->file($disk->path($path), [
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $public ? 'public, max-age=3600' : 'private, no-store',
        ]);

        $response->headers->set('Cache-Control', $public ? 'public, max-age=3600' : 'private, no-store', true);

        return $response;
    }
}
