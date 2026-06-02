@extends('layouts.app')

@section('title', 'Join '.$seminar->title.' | '.config('app.name', 'Conscious Connections'))

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="rounded-lg border border-gray-200 bg-gray-950 p-5 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold">{{ $seminar->title }}</h1>
                        <p class="mt-1 text-sm text-gray-300">{{ $seminar->connector?->name }}</p>
                    </div>
                    <span class="rounded-full {{ $canPublish ? 'bg-green-500/20 text-green-200' : 'bg-blue-500/20 text-blue-200' }} px-3 py-1 text-sm font-semibold">{{ $canPublish ? 'Speaker' : 'Audience' }}</span>
                </div>
                <div class="mt-6 flex aspect-video items-center justify-center rounded-lg border border-white/10 bg-black text-sm text-gray-400" data-agora-stage>
                    Livestream
                </div>
                @if($canPublish)
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900">Camera</button>
                        <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900">Mic</button>
                    </div>
                @endif
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
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const post = (url) => fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } }).catch(() => {});
            post(@json(route('seminars.attendance.join', $seminar)));
            const heartbeat = window.setInterval(() => post(@json(route('seminars.attendance.heartbeat', $seminar))), 60000);
            window.addEventListener('beforeunload', () => {
                window.clearInterval(heartbeat);
                fetch(@json(route('seminars.attendance.leave', $seminar)), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    keepalive: true,
                }).catch(() => {});
            });
        })();
    </script>
@endpush
