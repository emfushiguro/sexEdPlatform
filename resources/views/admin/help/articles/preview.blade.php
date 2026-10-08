@extends('layouts.admin')

@section('title', 'Preview Help Article')
@section('page-title', 'Preview Help Article')

@section('content')
<div class="mx-auto max-w-6xl px-4 pt-8"><x-support.admin-tabs /></div>
<main class="mx-auto max-w-6xl space-y-6 px-4 py-8">
    <nav aria-label="Breadcrumb">
        <a href="{{ route('admin.help.articles.index') }}" class="text-sm font-semibold text-purple-700">← Back to Help Articles</a>
    </nav>

    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Help Center preview</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-950">{{ $article->title }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ $article->category->name }} · Updated {{ $article->updated_at?->format('M j, Y') }}</p>
        </div>
        <span @class(['inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $article->status === \App\Enums\HelpArticleStatus::Published, 'bg-amber-50 text-amber-700' => $article->status === \App\Enums\HelpArticleStatus::Draft, 'bg-gray-100 text-gray-600' => $article->status === \App\Enums\HelpArticleStatus::Archived])>{{ $article->status->label() }}</span>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">
        <article class="rounded-2xl border border-purple-100 bg-white p-6 shadow-sm sm:p-10">
            <p class="text-sm leading-6 text-gray-600">{{ $article->summary }}</p>
            <div class="mt-8 space-y-8">
                @foreach($article->sections as $section)
                    <section>
                        @if($section->heading)<h2 class="text-xl font-semibold text-gray-900">{{ $section->heading }}</h2>@endif
                        <p class="mt-2 whitespace-pre-line text-sm leading-7 text-gray-700">{{ $section->body }}</p>
                        @if($section->image_path)<img src="{{ route('admin.help.articles.section.image', ['helpArticle' => $article, 'section' => $section]) }}" alt="{{ $section->image_alt_text ?: 'Screenshot for '.($section->heading ?: $article->title) }}" class="mt-4 rounded-xl border border-gray-200">@endif
                    </section>
                @endforeach
            </div>
        </article>

        <aside aria-labelledby="help-article-actions" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 id="help-article-actions" class="text-lg font-bold text-gray-950">Article actions</h2>
            <p class="mt-1 text-sm leading-6 text-gray-500">Manage this guide from its preview.</p>

            <div class="mt-5 space-y-2 border-t border-gray-100 pt-5">
                <a data-help-article-action="edit" href="{{ route('admin.help.articles.edit', $article) }}" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-gray-300 px-3.5 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-400">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m4 16.5-.75 3.75L7 19.5 18.25 8.25a2.65 2.65 0 0 0-3.75-3.75L3.25 15.75"/><path stroke-linecap="round" d="m13 6 3.75 3.75"/></svg>
                    <span>Edit article</span>
                </a>

                @if($article->status !== \App\Enums\HelpArticleStatus::Published)
                    <form method="POST" action="{{ route('admin.help.articles.publish', $article) }}" data-confirm-submit data-confirm-title="Publish article?" data-confirm-text="Make this guide visible in the Help Center." data-confirm-icon="question" data-confirm-button="Publish">
                        @csrf
                        <button data-help-article-action="publish" type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.25 4.25L19 7"/></svg>
                            <span>Publish article</span>
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.help.articles.archive', $article) }}" data-confirm-submit data-confirm-title="Archive article?" data-confirm-text="Remove this guide from the public Help Center." data-confirm-icon="warning" data-confirm-button="Archive">
                        @csrf
                        <button data-help-article-action="archive" type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-rose-200 px-3.5 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-400">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5h16M6.5 7.5v10.25A2.25 2.25 0 0 0 8.75 20h6.5a2.25 2.25 0 0 0 2.25-2.25V7.5M9 4h6l1.5 3.5h-9L9 4Z"/></svg>
                            <span>Archive article</span>
                        </button>
                    </form>
                @endif
            </div>
        </aside>
    </div>
</main>
@endsection
