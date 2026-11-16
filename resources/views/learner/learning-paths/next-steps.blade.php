@if(count($nextSteps['paths']) || $nextSteps['suggestions']->isNotEmpty())
    <section class="mt-6 overflow-hidden rounded-2xl border border-purple-200 bg-white shadow-sm dark:border-purple-900 dark:bg-gray-900" aria-labelledby="next-steps-heading">
        <div class="bg-gradient-to-r from-[#A30EB2] via-[#730DB1] to-[#3B0CB1] px-5 py-5 text-white sm:px-6">
            <p class="text-xs font-bold uppercase tracking-widest text-purple-100">Your journey continues</p>
            <h2 id="next-steps-heading" class="mt-1 text-xl font-bold sm:text-2xl">Choose your next step</h2>
            <p class="mt-1 text-sm text-purple-100">Keep learning at your own pace.</p>
        </div>
        <div class="space-y-3 p-4 sm:p-6">
            @foreach($nextSteps['paths'] as $step)
                <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-4 dark:border-purple-900 dark:bg-purple-950/20">
                    <p class="text-xs font-bold uppercase tracking-wide text-purple-700 dark:text-purple-300">{{ $step['path']->title }}</p>
                    @if($step['next'])
                        <p class="mt-2 text-base font-bold text-gray-900 dark:text-white">{{ $step['next']['module']->title }}</p>
                        <a class="mt-3 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-purple-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-600 dark:text-purple-300" href="{{ $step['next']['action_url'] }}">Continue journey <span aria-hidden="true">→</span></a>
                    @else
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">You have reached the end of the available steps in this path.</p>
                        <a class="mt-3 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-purple-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-600 dark:text-purple-300" href="{{ route('learner.learning-paths.show', $step['path']) }}">Review journey <span aria-hidden="true">→</span></a>
                    @endif
                </div>
            @endforeach
            @foreach($nextSteps['suggestions'] as $suggestion)
                <a class="group flex min-h-16 items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 transition hover:border-purple-300 hover:bg-purple-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-600 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-purple-700 dark:hover:bg-purple-950/30" href="{{ route('learner.modules.show', $suggestion) }}">
                    <span aria-hidden="true" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-lg font-bold text-purple-700 dark:bg-purple-900 dark:text-purple-200">{{ $loop->iteration }}</span>
                    <span class="min-w-0 flex-1"><span class="block text-xs font-semibold text-gray-500 dark:text-gray-400">Suggested module</span><span class="mt-0.5 block font-semibold text-gray-900 group-hover:text-purple-700 dark:text-white dark:group-hover:text-purple-300">{{ $suggestion->title }}</span></span>
                    <span aria-hidden="true" class="text-lg text-purple-600 dark:text-purple-300">→</span>
                </a>
            @endforeach
            <a class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-purple-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-600 dark:text-purple-300" href="{{ route('learner.modules.index') }}">Explore more modules <span aria-hidden="true">→</span></a>
        </div>
    </section>
@endif
