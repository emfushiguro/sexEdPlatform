@extends('layouts.connector-app')

@section('title', 'Educators')
@section('page-title', 'Educators')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Connect with an instructor</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600">Discuss speaker fees, availability, and arrangements for {{ $connector->name }}. Conversations are private between you and the instructor.</p>
        </div>
        <a href="{{ route('connector.educators.messages', $connector) }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Open messages</a>
    </div>

    <section aria-labelledby="instructor-directory-heading" class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 p-5 sm:p-6">
            <h2 id="instructor-directory-heading" class="text-base font-semibold text-gray-900">Instructor directory</h2>
            <form action="{{ route('connector.educators', $connector) }}" method="GET" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="educator-search" class="block text-sm font-medium text-gray-700">Search by instructor name</label>
                    <input id="educator-search" name="q" value="{{ $search }}" maxlength="100" type="search" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <button class="min-h-11 rounded-lg bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Search</button>
                @if($search !== '')
                    <a href="{{ route('connector.educators', $connector) }}" class="inline-flex min-h-11 items-center justify-center px-3 text-sm font-medium text-gray-600 hover:underline">Clear search</a>
                @endif
            </form>
            @error('q')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <ul class="divide-y divide-gray-100">
            @forelse($instructors as $instructor)
                <li class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <span aria-hidden="true" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-purple-50 font-semibold text-purple-700">{{ mb_substr($instructor->name, 0, 1) }}</span>
                        <div class="min-w-0">
                            <h3 class="break-words font-semibold text-gray-900">{{ $instructor->name }}</h3>
                            <p class="mt-1 text-xs text-gray-500">Instructor</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('connector.educators.show', [$connector, $instructor]) }}" aria-label="View instructor {{ $instructor->name }}" title="View profile" data-educator-action="view" class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                            <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </a>
                        @include('connectors.educators.partials.message-action')
                    </div>
                </li>
            @empty
                <li class="px-6 py-12 text-center">
                    <p class="font-semibold text-gray-900">{{ $search !== '' ? 'No instructors matched your search' : 'No instructors available yet' }}</p>
                    <p class="mt-2 text-sm text-gray-500">{{ $search !== '' ? 'Try another name or clear your search.' : 'Active instructors will appear here when available.' }}</p>
                </li>
            @endforelse
        </ul>
        @if($instructors->hasPages())<div class="border-t border-gray-200 p-5">{{ $instructors->links() }}</div>@endif
    </section>
    <p class="text-xs leading-5 text-gray-500">Discussing payment here does not change marketplace prices or complete a purchase.</p>
</div>
@endsection
