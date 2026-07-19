@extends('layouts.connector-app')

@section('title', 'Host Livestream')
@section('page-title', 'Host Livestream')

@section('content')
    <div class="space-y-5" data-agora-livestream
        data-agora-token-url="{{ route('connector.seminars.agora-token', [$connector, $seminar]) }}"
        data-agora-attendance-join-url="{{ route('seminars.attendance.join', $seminar) }}"
        data-agora-attendance-heartbeat-url="{{ route('seminars.attendance.heartbeat', $seminar) }}"
        data-agora-attendance-leave-url="{{ route('seminars.attendance.leave', $seminar) }}"
        data-agora-prepare-url="{{ route('connector.seminars.livestream.prepare', [$connector, $seminar]) }}"
        data-agora-start-url="{{ route('connector.seminars.livestream.start', [$connector, $seminar]) }}"
        data-agora-end-url="{{ route('connector.seminars.livestream.end', [$connector, $seminar]) }}"
        data-agora-status-url="{{ route('connector.seminars.livestream.status', [$connector, $seminar]) }}"
        data-agora-return-url="{{ route('connector.seminars.show', [$connector, $seminar]) }}"
        data-agora-role="host"
        data-agora-host-controls="1"
        data-agora-can-publish="1">
        <header class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-purple-700">{{ $connector->name }} · {{ $seminar->categoryDisplayName() }}</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-950">{{ $seminar->title }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-sm font-semibold text-gray-600">
                <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 data-[tone=live]:bg-red-100 data-[tone=live]:text-red-700 data-[tone=preparing]:bg-amber-100 data-[tone=preparing]:text-amber-700" data-agora-badge data-tone="waiting">Waiting</span>
                <span><strong data-agora-viewers>0</strong> viewers</span>
                <span><strong data-agora-speakers>0</strong> speakers</span>
                <span><strong data-agora-participants>0</strong> participants</span>
                <time class="font-mono" data-agora-duration>00:00:00</time>
            </div>
        </header>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <section class="overflow-hidden rounded-xl bg-gray-950 text-white shadow-lg">
                <div class="grid gap-3 p-4 md:grid-cols-2" data-agora-remotes>
                    <div class="flex aspect-video items-center justify-center rounded-xl border border-white/10 bg-black text-sm text-gray-400" data-agora-empty>Remote speakers appear here</div>
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-black">
                        <div class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-300"><span>You · Host</span><span>Preview</span></div>
                        <div class="aspect-video" data-agora-local></div>
                    </div>
                </div>

                <div class="border-t border-white/10 px-4 py-3">
                    <p class="mb-3 min-h-5 text-center text-sm text-gray-300" role="status" aria-live="polite" data-agora-status>{{ $joinOpen ? 'Ready to go live' : 'Host access opens 15 minutes before start.' }}</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <button type="button" data-agora-action="camera" aria-pressed="false" class="rounded-lg bg-white/10 px-4 py-2.5 text-sm font-semibold hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <span aria-hidden="true">◉</span> <span data-agora-camera-label>Camera on</span>
                        </button>
                        <button type="button" data-agora-action="mic" aria-pressed="false" class="rounded-lg bg-white/10 px-4 py-2.5 text-sm font-semibold hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <span aria-hidden="true">●</span> <span data-agora-mic-label>Unmute</span>
                        </button>
                        <button type="button" data-agora-action="join" disabled class="rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-bold hover:bg-purple-500 disabled:cursor-not-allowed disabled:bg-gray-700 disabled:text-gray-400">Go Live</button>
                        <button type="button" data-agora-action="leave" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold hover:bg-red-500">Leave session</button>
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <details open class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <summary class="cursor-pointer font-bold text-gray-950">Session information</summary>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Status</dt><dd class="font-semibold text-gray-900" data-agora-status>Waiting</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Connection</dt><dd class="font-semibold text-gray-900" data-agora-quality>Offline</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Host</dt><dd class="text-right font-semibold text-gray-900">{{ auth()->user()->name }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Organizer</dt><dd class="text-right font-semibold text-gray-900">{{ $connector->name }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Schedule</dt><dd class="text-right font-semibold text-gray-900">{{ $seminar->localStartsAt()?->format('M j, Y g:i A') }} PHT</dd></div>
                    </dl>
                </details>
                <details class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <summary class="cursor-pointer font-bold text-gray-950">Participants</summary>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="grid size-9 place-items-center rounded-full bg-purple-100 font-bold text-purple-700">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div><p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p><p class="text-xs text-gray-500">Host · Connected</p></div>
                    </div>
                    <p class="mt-4 text-sm text-gray-500">Speakers and viewers appear when connected.</p>
                </details>
            </aside>
        </div>

        <dialog data-agora-leave-dialog class="w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/70">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-950">End livestream?</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600">Everyone will be disconnected, attendance will be finalized, and this seminar will be marked completed.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-agora-leave-cancel class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Keep streaming</button>
                    <button type="button" data-agora-leave-confirm class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">End livestream</button>
                </div>
            </div>
        </dialog>
    </div>
@endsection
