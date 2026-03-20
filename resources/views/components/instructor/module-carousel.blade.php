@props([
    'modules' => collect(),
    'viewAllRoute' => null,
])

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5" data-testid="modules-carousel-section"
     x-data="{
        current: 0,
        timerId: null,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        init() {
            if (!this.reducedMotion) this.startAuto();
        },
        count: {{ $modules->count() }},
        get max() { return Math.max(0, this.count - 1); },
        prev() { this.current = this.current === 0 ? this.max : this.current - 1; },
        next() { this.current = this.current >= this.max ? 0 : this.current + 1; },
        goTo(index) { this.current = index; },
        startAuto() {
            if (this.count <= 1) return;
            this.stopAuto();
            this.timerId = setInterval(() => this.next(), 5000);
        },
        stopAuto() {
            if (this.timerId) clearInterval(this.timerId);
            this.timerId = null;
        }
     }"
     x-init="init()"
     @mouseenter="stopAuto()"
     @mouseleave="startAuto()">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-gray-900">Your Modules</h2>
        <div class="flex items-center gap-2">
            @if($modules->count() > 1)
                <button @click="prev()" aria-label="Previous module" class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <button @click="next()" aria-label="Next module" class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
            @endif

            @if($viewAllRoute)
                <a href="{{ $viewAllRoute }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">View all →</a>
            @endif
        </div>
    </div>

    @if($modules->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
            <p class="text-sm text-gray-400 mb-3">No modules yet</p>
            <a href="{{ route('instructor.modules.index', ['create_module' => 1]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl text-white" style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">+ Create Module</a>
        </div>
    @else
        <div class="overflow-hidden">
            <div class="flex transition-transform duration-300 ease-in-out" :style="`transform: translateX(-${current * 100}%)`">
                @foreach($modules as $module)
                    <div class="w-full flex-shrink-0 pr-1">
                        <div class="relative rounded-xl overflow-hidden">
                            <div class="aspect-video relative overflow-hidden" style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                @if($module->thumbnail)
                                    <img src="{{ Storage::url($module->thumbnail) }}" alt="{{ $module->title }}" class="w-full h-full object-cover opacity-80">
                                @endif
                                <div class="absolute top-2 right-2 flex items-center gap-1.5 z-20">
                                    <a href="{{ route('instructor.modules.show', $module) }}"
                                       aria-label="View module"
                                       class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm border border-white/30 text-white flex items-center justify-center hover:bg-white/35 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                                <a href="{{ route('instructor.modules.index', ['edit_module' => $module->id]) }}"
                                       aria-label="Edit module"
                                       class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm border border-white/30 text-white flex items-center justify-center hover:bg-white/35 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex flex-col justify-end p-3">
                                    <p class="text-white text-sm font-semibold leading-tight line-clamp-2">{{ $module->title }}</p>
                                    <span class="text-xs text-white/80 mt-1">{{ $module->enrollments_count }} enrolled</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($modules->count() > 1)
            <div class="mt-3 flex items-center justify-center gap-1.5" role="tablist" aria-label="Module slides">
                @foreach($modules as $idx => $unused)
                    <button @click="goTo({{ $idx }})"
                            aria-label="Go to slide {{ $idx + 1 }}"
                            class="w-2.5 h-2.5 rounded-full transition-colors"
                            :class="current === {{ $idx }} ? 'bg-purple-600' : 'bg-gray-300 hover:bg-gray-400'"></button>
                @endforeach
            </div>
        @endif
    @endif
</div>
