<x-app-layout>
    {{-- ═══════════════════════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">


            {{-- ─── Stats Cards ─── --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #ede9fe, #ddd6fe);">
                        <svg class="w-6 h-6" style="color:#6D2994" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-gray-900">{{ $totalEnrolled }}</p>
                        <p class="text-sm text-gray-400 font-medium mt-0.5">Enrolled Modules</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center bg-amber-50">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-gray-900">{{ $inProgress }}</p>
                        <p class="text-sm text-gray-400 font-medium mt-0.5">In Progress</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center bg-emerald-50">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-gray-900">{{ $totalCompleted }}</p>
                        <p class="text-sm text-gray-400 font-medium mt-0.5">Completed</p>
                    </div>
                </div>
            </div>

            {{-- ─── My Modules ─── --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">My Modules</h2>
                    <a href="{{ route('learner.modules.index') }}"
                       class="text-sm font-semibold hover:underline transition-colors"
                       style="color: #6D2994;">
                        Browse All &rarr;
                    </a>
                </div>

                @if($enrollmentData->isEmpty())
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                        <div class="mx-auto w-20 h-20 rounded-full flex items-center justify-center mb-4"
                             style="background: linear-gradient(135deg, #f3e8ff, #ede9fe);">
                            <svg class="w-10 h-10" style="color:#6D2994" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-800">No modules yet</h3>
                        <p class="mt-1 text-sm text-gray-400">Enroll in a module to start your learning journey.</p>
                        <a href="{{ route('learner.modules.index') }}"
                           class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-md hover:opacity-90 transition-opacity"
                           style="background: linear-gradient(135deg, #6D2994, #8B4DAF);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Explore Modules
                        </a>
                    </div>

                @else
                    @php
                        $cardGradients = [
                            ['from' => '#6D2994', 'to' => '#8B4DAF'],
                            ['from' => '#2563eb', 'to' => '#3b82f6'],
                            ['from' => '#db2777', 'to' => '#ec4899'],
                            ['from' => '#059669', 'to' => '#10b981'],
                            ['from' => '#d97706', 'to' => '#f59e0b'],
                            ['from' => '#7c3aed', 'to' => '#8b5cf6'],
                        ];
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($enrollmentData as $index => $data)
                            @php
                                $grad = $cardGradients[$index % count($cardGradients)];
                                $module = $data['module'];
                                $pct = $data['progress_percent'];
                            @endphp
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 flex flex-col">

                                {{-- Colored top strip --}}
                                <div class="h-1.5 w-full flex-shrink-0"
                                     style="background: linear-gradient(90deg, {{ $grad['from'] }}, {{ $grad['to'] }});">
                                </div>

                                <div class="p-5 flex flex-col flex-1">
                                    {{-- Status + lesson count --}}
                                    <div class="flex items-center justify-between mb-3">
                                        @if($data['enrollment']->completed_at)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Completed
                                            </span>
                                        @elseif($pct > 0)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                In Progress
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-50 text-gray-500 border border-gray-200">
                                                Not Started
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-400 font-medium">{{ $data['total_lessons'] }} {{ Str::plural('lesson', $data['total_lessons']) }}</span>
                                    </div>

                                    {{-- Title --}}
                                    <h3 class="text-base font-bold text-gray-900 leading-snug line-clamp-2 mb-1">{{ $module->title }}</h3>

                                    {{-- Description --}}
                                    @if($module->description)
                                        <p class="text-xs text-gray-400 line-clamp-2 mb-4 flex-1">{{ $module->description }}</p>
                                    @else
                                        <div class="flex-1 mb-4"></div>
                                    @endif

                                    {{-- Progress bar --}}
                                    <div class="mt-auto">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs text-gray-400">Progress</span>
                                            <span class="text-xs font-bold" style="color: {{ $grad['from'] }}">{{ $pct }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full transition-all duration-700"
                                                 style="width: {{ $pct }}%; background: linear-gradient(90deg, {{ $grad['from'] }}, {{ $grad['to'] }});"></div>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1">{{ $data['completed_lessons'] }} / {{ $data['total_lessons'] }} completed</p>
                                    </div>

                                    {{-- CTA --}}
                                    <a href="{{ route('learner.modules.show', $module->id) }}"
                                       class="mt-4 w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white transition-all duration-200 hover:opacity-90 active:scale-95 shadow-sm"
                                       style="background: linear-gradient(135deg, {{ $grad['from'] }}, {{ $grad['to'] }});">
                                        @if($data['enrollment']->completed_at)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Review Module
                                        @elseif($pct > 0)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Continue
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                            Start Learning
                                        @endif
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ─── Quick Links ─── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('learner.modules.index') }}"
                   class="group flex items-center gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
                         style="background: linear-gradient(135deg, #ede9fe, #ddd6fe);">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800">Browse All Modules</p>
                        <p class="text-xs text-gray-400 mt-0.5">Discover new learning content</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('learner.certificates.index') }}"
                   class="group flex items-center gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center bg-amber-50">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800">My Certificates</p>
                        <p class="text-xs text-gray-400 mt-0.5">View your earned certificates</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

        </div>
    </div>

</x-app-layout>

