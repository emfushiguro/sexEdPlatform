@extends('layouts.connector-app')

@section('title', $seminar->title)
@section('page-title', 'Seminar Details')

@section('content')
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700">{{ $seminar->status }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">{{ $seminar->type }}</span>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold text-gray-900">{{ $seminar->title }}</h2>
                    <p class="mt-2 text-sm text-gray-600">{{ $seminar->description }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('connector.seminars.edit', [$connector, $seminar]) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Edit</a>
                    @if($seminar->status === 'draft')
                        <form method="POST" action="{{ route('connector.seminars.publish', [$connector, $seminar]) }}">
                            @csrf
                            <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Publish</button>
                        </form>
                    @endif
                    @if(! in_array($seminar->status, ['cancelled', 'completed'], true))
                        <form method="POST" action="{{ route('connector.seminars.complete', [$connector, $seminar]) }}">
                            @csrf
                            <button class="rounded-lg border border-green-200 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">Complete</button>
                        </form>
                    @endif
                </div>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Schedule</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ optional($seminar->starts_at)->format('M d, Y g:i A') }} - {{ optional($seminar->ends_at)->format('g:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Capacity</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $seminar->capacity ?? 'Unlimited' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registrants</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $activeRegistrantCount }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Channel</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $seminar->livestream_channel ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        @if(! in_array($seminar->status, ['cancelled', 'completed'], true))
            <div class="rounded-lg border border-red-200 bg-red-50 p-5">
                <form method="POST" action="{{ route('connector.seminars.cancel', [$connector, $seminar]) }}" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                    @csrf
                    <label>
                        <span class="text-sm font-semibold text-red-900">Cancellation Reason</span>
                        <input name="cancellation_reason" required class="mt-1 w-full rounded-lg border-red-200 bg-white shadow-sm focus:border-red-500 focus:ring-red-500">
                    </label>
                    <button class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Cancel Seminar</button>
                </form>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900">Speakers</h3>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->speakers as $speaker)
                        <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-100 p-3">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $speaker->display_name }}</div>
                                <div class="text-sm text-gray-500">{{ $speaker->title ?? 'Speaker' }}</div>
                            </div>
                            <form method="POST" action="{{ route('connector.seminars.speakers.destroy', [$connector, $seminar, $speaker]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-semibold text-red-600 hover:text-red-800">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No speakers assigned.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('connector.seminars.speakers.store', [$connector, $seminar]) }}" class="mt-5 space-y-3 border-t border-gray-100 pt-4">
                    @csrf
                    <input type="hidden" name="speaker_type" value="platform">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Platform User ID</span>
                        <input type="number" name="user_id" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="display_name" placeholder="Display name override" class="rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        <input name="title" placeholder="Title" class="rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                    <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Add Platform Speaker</button>
                </form>

                <form method="POST" action="{{ route('connector.seminars.speakers.store', [$connector, $seminar]) }}" class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                    @csrf
                    <input type="hidden" name="speaker_type" value="external">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="display_name" placeholder="External speaker name" class="rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        <input name="title" placeholder="Title" class="rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                    <textarea name="bio" rows="2" placeholder="Short bio" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"></textarea>
                    <button class="rounded-lg border border-purple-200 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50">Add External Speaker</button>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900">Registrants</h3>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->registrants as $registrant)
                        <div class="rounded-lg border border-gray-100 p-3">
                            <div class="font-semibold text-gray-900">{{ $registrant->user?->name }}</div>
                            <div class="text-sm text-gray-500">{{ $registrant->status }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No registrants yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900">Comments</h3>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->comments as $comment)
                        <div class="rounded-lg border border-gray-100 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">{{ $comment->user?->name }}</div>
                                    <p class="mt-1 text-sm text-gray-700">{{ $comment->body }}</p>
                                    <div class="mt-1 text-xs text-gray-500">{{ $comment->status }}</div>
                                </div>
                            </div>
                            @if($comment->status !== 'hidden')
                                <form method="POST" action="{{ route('connector.seminars.comments.hide', [$connector, $seminar, $comment]) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    <input name="reason" placeholder="Reason" required class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hide</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No comments yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900">Q&A</h3>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->questions as $question)
                        <div class="rounded-lg border border-gray-100 p-3">
                            <div class="text-sm font-semibold text-gray-900">{{ $question->user?->name }}</div>
                            <p class="mt-1 text-sm text-gray-700">{{ $question->question }}</p>
                            @if($question->answer)
                                <p class="mt-2 rounded-lg bg-green-50 p-2 text-sm text-green-800">{{ $question->answer }}</p>
                            @endif
                            <div class="mt-1 text-xs text-gray-500">{{ $question->status }}</div>
                            @if($question->status !== 'hidden')
                                <form method="POST" action="{{ route('connector.seminars.questions.answer', [$connector, $seminar, $question]) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    <input name="answer" placeholder="Answer" required class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <button class="rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white hover:bg-green-800">Answer</button>
                                </form>
                                <form method="POST" action="{{ route('connector.seminars.questions.hide', [$connector, $seminar, $question]) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <input name="reason" placeholder="Reason" required class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hide</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No questions yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
