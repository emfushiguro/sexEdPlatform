@extends('layouts.app')

@section('title', 'Join '.$seminar->title.' | '.config('app.name', 'Conscious Connections'))

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]" data-agora-livestream data-agora-token-url="{{ route('seminars.agora-token', $seminar) }}" data-agora-attendance-join-url="{{ route('seminars.attendance.join', $seminar) }}" data-agora-attendance-heartbeat-url="{{ route('seminars.attendance.heartbeat', $seminar) }}" data-agora-attendance-leave-url="{{ route('seminars.attendance.leave', $seminar) }}" data-agora-return-url="{{ route('seminars.show', $seminar) }}" data-agora-can-publish="{{ $canPublish ? '1' : '0' }}" data-agora-role="{{ $livestreamRole }}" @if($livestreamRole === 'host') data-agora-host-controls="1" data-agora-prepare-url="{{ route('connector.seminars.livestream.prepare', [$seminar->connector, $seminar]) }}" data-agora-start-url="{{ route('connector.seminars.livestream.start', [$seminar->connector, $seminar]) }}" data-agora-end-url="{{ route('connector.seminars.livestream.end', [$seminar->connector, $seminar]) }}" data-agora-status-url="{{ route('connector.seminars.livestream.status', [$seminar->connector, $seminar]) }}" @endif>
            <section class="rounded-lg border border-gray-200 bg-gray-950 p-5 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold">{{ $seminar->title }}</h1>
                        <p class="mt-1 text-sm text-gray-300" data-agora-status>{{ $seminar->connector?->name }}</p>
                    </div>
                    <span class="rounded-full {{ $canPublish ? 'bg-green-500/20 text-green-200' : 'bg-blue-500/20 text-blue-200' }} px-3 py-1 text-sm font-semibold">{{ ucfirst($livestreamRole) }}</span>
                </div>
                <div class="mt-6 grid gap-3 {{ $canPublish ? 'lg:grid-cols-[16rem_1fr]' : '' }}">
                    @if($canPublish)
                        <div class="min-h-48 overflow-hidden rounded-lg border border-white/10 bg-black">
                            <div class="px-3 py-2 text-xs font-semibold text-gray-300">You</div>
                            <div class="h-48" data-agora-local></div>
                        </div>
                    @endif
                    <div class="grid gap-3 sm:grid-cols-2" data-agora-remotes>
                        <div class="flex min-h-48 items-center justify-center rounded-lg border border-white/10 bg-black text-sm text-gray-400">No video yet</div>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($canPublish)
                        <button type="button" data-agora-action="camera" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900"><span data-agora-camera-label>Camera on</span></button>
                        <button type="button" data-agora-action="mic" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900"><span data-agora-mic-label>Unmute</span></button>
                    @endif
                    <button type="button" data-agora-action="join" @disabled($livestreamRole === 'host') class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-gray-700 disabled:text-gray-400">{{ $livestreamRole === 'host' ? 'Go Live' : ($canPublish ? 'Join as Speaker' : 'Join Livestream') }}</button>
                    <button type="button" data-agora-action="leave" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Leave</button>
                </div>
            </section>

            <aside class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="font-bold text-gray-900">Live Chat</h2>
                <form method="POST" action="{{ route('seminars.comments.store', $seminar) }}" class="mt-4 space-y-2">
                    @csrf
                    <textarea name="body" rows="3" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Add a comment"></textarea>
                    <button class="rounded-lg bg-purple-700 px-3 py-2 text-sm font-semibold text-white hover:bg-purple-800">Post Comment</button>
                </form>
                <form method="POST" action="{{ route('seminars.questions.store', $seminar) }}" class="mt-5 space-y-2 border-t border-gray-100 pt-4">
                    @csrf
                    <textarea name="question" rows="3" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Ask a question"></textarea>
                    <button class="rounded-lg border border-purple-200 px-3 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50">Ask Question</button>
                </form>
            </aside>

            <dialog data-agora-leave-dialog class="w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/70">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-950">{{ $livestreamRole === 'host' ? 'End livestream?' : 'Leave livestream?' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $livestreamRole === 'host' ? 'Everyone will be disconnected and the seminar will be marked completed.' : 'Your camera and microphone will stop and you will leave the session.' }}</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" data-agora-leave-cancel class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700">Stay</button>
                        <button type="button" data-agora-leave-confirm class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">{{ $livestreamRole === 'host' ? 'End livestream' : 'Leave' }}</button>
                    </div>
                </div>
            </dialog>
        </div>
    </div>
@endsection
