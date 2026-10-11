<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HelpArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderHelpArticlesRequest;
use App\Http\Requests\Admin\StoreHelpArticleRequest;
use App\Http\Requests\Admin\UpdateHelpArticleRequest;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Services\Support\HelpArticlePersistenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpArticleController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = trim(mb_substr((string) $request->query('search', ''), 0, 100));
        $articles = HelpArticle::query()
            ->with('category')
            ->when(in_array($status, HelpArticleStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('title', 'like', $term)
                        ->orWhere('slug', 'like', $term)
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $term));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.help.articles.index', [
            'articles' => $articles,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.help.articles.form', [
            'article' => new HelpArticle(['status' => HelpArticleStatus::Draft, 'audiences' => ['all'], 'keywords' => []]),
            'categories' => HelpCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreHelpArticleRequest $request, HelpArticlePersistenceService $persistence): RedirectResponse
    {
        $article = $persistence->create($request->validated(), $request->user());

        return redirect()->route('admin.help.articles.index')->with('success', "Help article {$article->title} created.");
    }

    public function edit(HelpArticle $helpArticle): View
    {
        return view('admin.help.articles.form', [
            'article' => $helpArticle->load('sections'),
            'categories' => HelpCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateHelpArticleRequest $request, HelpArticle $helpArticle, HelpArticlePersistenceService $persistence): RedirectResponse
    {
        $persistence->update($helpArticle, $request->validated(), $request->user());

        return redirect()->route('admin.help.articles.index')->with('success', 'Help article updated.');
    }

    public function preview(HelpArticle $helpArticle): View
    {
        return view('admin.help.articles.preview', ['article' => $helpArticle->load(['category', 'sections'])]);
    }

    public function publish(HelpArticle $helpArticle): RedirectResponse
    {
        $helpArticle->update([
            'status' => HelpArticleStatus::Published,
            'published_at' => $helpArticle->published_at ?? now(),
        ]);

        return back()->with('success', 'Help article published.');
    }

    public function archive(HelpArticle $helpArticle): RedirectResponse
    {
        $helpArticle->update(['status' => HelpArticleStatus::Archived]);

        return back()->with('success', 'Help article archived.');
    }

    public function order(OrderHelpArticlesRequest $request): RedirectResponse
    {
        foreach ($request->validated('items') as $item) {
            HelpArticle::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Help articles reordered.');
    }
}
