@extends($supportLayout)

@section('title', 'Help Center | Conscious Connections')
@section('meta_description', 'Find clear guides for navigating Conscious Connections.')

@section('content')
<main class="min-h-screen bg-[#F9F7FF] text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="mx-auto max-w-6xl px-4 pb-12 pt-6 sm:px-6 sm:pb-16">
        <section class="overflow-hidden rounded-3xl px-5 py-8 text-white shadow-sm sm:px-10 sm:py-10" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 52%, #3B0CB1 100%);">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-100">Conscious Connections Support</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">How can we help?</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-purple-100 sm:text-base">Find clear answers for your account, learning, seminars, Community Hub, and more.</p>
            <form method="GET" action="{{ route($supportRoutes['help']['index']['name'], $supportRoutes['help']['index']['parameters']) }}" class="mt-7 max-w-2xl">
                <label for="help-search" class="sr-only">Search the Help Center</label>
                <div class="flex items-center gap-2 rounded-2xl bg-white p-2 shadow-lg shadow-purple-950/20">
                    <svg aria-hidden="true" class="ml-2 h-5 w-5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input id="help-search" name="q" value="{{ $search }}" type="search" placeholder="Search guides and common questions" class="min-w-0 flex-1 border-0 px-1 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0">
                    <button type="submit" class="min-h-11 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2">Search</button>
                </div>
            </form>
        </section>

        <div class="mt-10 space-y-12">
        @if($search !== '' || $categorySlug !== '')
            <section aria-labelledby="search-results-heading" class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700 dark:text-purple-300">Help Center</p>
                    <h2 id="search-results-heading" class="mt-1 text-2xl font-bold tracking-tight">
                        {{ $search !== '' ? 'Results for “'.$search.'”' : ($selectedCategory?->name ?? 'Guides') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $articles->total() }} {{ str('guide')->plural($articles->total()) }} found.</p>
                    </div>
                    <a href="{{ route($supportRoutes['help']['index']['name'], $supportRoutes['help']['index']['parameters']) }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-purple-700 transition hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-purple-400 dark:text-purple-300 dark:hover:bg-purple-950">Browse all topics</a>
                </div>
                @include('help.partials.search-results', ['articles' => $articles])
            </section>
        @else
            @if($recommendedArticles->isNotEmpty())
                <section aria-labelledby="recommended-heading" class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700 dark:text-purple-300">A useful place to start</p>
                        <h2 id="recommended-heading" class="mt-1 text-2xl font-bold tracking-tight">Recommended for {{ $audience === 'parent' ? 'parents' : str($audience)->plural() }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Common guides selected for what you can do on the platform.</p>
                    </div>
                    @include('help.partials.search-results', ['articles' => $recommendedArticles, 'showPagination' => false])
                </section>
            @endif

            <section aria-labelledby="categories-heading" class="space-y-4">
                <div>
                    <h2 id="categories-heading" class="text-2xl font-bold tracking-tight">Browse by topic</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose the area that matches what you are trying to do.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($categories as $category)
                        <a href="{{ route($supportRoutes['help']['index']['name'], array_merge($supportRoutes['help']['index']['parameters'], ['category' => $category->slug])) }}" class="group rounded-2xl border border-purple-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-purple-400 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-purple-700">
                            <div class="flex items-start gap-4">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-purple-50 text-purple-700 transition group-hover:bg-purple-100 dark:bg-purple-950 dark:text-purple-300">
                                    <x-support.category-icon :name="$category->icon_key" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-semibold text-gray-900 dark:text-white">{{ $category->name }}</span>
                                    <span class="mt-1 block text-xs font-medium text-purple-700 dark:text-purple-300">{{ $category->visible_articles_count }} {{ str('guide')->plural($category->visible_articles_count) }}</span>
                                </span>
                            </div>
                            <p class="mt-4 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $category->description ?: 'Explore guides and next steps.' }}</p>
                        </a>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="guides-heading" class="space-y-4">
                <div>
                    <h2 id="guides-heading" class="text-2xl font-bold tracking-tight">Popular guides</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Clear steps for common platform tasks.</p>
                </div>
                @include('help.partials.search-results', ['articles' => $articles])
            </section>
        @endif

            @auth
                @if(collect($supportActions)->isNotEmpty())
                    <section data-support-hub aria-labelledby="support-hub-heading" class="rounded-3xl border border-purple-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-7">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700 dark:text-purple-300">Your support</p>
                            <h2 id="support-hub-heading" class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Everything you need, in one place</h2>
                            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">Submit a ticket, check a reply, or share your experience without leaving the Help Center.</p>
                        </div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach($supportActions as $action)
                                <a data-support-action="{{ $action['key'] }}" href="{{ $action['url'] }}" class="group flex min-h-28 items-start gap-4 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 transition hover:-translate-y-0.5 hover:border-purple-300 hover:bg-purple-50/60 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-400 dark:border-gray-700 dark:bg-gray-800/70 dark:hover:border-purple-700 dark:hover:bg-purple-950/30">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-purple-100 text-purple-700 transition group-hover:bg-purple-200 dark:bg-purple-950 dark:text-purple-300 dark:group-hover:bg-purple-900">
                                        <x-ui.support-icon :name="$action['icon']" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white">
                                            {{ $action['label'] }}
                                            <svg aria-hidden="true" class="h-4 w-4 shrink-0 text-purple-600 transition-transform group-hover:translate-x-0.5 dark:text-purple-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                        </span>
                                        <span class="mt-1 block text-sm leading-5 text-gray-500 dark:text-gray-400">{{ $action['description'] }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endauth
        </div>
    </div>
</main>
@endsection
