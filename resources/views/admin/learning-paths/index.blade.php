@extends('layouts.admin')

@section('title', 'Learning Paths')
@section('page-title', 'Learning Paths')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Learning Paths</h1>
            <p class="mt-1 text-sm text-gray-600">Build guided sequences from learner-visible modules.</p>
        </div>
        @can('create', \App\Models\LearningPath::class)
            <a href="{{ route('admin.learning-paths.create') }}" class="rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">Create learning path</a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-left text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr><th scope="col" class="px-5 py-4">Path</th><th scope="col" class="px-5 py-4">Categories</th><th scope="col" class="px-5 py-4">Modules</th><th scope="col" class="px-5 py-4">Status</th><th scope="col" class="px-5 py-4">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($paths as $path)
                    <tr>
                        <td class="px-5 py-4"><span class="font-semibold text-gray-900">{{ $path->title }}</span><span class="mt-1 block text-xs text-gray-500">By {{ $path->creator?->name ?? 'Former administrator' }}</span></td>
                        <td class="px-5 py-4 text-gray-600">{{ $path->learnerCategories->pluck('category')->map(fn ($category) => ucfirst($category))->join(', ') }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $path->path_modules_count }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $path->status === 'published' ? 'bg-emerald-50 text-emerald-700' : ($path->status === 'archived' ? 'bg-gray-100 text-gray-600' : 'bg-amber-50 text-amber-700') }}">{{ ucfirst($path->status) }}</span></td>
                        <td class="px-5 py-4"><div class="flex flex-wrap items-center gap-2">
                            @can('view', $path)
                                <a class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-indigo-200 text-indigo-700 transition hover:bg-indigo-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700" href="{{ route('admin.learning-paths.preview', $path) }}" title="Preview learning path" aria-label="Preview {{ $path->title }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6Z"/><circle cx="12" cy="12" r="2.25"/></svg>
                                </a>
                            @endcan
                            @can('update', $path)
                                <a class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-purple-200 text-purple-700 transition hover:bg-purple-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700" href="{{ route('admin.learning-paths.edit', $path) }}" title="Edit learning path" aria-label="Edit {{ $path->title }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 5.25 3 3m-12.75 10.5h3.75L20.25 8.25a2.121 2.121 0 0 0-3-3L6.75 15.75v3Z"/></svg>
                                </a>
                            @endcan
                            @can('archive', $path)
                                @if($path->status === 'archived')
                                    <form method="POST" action="{{ route('admin.learning-paths.restore', $path) }}">@csrf @method('PATCH')<button class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-emerald-200 text-emerald-700 transition hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700" type="submit" title="Restore as draft" aria-label="Restore {{ $path->title }} as draft"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.708L3 8m0-5v5h5"/></svg></button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.learning-paths.archive', $path) }}">@csrf @method('PATCH')<button class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-700" type="submit" title="Archive learning path" aria-label="Archive {{ $path->title }}"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h16.5v4.5H3.75zM5.25 9v10.5h13.5V9m-9 4.5h4.5"/></svg></button></form>
                                @endif
                            @endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">No learning paths yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $paths->links() }}
</div>
@endsection
