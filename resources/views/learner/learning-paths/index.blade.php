@extends('layouts.learner-app')

@section('title', 'Learning Paths')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
@endphp

<div class="mx-auto max-w-7xl space-y-8">
    <header class="max-w-3xl">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-purple-600 dark:text-purple-300">Guided learning</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Learning Paths</h1>
        <p class="mt-3 text-base leading-7 text-gray-600 dark:text-gray-300">
            Follow a thoughtful sequence of modules built to help you learn one step at a time.
        </p>
    </header>

    @if($paths->isEmpty())
        <section class="rounded-3xl border border-dashed border-purple-200 bg-white p-10 text-center shadow-sm dark:border-purple-800/60 dark:bg-gray-800">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200" aria-hidden="true">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5.75A2.75 2.75 0 0 1 7.75 3h8.5A2.75 2.75 0 0 1 19 5.75v12.5A2.75 2.75 0 0 1 16.25 21h-8.5A2.75 2.75 0 0 1 5 18.25V5.75Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 7.5h7M8.5 11h7M8.5 14.5h4" />
                </svg>
            </div>
            <h2 class="mt-5 text-xl font-semibold text-gray-900 dark:text-white">No learning paths yet</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-gray-600 dark:text-gray-300">
                Browse individual modules while new guided paths are being prepared for your learner group.
            </p>
            <a href="{{ route('learner.modules.index') }}"
               class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                Browse modules
            </a>
        </section>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($paths as $path)
                @php
                    $summary = $summaries[$path->id] ?? [
                        'progress_percentage' => 0,
                        'completed_modules' => 0,
                        'actionable_modules' => 0,
                        'action_url' => null,
                        'action_label' => null,
                    ];
                    $thumbnail = $path->thumbnail
                        ? (Str::startsWith($path->thumbnail, ['http://', 'https://', '//'])
                            ? $path->thumbnail
                            : Storage::url(ltrim($path->thumbnail, '/')))
                        : null;
                    $actionUrl = $summary['action_url'] ?? route('learner.learning-paths.show', $path);
                    $actionLabel = $summary['action_label'] ?? 'View path';
                @endphp

                <article class="flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    @if($thumbnail)
                        <img src="{{ $thumbnail }}" alt="{{ $path->title }} thumbnail" class="h-44 w-full object-cover">
                    @else
                        <div class="flex h-44 w-full items-center justify-center bg-gradient-to-br from-purple-700 via-indigo-700 to-fuchsia-600 text-white" aria-label="Learning path placeholder">
                            <svg class="h-14 w-14 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h9A2.75 2.75 0 0 1 19.25 5.75v12.5A2.75 2.75 0 0 1 16.5 21h-9a2.75 2.75 0 0 1-2.75-2.75V5.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h4" />
                            </svg>
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex flex-wrap gap-2" aria-label="Learner categories">
                            @foreach($path->learnerCategories as $category)
                                <span class="rounded-full bg-purple-100 px-2.5 py-1 text-[11px] font-semibold text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">
                                    {{ ucfirst($category->category) }}
                                </span>
                            @endforeach
                        </div>
                        <h2 class="mt-4 text-xl font-bold leading-tight text-gray-900 dark:text-white">{{ $path->title }}</h2>
                        <p class="mt-3 min-h-[3rem] text-sm leading-6 text-gray-600 dark:text-gray-300">{{ Str::limit($path->description, 140) }}</p>

                        <div class="mt-5" role="group" aria-label="Learning path progress">
                            <div class="flex items-center justify-between gap-3 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <span>{{ $summary['completed_modules'] }} of {{ $summary['actionable_modules'] }} modules completed</span>
                                <span>{{ $summary['progress_percentage'] }}%</span>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700"
                                 role="progressbar"
                                 aria-label="Progress for {{ $path->title }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-valuenow="{{ $summary['progress_percentage'] }}">
                                <span class="block h-full rounded-full bg-gradient-to-r from-purple-600 to-fuchsia-500" style="width: {{ $summary['progress_percentage'] }}%"></span>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ $actionUrl ?? route('learner.learning-paths.show', $path) }}"
                               class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                                {{ $actionLabel ?: 'View path' }}
                            </a>
                            <a href="{{ route('learner.learning-paths.show', $path) }}"
                               class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-purple-400 hover:text-purple-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:border-gray-600 dark:text-gray-200 dark:hover:border-purple-400 dark:hover:text-purple-200 dark:focus-visible:ring-offset-gray-800">
                                View
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <nav aria-label="Learning path pages" class="pt-2">
            {{ $paths->links() }}
        </nav>
    @endif
</div>
@endsection
