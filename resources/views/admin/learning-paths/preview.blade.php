@extends('layouts.admin')

@section('title', 'Administrator Preview')
@section('page-title', 'Learning Path Preview')

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
@endphp

<div class="mx-auto max-w-6xl space-y-8">
    <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm" role="status" aria-labelledby="administrator-preview-heading">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-700">Administrator Preview</p>
        <h1 id="administrator-preview-heading" class="mt-2 text-xl font-bold text-indigo-950">Neutral learning path view</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-indigo-900">Progress and recommendation are illustrative for this administrator preview. No learner is being impersonated, and no enrollment, purchase, or progress data is changed.</p>
    </section>

    <header class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="grid gap-0 md:grid-cols-[minmax(0,1fr)_16rem]">
            <div class="p-6 sm:p-8">
                <div class="flex flex-wrap gap-2" aria-label="Learning path categories">
                    @foreach($pathModel->learnerCategories as $category)
                        <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-semibold text-indigo-800">{{ ucfirst($category->category) }}</span>
                    @endforeach
                </div>
                <h2 class="mt-4 overflow-wrap-anywhere text-3xl font-bold tracking-tight text-gray-900">{{ $pathModel->title }}</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-gray-600">{{ $pathModel->description }}</p>
                <a href="{{ route('admin.learning-paths.edit', $pathModel) }}" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-indigo-400 hover:text-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">
                    Back to edit
                </a>
            </div>
            @if($thumbnail)
                <img src="{{ $thumbnail }}" alt="{{ $pathModel->title }} thumbnail" class="h-48 w-full object-cover md:h-full">
            @else
                <div class="flex min-h-40 items-center justify-center bg-gradient-to-br from-indigo-700 via-purple-700 to-fuchsia-600 text-white" aria-label="Learning path placeholder">
                    <svg class="h-16 w-16 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h9A2.75 2.75 0 0 1 19.25 5.75v12.5A2.75 2.75 0 0 1 16.5 21h-9a2.75 2.75 0 0 1-2.75-2.75V5.75Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h4" />
                    </svg>
                </div>
            @endif
        </div>
    </header>

    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="administrator-preview-progress-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gray-500">Illustrative progress</p>
                <h2 id="administrator-preview-progress-heading" class="mt-1 text-2xl font-bold text-gray-900">{{ $path['completed_modules'] }} of {{ $path['actionable_modules'] }} modules completed</h2>
            </div>
            <p class="text-3xl font-bold text-indigo-700">{{ $path['progress_percentage'] }}%</p>
        </div>
        <div class="mt-5 h-3 overflow-hidden rounded-full bg-gray-100"
             role="progressbar"
             aria-label="Illustrative progress for {{ $pathModel->title }}"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-valuenow="{{ $path['progress_percentage'] }}">
            <span class="learning-path-overall-progress" style="width: {{ $path['progress_percentage'] }}%"></span>
        </div>
    </section>

    <section aria-labelledby="administrator-preview-modules-heading">
        <div class="mb-5">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gray-500">The journey</p>
            <h2 id="administrator-preview-modules-heading" class="mt-1 text-2xl font-bold text-gray-900">Path modules</h2>
        </div>

        @if($path['nodes'])
            <ol class="learning-path-route" aria-label="Learning path modules">
                @foreach($path['nodes'] as $node)
                    <x-learning-path.module-node :node="$node" :preview="true" :show-connector="!$loop->last" />
                @endforeach
            </ol>
        @else
            <div class="rounded-3xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-600">
                No modules are currently included in this learning path.
            </div>
        @endif
    </section>
</div>
@endsection
