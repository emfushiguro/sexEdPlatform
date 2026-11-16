@extends('layouts.admin')
@section('title', 'Reference Library')
@section('content')
<div class="mx-auto max-w-6xl space-y-8 p-4 sm:p-8">
    <header>
        @if($category)<a href="{{ route('admin.identity-references.index') }}" class="text-sm font-semibold text-purple-700 underline">Back to categories</a>@endif
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $category ?? 'Reference Library' }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ $category ? 'Choose a document type to add or update its reference examples.' : 'Choose a category to browse and manage its document references.' }}</p>
    </header>
    @if($category === null)
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($groups as $heading => $types)
                <a href="{{ route('admin.identity-references.index', ['category' => $heading]) }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm hover:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <h2 class="text-lg font-bold text-gray-900">{{ $heading }}</h2>
                    <p class="mt-2 text-sm text-gray-600">{{ count($types) }} document types</p>
                    <span class="mt-4 inline-block text-sm font-semibold text-purple-800">Browse documents &rarr;</span>
                </a>
            @endforeach
        </div>
    @else
    @foreach($groups as $heading => $types)
        @continue($heading !== $category)
        <section aria-labelledby="reference-group-{{ $loop->index }}">
            <h2 id="reference-group-{{ $loop->index }}" class="mb-4 text-lg font-bold text-gray-900">{{ $heading }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($types as $type => $label)
                    <a href="{{ route('admin.identity-references.show', $type) }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <h3 class="font-semibold text-gray-900">{{ $label }}</h3>
                        <p class="mt-2 text-sm {{ ($counts[$type] ?? 0) ? 'text-purple-700' : 'text-amber-800' }}">{{ ($counts[$type] ?? 0) ? $counts[$type].' reference examples' : 'No examples yet' }}</p>
                        <span class="mt-3 inline-block text-sm font-semibold text-purple-800">Manage examples &rarr;</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
    @endif
</div>
@endsection
