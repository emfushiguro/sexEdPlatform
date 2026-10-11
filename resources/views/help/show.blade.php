@extends($supportLayout)

@section('title', $article->title.' | Help Center')
@section('meta_description', $article->summary)

@section('content')
<main class="min-h-screen bg-[#F9F7FF] text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        <nav aria-label="Breadcrumb" class="mt-7 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route($supportRoutes['help']['index']['name'], $supportRoutes['help']['index']['parameters']) }}" class="font-semibold text-purple-700 hover:text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-400 dark:text-purple-300">Help Center</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route($supportRoutes['help']['index']['name'], array_merge($supportRoutes['help']['index']['parameters'], ['category' => $article->category->slug])) }}" class="hover:text-gray-800 dark:hover:text-gray-200">{{ $article->category->name }}</a>
        </nav>

        <header class="mt-6 max-w-4xl">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-purple-700 dark:text-purple-300">{{ $article->category->name }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-950 sm:text-4xl dark:text-white">{{ $article->title }}</h1>
            <p class="mt-4 text-base leading-7 text-gray-600 sm:text-lg dark:text-gray-300">{{ $article->summary }}</p>
            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                <span>{{ $readingMinutes }} min read</span>
                <span>Last updated {{ $article->updated_at->format('M j, Y') }}</span>
            </div>
        </header>

        @if($articleSections->isNotEmpty())
            <details class="mt-7 rounded-2xl border border-purple-100 bg-white p-4 shadow-sm lg:hidden dark:border-gray-800 dark:bg-gray-900">
                <summary class="cursor-pointer font-semibold text-gray-900 dark:text-white">On this page</summary>
                <ol class="mt-3 space-y-2 border-l border-purple-100 pl-4 dark:border-gray-700">
                    @foreach($articleSections as $item)
                        <li><a href="#{{ $item['anchor'] }}" class="text-sm text-gray-600 hover:text-purple-700 dark:text-gray-300 dark:hover:text-purple-300">{{ $item['model']->heading ?: 'Guide section '.$item['number'] }}</a></li>
                    @endforeach
                </ol>
            </details>
        @endif

        <div class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,1fr)_14rem] lg:items-start">
            <article class="rounded-2xl border border-purple-100 bg-white px-5 py-7 shadow-sm sm:px-9 sm:py-10 dark:border-gray-800 dark:bg-gray-900">
                <div class="space-y-10">
                    @foreach($articleSections as $item)
                        @php
                            $section = $item['model'];
                        @endphp
                        <section id="{{ $item['anchor'] }}" class="scroll-mt-24">
                            @if($section->heading)
                                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $section->heading }}</h2>
                            @endif
                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-gray-700 sm:text-base dark:text-gray-300">{{ $section->body }}</p>
                            @if($section->image_path)
                                <img src="{{ route($supportRoutes['help']['section_image']['name'], array_merge($supportRoutes['help']['section_image']['parameters'], ['helpArticle' => $article->slug, 'section' => $section])) }}" alt="{{ $section->image_alt_text ?: 'Screenshot for '.($section->heading ?: $article->title) }}" class="mt-5 rounded-2xl border border-gray-200 dark:border-gray-700">
                            @endif
                        </section>
                    @endforeach
                </div>
            </article>

            @if($articleSections->isNotEmpty())
                <aside class="sticky top-6 hidden rounded-2xl border border-purple-100 bg-white p-5 shadow-sm lg:block dark:border-gray-800 dark:bg-gray-900" aria-labelledby="article-contents-heading">
                    <h2 id="article-contents-heading" class="font-semibold text-gray-950 dark:text-white">On this page</h2>
                    <ol class="mt-4 space-y-3 border-l border-purple-100 pl-4 dark:border-gray-700">
                        @foreach($articleSections as $item)
                            <li><a href="#{{ $item['anchor'] }}" class="text-sm leading-5 text-gray-600 transition hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-400 dark:text-gray-300 dark:hover:text-purple-300">{{ $item['model']->heading ?: 'Guide section '.$item['number'] }}</a></li>
                        @endforeach
                    </ol>
                </aside>
            @endif
        </div>

        @php
            $yesSelected = $currentVote === true || $currentVote === 1;
            $noSelected = $currentVote === false || $currentVote === 0;
        @endphp
        <section aria-labelledby="guide-feedback" class="mt-7 rounded-2xl border border-purple-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 id="guide-feedback" class="text-lg font-bold text-gray-950 dark:text-white">Was this guide helpful?</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $article->helpful_votes_count ?? 0 }} helpful · {{ $article->unhelpful_votes_count ?? 0 }} not helpful</p>
            @auth
                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('help.helpfulness.update', $article) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="is_helpful" value="1">
                        <button type="submit" aria-pressed="{{ $yesSelected ? 'true' : 'false' }}" @class(['min-h-11 rounded-xl border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-purple-400', 'border-brand-700 bg-brand-700 text-white' => $yesSelected, 'border-purple-200 text-purple-800 hover:bg-purple-50 dark:border-purple-800 dark:text-purple-300 dark:hover:bg-purple-950' => ! $yesSelected])>Yes</button>
                    </form>
                    <form method="POST" action="{{ route('help.helpfulness.update', $article) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="is_helpful" value="0">
                        <button type="submit" aria-pressed="{{ $noSelected ? 'true' : 'false' }}" @class(['min-h-11 rounded-xl border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-purple-400', 'border-brand-700 bg-brand-700 text-white' => $noSelected, 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' => ! $noSelected])>No</button>
                    </form>
                    <a href="{{ $feedbackCreateUrl }}" class="inline-flex min-h-11 items-center rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2">Report an issue with this guide</a>
                </div>
            @else
                <a href="{{ route('login') }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900">Sign in to rate this guide</a>
            @endauth
        </section>

        @if($related->isNotEmpty())
            <section aria-labelledby="related-guides" class="mt-10">
                <h2 id="related-guides" class="text-xl font-bold text-gray-950 dark:text-white">Related guides</h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    @foreach($related as $relatedArticle)
                        <a href="{{ route($supportRoutes['help']['show']['name'], array_merge($supportRoutes['help']['show']['parameters'], ['helpArticle' => $relatedArticle->slug])) }}" class="flex min-h-14 items-center gap-4 border-b border-gray-100 p-5 transition last:border-b-0 hover:bg-purple-50/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-purple-400 dark:border-gray-800 dark:hover:bg-purple-950/30">
                            <span class="min-w-0 flex-1 font-semibold text-gray-900 dark:text-white">{{ $relatedArticle->title }}</span>
                            <svg aria-hidden="true" class="h-4 w-4 shrink-0 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</main>
@endsection
