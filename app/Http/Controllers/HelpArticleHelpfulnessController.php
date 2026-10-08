<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHelpArticleHelpfulnessRequest;
use App\Models\HelpArticle;
use App\Services\Support\HelpAudienceResolver;
use Illuminate\Http\RedirectResponse;

class HelpArticleHelpfulnessController extends Controller
{
    public function __construct(private readonly HelpAudienceResolver $audienceResolver) {}

    public function update(UpdateHelpArticleHelpfulnessRequest $request, HelpArticle $helpArticle): RedirectResponse
    {
        $audience = $this->audienceResolver->resolve($request->user());
        $article = HelpArticle::query()
            ->publishedForAudience($audience)
            ->whereKey($helpArticle->id)
            ->firstOrFail();

        $article->votes()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['is_helpful' => $request->boolean('is_helpful')],
        );

        return back()->with('success', 'Thanks for helping us improve this guide.');
    }
}
