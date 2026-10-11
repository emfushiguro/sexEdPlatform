@if($articles->count())
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        @foreach($articles as $article)
            <a href="{{ route($supportRoutes['help']['show']['name'], array_merge($supportRoutes['help']['show']['parameters'], ['helpArticle' => $article->slug])) }}" class="group flex items-center gap-4 border-b border-gray-100 p-5 transition last:border-b-0 hover:bg-purple-50/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-purple-400 dark:border-gray-800 dark:hover:bg-purple-950/30">
                <span class="min-w-0 flex-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-purple-700 dark:text-purple-300">{{ $article->category->name }}</span>
                    <span class="mt-1 block font-semibold text-gray-900 dark:text-white">{{ $article->title }}</span>
                    <span class="mt-1 block text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $article->summary }}</span>
                </span>
                <svg aria-hidden="true" class="h-5 w-5 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        @endforeach
    </div>
    @if(($showPagination ?? true) && method_exists($articles, 'hasPages') && $articles->hasPages())
        <div class="pt-2">{{ $articles->links() }}</div>
    @endif
@else
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
        <h3 class="font-semibold text-gray-900 dark:text-white">No guides found yet</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Try another search or browse the available topics.</p>
        <a href="{{ route($supportRoutes['help']['index']['name'], $supportRoutes['help']['index']['parameters']) }}" class="mt-4 inline-flex rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Browse all guides</a>
    </div>
@endif
