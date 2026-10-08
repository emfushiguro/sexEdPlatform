<?php

namespace App\Http\Controllers;

use App\Enums\HelpArticleStatus;
use App\Models\Connector;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Services\Support\HelpArticleVisibilityService;
use App\Services\Support\HelpAudienceResolver;
use App\Services\Support\SupportLayoutResolver;
use App\Services\Support\SupportRouteContext;
use App\Services\Support\TestimonialEligibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HelpCenterController extends Controller
{
    public function __construct(
        private readonly SupportLayoutResolver $layoutResolver,
        private readonly HelpAudienceResolver $audienceResolver,
        private readonly HelpArticleVisibilityService $visibility,
        private readonly SupportRouteContext $routeContext,
        private readonly TestimonialEligibility $testimonialEligibility,
    ) {}

    public function index(Request $request, ?Connector $connector = null): View
    {
        $supportLayout = $this->layoutResolver->resolve($request->user(), $connector);
        $audience = $this->audienceResolver->resolve($request->user(), $connector);
        $search = trim(substr((string) $request->query('q', ''), 0, 100));
        $categorySlug = trim(substr((string) $request->query('category', ''), 0, 140));
        if ($categorySlug === '' && $search !== '') {
            $categorySlug = (string) (HelpCategory::query()->where('is_active', true)->where(function ($q) use ($search) {
                $q->where('slug', $search)->orWhere('name', $search);
            })->value('slug') ?? '');
            if ($categorySlug !== '') {
                $search = '';
            }
        }
        $categories = HelpCategory::query()
            ->visibleToAudience($audience)
            ->withCount(['articles as visible_articles_count' => function (Builder $query) use ($audience): void {
                $query->where('status', HelpArticleStatus::Published)
                    ->whereNotNull('published_at')
                    ->where(function (Builder $audiences) use ($audience): void {
                        $audiences->whereJsonContains('audiences', 'all')
                            ->orWhereJsonContains('audiences', $audience);
                    });
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $articles = HelpArticle::query()
            ->publishedForAudience($audience)
            ->with('category')
            ->when($categorySlug !== '', fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $categorySlug)))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $keyword = strtolower($search);
                $query->where(function ($searchQuery) use ($term, $keyword): void {
                    $searchQuery->where('title', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhereJsonContains('keywords', $keyword)
                        ->orWhereHas('sections', fn ($sections) => $sections->where('body', 'like', $term));
                });
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $recommendedArticles = collect();
        if ($search === '' && $categorySlug === '') {
            $recommendedArticles = HelpArticle::query()
                ->publishedForAudience($audience)
                ->with('category')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit(4)
                ->get();
        }

        $supportRoutes = $this->routeContext->for($connector);
        $supportActions = [];
        if ($request->user()) {
            $supportActions = [
                [
                    'key' => 'submit-ticket',
                    'label' => 'Submit a Ticket',
                    'description' => 'Tell us about a problem, idea, or guide that needs attention.',
                    'url' => route($supportRoutes['feedback']['create']['name'], $supportRoutes['feedback']['create']['parameters']),
                    'icon' => 'feedback',
                ],
                [
                    'key' => 'my-tickets',
                    'label' => 'My Tickets',
                    'description' => 'Review your submitted feedback and follow-up messages.',
                    'url' => route($supportRoutes['feedback']['index']['name'], $supportRoutes['feedback']['index']['parameters']),
                    'icon' => 'my-feedback',
                ],
            ];

            if ($this->testimonialEligibility->canSubmit($request->user())) {
                $supportActions[] = [
                    'key' => 'share-experience',
                    'label' => 'Share Your Experience',
                    'description' => 'Share a public learning story with the Conscious Connections community.',
                    'url' => route('testimonials.create'),
                    'icon' => 'testimonial',
                ];
                $supportActions[] = [
                    'key' => 'my-testimonials',
                    'label' => 'My Testimonials',
                    'description' => 'See your testimonials and their current publication status.',
                    'url' => route('testimonials.index'),
                    'icon' => 'testimonial',
                ];
            }
        }

        return view('help.index', [
            'supportLayout' => $supportLayout,
            'connector' => $connector,
            'audience' => $audience,
            'categories' => $categories,
            'articles' => $articles,
            'recommendedArticles' => $recommendedArticles,
            'search' => $search,
            'categorySlug' => $categorySlug,
            'selectedCategory' => $categories->firstWhere('slug', $categorySlug),
            'supportRoutes' => $supportRoutes,
            'supportActions' => $supportActions,
            'feedbackCreateUrl' => route($supportRoutes['feedback']['create']['name'], $supportRoutes['feedback']['create']['parameters']),
        ]);
    }

    public function show(Request $request, HelpArticle $helpArticle, ?Connector $connector = null): View
    {
        $supportLayout = $this->layoutResolver->resolve($request->user(), $connector);
        $audience = $this->audienceResolver->resolve($request->user(), $connector);
        $article = $this->visibility->published($helpArticle, $audience)
            ->load(['category', 'sections'])
            ->loadCount([
                'votes as helpful_votes_count' => fn ($votes) => $votes->where('is_helpful', true),
                'votes as unhelpful_votes_count' => fn ($votes) => $votes->where('is_helpful', false),
            ]);

        $related = HelpArticle::query()
            ->publishedForAudience($audience)
            ->where('help_category_id', $article->help_category_id)
            ->where('id', '!=', $article->id)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(3)
            ->get();
        $currentVote = $request->user()?->id
            ? $article->votes()->where('user_id', $request->user()->id)->value('is_helpful')
            : null;

        $supportRoutes = $this->routeContext->for($connector);
        $articleSections = $article->sections->values()->map(fn ($section, int $index): array => [
            'model' => $section,
            'number' => $index + 1,
            'anchor' => 'section-'.($index + 1).'-'.Str::slug($section->heading ?: 'guide'),
        ]);
        $wordCount = $article->sections->sum(fn ($section): int => str_word_count(strip_tags($section->body)));
        $readingMinutes = max(1, (int) ceil($wordCount / 200));

        return view('help.show', [
            'supportLayout' => $supportLayout,
            'connector' => $connector,
            'audience' => $audience,
            'article' => $article,
            'related' => $related,
            'currentVote' => $currentVote,
            'articleSections' => $articleSections,
            'readingMinutes' => $readingMinutes,
            'supportRoutes' => $supportRoutes,
            'feedbackCreateUrl' => route($supportRoutes['feedback']['create']['name'], array_merge(
                $supportRoutes['feedback']['create']['parameters'],
                ['type' => 'help_content_issue'],
            )),
        ]);
    }

    public function connectorShow(Request $request, Connector $connector, HelpArticle $helpArticle): View
    {
        return $this->show($request, $helpArticle, $connector);
    }
}
