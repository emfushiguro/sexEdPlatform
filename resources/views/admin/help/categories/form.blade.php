@extends('layouts.admin')

@section('title', $category->exists ? 'Edit Help Category' : 'Add Help Category')
@section('page-title', $category->exists ? 'Edit Help Category' : 'Add Help Category')

@section('content')
<div class="mx-auto max-w-3xl px-4 pt-8"><x-support.admin-tabs /></div>
<div class="mx-auto max-w-3xl px-4 py-8">
    <form method="POST" action="{{ $category->exists ? route('admin.help.categories.update', $category) : route('admin.help.categories.store') }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf @if($category->exists) @method('PUT') @endif
        <div><label for="name" class="text-sm font-semibold text-gray-700">Name</label><input id="name" name="name" value="{{ old('name', $category->name) }}" class="mt-1 w-full rounded-xl border-gray-300" required>@error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
        <div><label for="description" class="text-sm font-semibold text-gray-700">Description</label><textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-xl border-gray-300">{{ old('description', $category->description) }}</textarea></div>
        <div><label for="icon_key" class="text-sm font-semibold text-gray-700">Category icon</label><select id="icon_key" name="icon_key" class="mt-1 w-full rounded-xl border-gray-300" required>@foreach(['rocket','account','book','quiz','seminar','community','guardian','instructor','connector','payment','shield','accessibility','tools'] as $icon)<option value="{{ $icon }}" @selected(old('icon_key', $category->icon_key ?? 'tools') === $icon)>{{ str($icon)->headline() }}</option>@endforeach</select><p class="mt-1 text-xs text-gray-500">The same icon appears for this topic in every role.</p></div>
        <x-support.audience-picker id="category-audiences" :selected="old('audiences', $category->audiences ?? ['all'])" />
        <div class="flex items-center gap-3"><input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true)) class="rounded border-gray-300 text-brand-700"><label for="is_active" class="text-sm font-semibold text-gray-700">Active category</label><input type="hidden" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
        <div class="flex justify-end gap-3"><a href="{{ route('admin.help.categories.index') }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700">Cancel</a><button class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Save category</button></div>
    </form>
</div>
@endsection
