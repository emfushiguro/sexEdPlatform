@extends('layouts.connector-app')

@section('title', 'Livestream')
@section('page-title', 'Livestream')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="rounded-lg border border-gray-200 bg-gray-950 p-5 text-white">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">{{ $seminar->title }}</h2>
                    <p class="mt-1 text-sm text-gray-300">{{ $joinOpen ? 'Host access is open.' : 'Host access opens 15 minutes before start.' }}</p>
                </div>
                <span class="rounded-full bg-green-500/20 px-3 py-1 text-sm font-semibold text-green-200">Publisher</span>
            </div>
            <div class="mt-6 flex aspect-video items-center justify-center rounded-lg border border-white/10 bg-black text-sm text-gray-400" data-agora-stage>
                Livestream preview
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900">Camera</button>
                <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900">Mic</button>
                <button class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white">Go Live</button>
            </div>
        </section>

        <aside class="rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="font-bold text-gray-900">Session</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="font-semibold text-gray-500">Channel</dt>
                    <dd class="mt-1 text-gray-900">{{ $seminar->livestream_channel }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-500">Token Endpoint</dt>
                    <dd class="mt-1 break-all text-gray-900">{{ route('connector.seminars.agora-token', [$connector, $seminar]) }}</dd>
                </div>
            </dl>
            <div class="mt-6 border-t border-gray-100 pt-4">
                <h3 class="font-bold text-gray-900">Moderation</h3>
                <p class="mt-2 text-sm text-gray-500">Comments and questions can be moderated from the seminar detail page.</p>
            </div>
        </aside>
    </div>
@endsection
