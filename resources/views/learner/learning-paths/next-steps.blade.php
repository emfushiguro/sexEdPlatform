@if(count($nextSteps['paths']) || $nextSteps['suggestions']->isNotEmpty())
    <section class="mt-6 rounded-3xl border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-800 dark:bg-gray-800" aria-labelledby="next-steps-heading">
        <p class="text-xs font-bold uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Your journey continues</p>
        <h2 id="next-steps-heading" class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Choose your next step</h2>
        <div class="mt-4 grid gap-3">
            @foreach($nextSteps['paths'] as $step)
                <div class="rounded-2xl bg-white p-4 dark:bg-gray-900">
                    <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">{{ $step['path']->title }}</p>
                    @if($step['next'])
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $step['next']['module']->title }}</p>
                        <a class="mt-3 inline-flex min-h-11 items-center font-semibold text-purple-700 underline dark:text-purple-300" href="{{ $step['next']['action_url'] }}">Continue journey</a>
                    @else
                        <p class="mt-1 text-gray-700 dark:text-gray-300">You have reached the end of the available steps in this path.</p>
                        <a class="mt-3 inline-flex min-h-11 items-center font-semibold text-purple-700 underline dark:text-purple-300" href="{{ route('learner.learning-paths.show', $step['path']) }}">Review journey</a>
                    @endif
                </div>
            @endforeach
            @foreach($nextSteps['suggestions'] as $suggestion)
                <a class="rounded-2xl bg-white p-4 font-semibold text-purple-700 hover:underline dark:bg-gray-900 dark:text-purple-300" href="{{ route('learner.modules.show', $suggestion) }}">Explore {{ $suggestion->title }}</a>
            @endforeach
        </div>
        <a class="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700 underline dark:text-indigo-300" href="{{ route('learner.modules.index') }}">Explore more modules</a>
    </section>
@endif
