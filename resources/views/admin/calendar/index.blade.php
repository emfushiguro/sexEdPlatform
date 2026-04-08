@extends('layouts.admin')
@section('title', 'Calendar')
@section('page-title', 'Calendar')
@section('content')

    {{-- Coming Soon Notice --}}
    <div
        class="flex items-center gap-3 px-4 py-3 mb-5 text-sm border rounded-xl bg-brand-50 border-brand-200 text-brand-700">
        <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Calendar backend integration coming soon. This is a UI preview.
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- Calendar Grid --}}
        <div class="xl:col-span-2">
            <div class="overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-theme-xs">
                {{-- Month Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 ">
                    <button class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h3 class="text-base font-bold text-gray-900 ">{{ now()->format('F Y') }}</h3>
                    <button class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
                {{-- Day Headers --}}
                <div class="grid grid-cols-7 border-b border-gray-100 ">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                        <div class="px-2 py-2.5 text-center text-xs font-semibold text-gray-400 uppercase">
                            {{ $day }}</div>
                    @endforeach
                </div>
                {{-- Calendar Days --}}
                @php
                    $start = now()->startOfMonth();
                    $daysInMonth = now()->daysInMonth;
                    $startDow = $start->dayOfWeek;
                    $today = now()->day;
                @endphp
                <div class="grid grid-cols-7 divide-x divide-gray-100 ">
                    @for ($i = 0; $i < $startDow; $i++)
                        <div class="min-h-[80px] bg-gray-50/50 p-1.5"></div>
                    @endfor
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $isToday = $d === $today;
                            $events = [];
                        @endphp
                        <div
                            class="min-h-[80px] p-1.5 border-b border-gray-100 {{ $isToday ? 'bg-brand-50/50 ' : 'hover:bg-gray-50 ' }} transition-colors">
                            <p
                                class="text-xs font-semibold {{ $isToday ? 'w-6 h-6 rounded-full bg-brand-500 text-white flex items-center justify-center' : 'text-gray-500 ' }} mb-1">
                                {{ $d }}</p>
                            {{-- Event dots (placeholder) --}}
                            @if ($d % 7 == 0 || $d % 11 == 0)
                                <div class="px-1 py-0.5 rounded text-xs bg-brand-100 text-brand-700 truncate mb-0.5">Seminar
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Events Sidebar --}}
        <div class="space-y-4">
            {{-- Quick Add --}}
            <div class="p-5 bg-white border border-gray-200 rounded-2xl shadow-theme-xs">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Add Event</h3>
                <div class="space-y-3">
                    <input type="text" placeholder="Event title" disabled
                        class="w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 bg-transparent border border-gray-200 rounded-lg opacity-60">
                    <input type="date" disabled
                        class="w-full px-3 py-2 text-sm text-gray-900 bg-transparent border border-gray-200 rounded-lg opacity-60">
                    <select disabled
                        class="w-full px-3 py-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg opacity-60">
                        <option>Event Type</option>
                        <option>Seminar</option>
                        <option>Workshop</option>
                        <option>Holiday</option>
                        <option>Reminder</option>
                    </select>
                    <button disabled
                        class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg opacity-50 cursor-not-allowed bg-brand-500">Add
                        Event (Coming Soon)</button>
                </div>
            </div>

            {{-- Upcoming Events --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-theme-xs">
                <div class="px-5 py-3.5 border-b border-gray-100 ">
                    <h3 class="text-sm font-semibold text-gray-700 ">Upcoming Events</h3>
                </div>
                <div class="divide-y divide-gray-100 ">
                    @foreach ([['title' => 'SexEd Awareness Seminar', 'date' => 'Tomorrow', 'type' => 'Seminar', 'color' => 'bg-brand-500'], ['title' => 'Teen Health Workshop', 'date' => 'In 3 days', 'type' => 'Workshop', 'color' => 'bg-success-500'], ['title' => 'Educators Q&A Session', 'date' => 'Next week', 'type' => 'Session', 'color' => 'bg-purple-500']] as $ev)
                        <div class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50">
                            <div class="w-2 h-2 rounded-full {{ $ev['color'] }} flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $ev['title'] }}</p>
                                <p class="text-xs text-gray-400 ">{{ $ev['date'] }} &middot; {{ $ev['type'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
