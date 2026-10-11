@extends('layouts.admin')

@section('title', 'Help Categories')
@section('page-title', 'Help Categories')

@section('content')
<main class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <x-support.admin-tabs />
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Help Center</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Help Categories</h1><p class="mt-2 text-sm text-gray-500">Organize guides into predictable topics for every role.</p></div>
        <a href="{{ route('admin.help.categories.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Add category</a>
    </header>
    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Category</th><th class="px-5 py-3">Guides</th><th class="px-5 py-3">Audience</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                    <tr class="hover:bg-purple-50/30">
                        <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-lg bg-purple-50 text-purple-700"><x-support.category-icon :name="$category->icon_key" /></span><div><p class="font-semibold text-gray-900">{{ $category->name }}</p></div></div></td>
                        <td class="px-5 py-4 text-gray-600">{{ $category->articles_count }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ collect($category->audiences)->map(fn ($audience) => str($audience)->headline())->join(', ') }}</td>
                        <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $category->is_active, 'bg-gray-100 text-gray-600' => ! $category->is_active])>{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-5 py-4 text-right"><a data-help-category-action="edit" href="{{ route('admin.help.categories.edit', $category) }}" title="Edit help category" aria-label="Edit help category" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-purple-200 text-purple-700 transition hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m4 16.5-.75 3.75L7 19.5 18.25 8.25a2.65 2.65 0 0 0-3.75-3.75L3.25 15.75"/><path stroke-linecap="round" d="m13 6 3.75 3.75"/></svg><span class="sr-only">Edit help category</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center"><p class="font-semibold text-gray-900">No Help Center categories yet</p><a href="{{ route('admin.help.categories.create') }}" class="mt-2 inline-flex text-sm font-semibold text-purple-700">Add the first category</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $categories->links() }}
</main>
@endsection
