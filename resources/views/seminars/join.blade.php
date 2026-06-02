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
                <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm text-gray-500">
                    Comments and Q&A will appear here.
                </div>
            </aside>
        </div>
    </div>
@endsection
