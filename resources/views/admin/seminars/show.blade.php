@extends('layouts.admin')

@section('title', $seminar->title)

@section('content')
    <div class="space-y-6 p-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <a href="{{ route('admin.seminars.index') }}" class="text-sm font-semibold text-purple-700 hover:text-purple-900">Back to seminars</a>
                    <h1 class="mt-3 text-2xl font-bold text-gray-900">{{ $seminar->title }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ $seminar->connector?->name ?? 'No connector' }}</p>
                </div>
                @if(! in_array($seminar->status, ['cancelled', 'completed'], true))
                    <form method="POST" action="{{ route('admin.seminars.cancel', $seminar) }}" class="flex gap-2">
                        @csrf
                        <input name="reason" required placeholder="Cancellation reason" class="rounded-lg border-gray-300 text-sm">
                        <button class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Cancel</button>
                    </form>
                @endif
            </div>

            <dl class="mt-6 grid gap-4 md:grid-cols-4">
                <div><dt class="text-xs font-semibold uppercase text-gray-500">Status</dt><dd class="mt-1 font-semibold capitalize">{{ $seminar->status }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-gray-500">Type</dt><dd class="mt-1 font-semibold capitalize">{{ $seminar->type }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-gray-500">Starts</dt><dd class="mt-1 font-semibold">{{ optional($seminar->starts_at)->format('M d, Y g:i A') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-gray-500">Moderation</dt><dd class="mt-1 font-semibold">{{ $seminar->admin_moderation_status }}</dd></div>
            </dl>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="font-bold text-gray-900">Registrants</h2>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->registrants as $registrant)
                        <div class="rounded-lg border border-gray-100 p-3 text-sm">
                            <div class="font-semibold">{{ $registrant->user?->name }}</div>
                            <div class="text-gray-500">{{ $registrant->status }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No registrants.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="font-bold text-gray-900">Attendance</h2>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->attendances as $attendance)
                        <div class="rounded-lg border border-gray-100 p-3 text-sm">
                            <div class="font-semibold">{{ $attendance->user?->name }}</div>
                            <div class="text-gray-500">{{ number_format($attendance->total_seconds / 60, 1) }} min · {{ $attendance->status }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No attendance.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="font-bold text-gray-900">Comments</h2>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->comments as $comment)
                        <div class="rounded-lg border border-gray-100 p-3 text-sm">
                            <div class="font-semibold">{{ $comment->user?->name }}</div>
                            <p class="mt-1">{{ $comment->body }}</p>
                            <div class="mt-1 text-gray-500">{{ $comment->status }}</div>
                            @if($comment->status !== 'hidden')
                                <form method="POST" action="{{ route('admin.seminars.comments.hide', [$seminar, $comment]) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <input name="reason" required placeholder="Reason" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm">
                                    <button class="rounded-lg border border-red-200 px-3 py-2 font-semibold text-red-700 hover:bg-red-50">Hide</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No comments.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="font-bold text-gray-900">Q&A</h2>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->questions as $question)
                        <div class="rounded-lg border border-gray-100 p-3 text-sm">
                            <div class="font-semibold">{{ $question->user?->name }}</div>
                            <p class="mt-1">{{ $question->question }}</p>
                            @if($question->answer)
                                <p class="mt-2 rounded-lg bg-green-50 p-2 text-green-800">{{ $question->answer }}</p>
                            @endif
                            <div class="mt-1 text-gray-500">{{ $question->status }}</div>
                            @if($question->status !== 'hidden')
                                <form method="POST" action="{{ route('admin.seminars.questions.answer', [$seminar, $question]) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <input name="answer" required placeholder="Answer" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm">
                                    <button class="rounded-lg bg-green-700 px-3 py-2 font-semibold text-white hover:bg-green-800">Answer</button>
                                </form>
                                <form method="POST" action="{{ route('admin.seminars.questions.hide', [$seminar, $question]) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <input name="reason" required placeholder="Reason" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm">
                                    <button class="rounded-lg border border-red-200 px-3 py-2 font-semibold text-red-700 hover:bg-red-50">Hide</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No questions.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
