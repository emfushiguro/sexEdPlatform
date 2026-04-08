@extends('layouts.learner-app')

@section('title', 'Module Completion')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4 sm:px-0">
    <div class="rounded-2xl overflow-hidden border border-purple-200/60 bg-white dark:bg-gray-800 shadow-sm">
        <div class="px-6 py-6 text-white" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 45%, #3B0CB1 100%);">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-100">Module Completion</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight">{{ $module->title }}</h1>
        </div>

        <div class="px-6 py-7">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20 px-4 py-4">
                <p class="text-lg font-bold text-emerald-800 dark:text-emerald-300">Congratulations!</p>
                <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                    You have successfully completed this module.
                </p>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                @if($certificate)
                    <a
                        href="{{ route('learner.certificates.show', $certificate) }}"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-bold text-white transition-opacity hover:opacity-90"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                    >
                        View Certificate
                    </a>
                @else
                    <form method="POST" action="{{ route('learner.certificates.check', $module) }}" class="flex-1">
                        @csrf
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-bold text-white transition-opacity hover:opacity-90"
                            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                        >
                            Claim Certificate
                        </button>
                    </form>
                @endif

                <a
                    href="{{ route('learner.modules.index') }}"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-5 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                >
                    Return to Modules
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
