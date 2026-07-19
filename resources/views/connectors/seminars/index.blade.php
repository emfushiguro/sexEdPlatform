@extends('layouts.connector-app')

@section('title', 'Seminars')
@section('page-title', 'Seminars')

@section('content')
    @php
        $statusStyles = [
            'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
            'pending_review' => 'bg-amber-100 text-amber-800 border-amber-200',
            'approved' => 'bg-sky-100 text-sky-800 border-sky-200',
            'published' => 'bg-blue-100 text-blue-800 border-blue-200',
            'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'completed' => 'bg-purple-100 text-purple-800 border-purple-200',
            'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
            'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
            'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
        ];
    @endphp

<div x-data="{ deleteOpen: false, deleteAction: '', deleteTitle: '' }">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Seminars</h2>
            <p class="mt-1 text-sm text-gray-600">{{ $canManageSeminars ? 'Manage free webinars and physical seminars for your connector.' : 'Browse connector seminars available to members.' }}</p>
        </div>
        @if($canManageSeminars)
            <a href="{{ route('connector.seminars.create', $connector) }}" class="inline-flex items-center justify-center rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Create Seminar</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Starts</th>
                    <th class="px-4 py-3 text-right">Registrants</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($seminars as $seminar)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('connector.seminars.show', [$connector, $seminar]) }}" class="font-semibold text-purple-700 hover:text-purple-900">{{ $seminar->title }}</a>
                            <div class="text-xs text-gray-500">{{ config('seminars.categories.'.$seminar->category, ucfirst((string) $seminar->category)) }}</div>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $seminar->type }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold capitalize {{ $statusStyles[$seminar->status] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                {{ \App\Enums\SeminarStatus::tryFrom($seminar->status)?->label() ?? str_replace('_', ' ', $seminar->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $seminar->localStartsAt()?->format('M d, Y g:i A') }} PHT</td>
                        <td class="px-4 py-3 text-right">{{ $seminar->registrants_count ?? $seminar->registrants()->count() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('connector.seminars.show', [$connector, $seminar]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50" title="View">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12Z"/></svg>
                                </a>
                                @if($canManageSeminars)
                                    <a href="{{ route('connector.seminars.edit', [$connector, $seminar]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Edit">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4 4 0 0 1-1.897 1.13L6 18l.8-2.685a4 4 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </a>
                                    @php($canDelete = $seminar->published_at === null && ! in_array($seminar->status, ['published', 'completed', 'cancelled', 'archived'], true))
                                    <button type="button" @if($canDelete) @click="deleteAction = '{{ route('connector.seminars.destroy', [$connector, $seminar]) }}'; deleteTitle = @js($seminar->title); deleteOpen = true" @endif :disabled="{{ $canDelete ? 'false' : 'true' }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-40" title="{{ $canDelete ? 'Delete' : 'Published seminars cannot be deleted' }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M19.228 5.79 18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">No seminars yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $seminars->links() }}</div>
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <form method="POST" :action="deleteAction" @click.outside="deleteOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            @csrf
            @method('DELETE')
            <h3 class="text-lg font-bold text-gray-900">Delete seminar?</h3>
            <p class="mt-2 text-sm text-gray-600">This deletes <span class="font-semibold" x-text="deleteTitle"></span>. Published seminars cannot be deleted.</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="deleteOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection
