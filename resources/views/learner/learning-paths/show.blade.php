@extends('layouts.learner-app')

@section('title', $path['path']->title)

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $pathModel = $path['path'];
    $thumbnail = $pathModel->thumbnail
        ? (Str::startsWith($pathModel->thumbnail, ['http://', 'https://', '//'])
            ? $pathModel->thumbnail
            : Storage::url(ltrim($pathModel->thumbnail, '/')))
        : null;
    $continueNode = $path['current'] ?? $path['recommended'];
@endphp

<div class="learning-path-page mx-auto max-w-5xl overflow-x-hidden space-y-8">
    <header class="overflow-hidden rounded-3xl border border-purple-200/70 bg-white shadow-sm dark:border-purple-900/60 dark:bg-gray-800">
        <div class="grid gap-0 md:grid-cols-[minmax(0,1fr)_16rem]">
            <div class="p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-purple-600 dark:text-purple-300">Learning Path</p>
                <h1 class="mt-2 overflow-wrap-anywhere text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $pathModel->title }}</h1>
                <p class="mt-4 max-w-3xl text-base leading-7 text-gray-600 dark:text-gray-300">{{ $pathModel->description }}</p>
            </div>
            @if($thumbnail)
                <img src="{{ $thumbnail }}" alt="{{ $pathModel->title }} thumbnail" class="h-48 w-full object-cover md:h-full">
            @else
                <div class="flex min-h-40 items-center justify-center bg-gradient-to-br from-purple-700 via-indigo-700 to-fuchsia-600 text-white" aria-label="Learning path placeholder">
                    <svg class="h-16 w-16 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h9A2.75 2.75 0 0 1 19.25 5.75v12.5A2.75 2.75 0 0 1 16.5 21h-9a2.75 2.75 0 0 1-2.75-2.75V5.75Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h4" />
                    </svg>
                </div>
            @endif
        </div>
    </header>

    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="learning-path-progress-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Your progress</p>
                <h2 id="learning-path-progress-heading" class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $path['completed_modules'] }} of {{ $path['actionable_modules'] }} modules completed</h2>
            </div>
            <p class="text-3xl font-bold text-purple-700 dark:text-purple-300">{{ $path['progress_percentage'] }}%</p>
        </div>
        <div class="mt-5 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700"
             role="progressbar"
             aria-label="Overall progress for {{ $pathModel->title }}"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-valuenow="{{ $path['progress_percentage'] }}">
            <span class="learning-path-overall-progress" style="width: {{ $path['progress_percentage'] }}%"></span>
        </div>
    </section>

    @if($path['completed_path'])
        <section class="learning-path-completion-card rounded-3xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-800/60 dark:bg-emerald-950/30" aria-labelledby="learning-path-complete-heading">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Well done</p>
            <h2 id="learning-path-complete-heading" class="mt-2 text-2xl font-bold text-emerald-950 dark:text-emerald-100">Path complete</h2>
            <p class="mt-2 text-sm leading-6 text-emerald-900/80 dark:text-emerald-200/80">You completed every actionable module in this learning path.</p>
            @if($path['action_url'])
                <a href="{{ $path['action_url'] }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-emerald-950">
                    {{ $path['action_label'] ?? 'Review path' }}
                </a>
            @endif
        </section>
    @elseif($continueNode)
        <aside class="learning-path-continue-card rounded-3xl border border-indigo-200 bg-indigo-50/70 p-6 shadow-sm dark:border-indigo-800/60 dark:bg-indigo-950/30" aria-labelledby="learning-path-continue-heading">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700 dark:text-indigo-300">Next step</p>
            <h2 id="learning-path-continue-heading" class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Continue Learning</h2>
            <p class="mt-2 text-base font-semibold text-gray-800 dark:text-gray-100">{{ $continueNode['module']->title }}</p>
            <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $continueNode['state_label'] }} · {{ $continueNode['progress_percentage'] }}% complete</p>
            @if($continueNode['action_url'])
                <a href="{{ $continueNode['action_url'] }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-indigo-950">
                    {{ $continueNode['action_label'] }}
                </a>
            @endif
        </aside>
    @endif

    <section aria-labelledby="learning-path-modules-heading">
        <div class="mb-5">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">The journey</p>
            <h2 id="learning-path-modules-heading" class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Path modules</h2>
        </div>

        <ol class="learning-path-route" aria-label="Learning path modules">
            @foreach($path['nodes'] as $node)
                <x-learning-path.module-node :node="$node" :show-connector="!$loop->last" />
            @endforeach
        </ol>
    </section>
</div>
@endsection
