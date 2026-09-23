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
                        <td class="px-5 py-4"><div class="flex flex-wrap items-center gap-3">
                            @can('update', $path)<a class="font-medium text-purple-700 hover:underline" href="{{ route('admin.learning-paths.edit', $path) }}">Edit</a>@endcan
                            @can('archive', $path)
                                @if($path->status === 'archived')
                                    <form method="POST" action="{{ route('admin.learning-paths.restore', $path) }}">@csrf @method('PATCH')<button class="font-medium text-emerald-700 hover:underline" type="submit">Restore as draft</button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.learning-paths.archive', $path) }}">@csrf @method('PATCH')<button class="font-medium text-gray-600 hover:underline" type="submit">Archive</button></form>
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
