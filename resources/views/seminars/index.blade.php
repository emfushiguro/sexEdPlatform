@extends('layouts.app')

@section('title', 'Seminars | '.config('app.name', 'Conscious Connections'))

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Seminars</h1>
            <p class="mt-1 text-sm text-gray-600">Browse upcoming connector-hosted learning sessions available to your account.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($seminars as $seminar)
                <a href="{{ route('seminars.show', $seminar) }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-purple-200 hover:shadow-md">
                    <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700">{{ $seminar->type }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">{{ config('seminars.categories.'.$seminar->category, ucfirst((string) $seminar->category)) }}</span>
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-gray-900">{{ $seminar->title }}</h2>
                    <p class="mt-2 line-clamp-3 text-sm text-gray-600">{{ $seminar->description }}</p>
                    <div class="mt-4 text-sm font-semibold text-gray-800">{{ optional($seminar->starts_at)->format('M d, Y g:i A') }}</div>
                    <div class="mt-1 text-xs text-gray-500">{{ $seminar->connector?->name }}</div>
                </a>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">
                    No eligible seminars are available right now.
                </div>
            @endforelse
        </div>
    </div>
@endsection
