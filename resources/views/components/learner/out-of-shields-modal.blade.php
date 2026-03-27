{{--
    Out-of-shields modal.
    Opens automatically if session('out_of_shields') is set, or via Alpine event 'open-shields-modal'.
--}}
@props(['score' => 0])

<div
    x-data="{ open: {{ session('out_of_shields') ? 'true' : 'false' }} }"
    x-on:open-shields-modal.window="open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="shields-modal-title"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        x-on:click="open = false"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        {{-- Header --}}
        <div class="px-6 pt-6 pb-4 text-center" style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
            <button
                x-on:click="open = false"
                class="absolute top-4 right-4 text-white/70 hover:text-white transition-colors"
                aria-label="Close"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            <x-icons.shield state="broken" :size="48" />
            <h2 id="shields-modal-title" class="mt-3 text-xl font-bold text-white">Out of Shields!</h2>
            <p class="mt-1 text-sm text-white/80">You've used all your quiz shields for today.</p>
        </div>

        {{-- Body --}}
        <div class="p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-5">
                Spend points to refill your shields and keep going, or come back tomorrow when they reset.
            </p>

            <div class="grid grid-cols-2 gap-4">
                {{-- +1 Shield --}}
                <form method="POST" action="{{ route('learner.shields.refill') }}">
                    @csrf
                    <input type="hidden" name="type" value="single">
                    <button
                        type="submit"
                        @if(($score ?? 0) < 50) disabled @endif
                        class="w-full flex flex-col items-center gap-2 py-4 px-3 rounded-xl border-2 transition-all
                            {{ ($score ?? 0) >= 50
                                ? 'border-purple-200 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20'
                                : 'border-gray-200 opacity-50 cursor-not-allowed dark:border-gray-700' }}"
                    >
                        <x-icons.shield state="full" :size="28" />
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">+1 Shield</span>
                        <span class="text-xs text-purple-600 dark:text-purple-400 font-bold">⭐ 50 pts</span>
                    </button>
                </form>

                {{-- Full Refill --}}
                <form method="POST" action="{{ route('learner.shields.refill') }}">
                    @csrf
                    <input type="hidden" name="type" value="full">
                    <button
                        type="submit"
                        @if(($score ?? 0) < 100) disabled @endif
                        class="w-full flex flex-col items-center gap-2 py-4 px-3 rounded-xl border-2 transition-all
                            {{ ($score ?? 0) >= 100
                                ? 'border-purple-200 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20'
                                : 'border-gray-200 opacity-50 cursor-not-allowed dark:border-gray-700' }}"
                    >
                        <div class="flex gap-1">
                            <x-icons.shield state="full" :size="20" />
                            <x-icons.shield state="full" :size="20" />
                            <x-icons.shield state="full" :size="20" />
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Full Refill</span>
                        <span class="text-xs text-purple-600 dark:text-purple-400 font-bold">⭐ 100 pts</span>
                    </button>
                </form>
            </div>

            <p class="mt-4 text-center text-xs text-gray-400 dark:text-gray-500">
                Your current balance: <strong class="text-gray-700 dark:text-gray-300">⭐ {{ $score ?? 0 }} pts</strong>
            </p>

            <button
                x-on:click="open = false"
                class="mt-4 w-full py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
            >
                Maybe later
            </button>
        </div>
    </div>
</div>
