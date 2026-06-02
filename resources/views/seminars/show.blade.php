@extends('layouts.app')

@section('title', $seminar->title.' | '.config('app.name', 'Conscious Connections'))

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700">{{ $seminar->status }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">{{ $seminar->type }}</span>
                    </div>
                    <h1 class="mt-3 text-3xl font-bold text-gray-900">{{ $seminar->title }}</h1>
                    <p class="mt-2 text-sm text-gray-500">{{ $seminar->connector?->name }}</p>
                </div>

                <div class="w-full rounded-lg border border-gray-100 bg-gray-50 p-4 lg:w-72">
                    <div class="text-sm font-semibold text-gray-900">{{ optional($seminar->starts_at)->format('M d, Y') }}</div>
                    <div class="mt-1 text-sm text-gray-600">{{ optional($seminar->starts_at)->format('g:i A') }} - {{ optional($seminar->ends_at)->format('g:i A') }}</div>
                    <div class="mt-2 text-sm text-gray-600">{{ $seminar->capacity ? $seminar->registrants()->active()->count().' / '.$seminar->capacity.' registered' : 'Open capacity' }}</div>

                    <div class="mt-4">
                        @if($registration)
                            <form method="POST" action="{{ route('seminars.cancel-registration', $seminar) }}">
                                @csrf
                                <button class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancel Registration</button>
                            </form>
                        @elseif($canRegister)
                            <form method="POST" action="{{ route('seminars.register', $seminar) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Register</button>
                            </form>
                        @else
                            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">{{ $registrationError }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_18rem]">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">About</h2>
                    <div class="mt-2 space-y-4 text-sm leading-6 text-gray-700">
                        <p>{{ $seminar->description }}</p>
                        @if($seminar->purpose)
                            <p>{{ $seminar->purpose }}</p>
                        @endif
                    </div>
                </div>
                <dl class="space-y-4 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-900">Category</dt>
                        <dd class="mt-1 text-gray-600">{{ config('seminars.categories.'.$seminar->category, ucfirst((string) $seminar->category)) }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900">Audience</dt>
                        <dd class="mt-1 text-gray-600">{{ str_replace('_', ' ', $seminar->target_participants) }}</dd>
                    </div>
                    @if($seminar->location)
                        <div>
                            <dt class="font-semibold text-gray-900">Location</dt>
                            <dd class="mt-1 text-gray-600">{{ $seminar->location }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
@endsection
