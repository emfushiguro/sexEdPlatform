@extends('layouts.admin')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $module->title }}</h1>
            <p class="text-sm text-gray-500">Admin-authored and platform-owned.</p>
        </div>
        <a href="{{ route('admin.modules.edit', $module) }}" class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">
            Edit Module
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 space-y-3">
        <p class="text-sm text-gray-600">{{ $module->description }}</p>
        <p class="text-sm text-gray-900">Owner type: {{ $module->content_owner_type }}</p>
        <p class="text-sm text-gray-900">Published status: {{ $module->current_review_status }}</p>
    </div>
</div>
@endsection
