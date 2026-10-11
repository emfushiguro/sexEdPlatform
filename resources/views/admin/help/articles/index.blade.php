@extends('layouts.admin')

@section('title', 'Help Articles')
@section('page-title', 'Help Articles')

@section('content')
<main class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <x-support.admin-tabs />
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Help Center</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Help Articles</h1>
            <p class="mt-2 text-sm text-gray-500">Write, preview, and publish clear navigation guides.</p>
        </div>
        <a href="{{ route('admin.help.articles.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Add article</a>
    </header>

    <form method="GET" class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end">
        <div>
            <label for="article-search" class="text-xs font-semibold text-gray-700">Search articles</label>
            <input id="article-search" name="search" value="{{ $search }}" placeholder="Title or category" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
        </div>
        <div>
            <label for="article-status" class="text-xs font-semibold text-gray-700">Status</label>
            <select id="article-status" name="status" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                <option value="">All statuses</option>
                @foreach(\App\Enums\HelpArticleStatus::cases() as $option)
                    <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            @if($search !== '' || $status)<a href="{{ route('admin.help.articles.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700">Clear</a>@endif
            <button class="min-h-10 rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Article</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Audience</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Updated</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($articles as $article)
                    <tr class="hover:bg-purple-50/30">
                        <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $article->title }}</p></td>
                        <td class="px-5 py-4 text-gray-600">{{ $article->category->name }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ collect($article->audiences)->map(fn ($audience) => str($audience)->headline())->join(', ') }}</td>
                        <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $article->status === \App\Enums\HelpArticleStatus::Published, 'bg-amber-50 text-amber-700' => $article->status === \App\Enums\HelpArticleStatus::Draft, 'bg-gray-100 text-gray-600' => $article->status === \App\Enums\HelpArticleStatus::Archived])>{{ $article->status->label() }}</span></td>
                        <td class="px-5 py-4 text-gray-600">{{ $article->updated_at?->format('M j, Y') }}</td>
                        <td class="px-5 py-4"><div class="flex justify-end" aria-label="Article actions"><a data-help-article-action="preview" href="{{ route('admin.help.articles.preview', $article) }}" title="Preview help article" aria-label="Preview help article" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-purple-200 text-purple-700 transition hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg><span class="sr-only">Preview help article</span></a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-semibold text-gray-900">No articles found</p><p class="mt-1 text-sm text-gray-500">Create a guide or clear the current filters.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $articles->links() }}
</main>
@endsection
