@extends('layouts.learner-app')

@section('title', 'My Children')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Gradient banner header --}}
    <div class="rounded-2xl p-6 text-white flex items-center justify-between"
         style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div>
            <h1 class="text-2xl font-bold">My Children</h1>
            <p class="text-white/80 text-sm mt-1">Manage and monitor your children's learning accounts.</p>
        </div>
        <a href="{{ route('parent.create-child') }}"
           class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white font-semibold py-2 px-4 rounded-xl transition text-sm backdrop-blur-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Child
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Children list --}}
    @if($children->isEmpty())
        {{-- Empty state --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center shadow-sm">
            <div class="w-20 h-20 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No children added yet</h3>
            <p class="text-gray-500 text-sm mb-6 max-w-sm mx-auto">
                Create a learning account for your child. You'll be able to monitor their progress and quiz results.
            </p>
            <a href="{{ route('parent.create-child') }}"
               style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
               class="inline-flex items-center gap-2 text-white font-semibold py-2.5 px-6 rounded-xl hover:opacity-90 transition text-sm shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Your First Child
            </a>
        </div>
    @else
        {{-- Children cards grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($children as $child)
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden">

                    {{-- Card top: avatar + info + badge --}}
                    <div class="p-5 flex items-center gap-4">
                        {{-- Avatar initials --}}
                        <div class="flex-shrink-0 w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold shadow"
                             style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                            {{ strtoupper(substr($child->first_name ?? $child->name, 0, 1)) }}{{ strtoupper(substr($child->last_name ?? '', 0, 1)) }}
                        </div>

                        {{-- Name & age --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 truncate">{{ $child->full_name }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if($child->learnerProfile?->birthdate)
                                    {{ \Carbon\Carbon::parse($child->learnerProfile->birthdate)->age }} years old Â·
                                @endif
                                Added {{ $child->created_at->diffForHumans() }}
                            </p>
                            @if($child->learnerProfile?->username)
                                <p class="text-xs text-purple-600 mt-0.5">@{{ $child->learnerProfile->username }}</p>
                            @endif
                        </div>

                        {{-- Consent badge --}}
                        @if($child->learnerProfile?->requires_parental_consent)
                            <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Under 13</span>
                        @else
                            <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Teen</span>
                        @endif
                    </div>

                    {{-- Stats row --}}
                    <div class="border-t border-gray-100 grid grid-cols-3 divide-x divide-gray-100 text-center py-3 bg-gray-50/50">
                        <div class="px-2">
                            <p class="text-lg font-bold text-purple-700">{{ $child->moduleEnrollments()->count() }}</p>
                            <p class="text-xs text-gray-500">Modules</p>
                        </div>
                        <div class="px-2">
                            <p class="text-lg font-bold text-purple-700">{{ $child->quizAttempts()->count() }}</p>
                            <p class="text-xs text-gray-500">Quizzes</p>
                        </div>
                        <div class="px-2">
                            <p class="text-lg font-bold text-purple-700">{{ $child->achievements()->count() }}</p>
                            <p class="text-xs text-gray-500">Achievements</p>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    @if($child->moduleEnrollments()->count() > 0)
                        @php
                            $total     = $child->moduleEnrollments()->count();
                            $completed = $child->moduleEnrollments()->where('status', 'completed')->count();
                            $pct       = $total > 0 ? round(($completed / $total) * 100) : 0;
                        @endphp
                        <div class="px-5 py-3 border-t border-gray-100">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Overall Progress</span>
                                <span class="font-medium text-gray-700">{{ $pct }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all"
                                     style="width: {{ $pct }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);">
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                        <a href="{{ route('parent.children.show', $child->id) }}"
                           class="inline-flex items-center gap-1.5 text-sm font-medium text-purple-700 hover:text-purple-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            View Progress
                        </a>

                        @if($child->quizAttempts()->count() > 0)
                            <a href="{{ route('parent.children.show', $child->id) }}"
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-purple-700 hover:text-purple-900">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Quiz Results
                            </a>
                        @endif

                        <a href="{{ route('parent.children.show', $child->id) }}"
                           class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                            </svg>
                            Manage
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
