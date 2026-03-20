@extends('layouts.learner-app')

@section('title', 'My Certificates')

@section('content')
<div class="space-y-5">

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">My Certificates</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $certificates->count() }} {{ \Illuminate\Support\Str::plural('certificate', $certificates->count()) }} earned
                </p>
            </div>
            <a href="{{ route('learner.modules.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800/40 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                Browse Modules
            </a>
        </div>
    </div>

    @if($certificates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($certificates as $certificate)
                <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700" style="background: linear-gradient(120deg, rgba(163, 14, 178, 0.08), rgba(59, 12, 177, 0.08));">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white"
                                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $certificate->module_title }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Issued {{ $certificate->issued_at->format('F d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-4 space-y-1.5">
                        <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Certificate Number</p>
                        <p class="text-sm font-mono font-semibold text-gray-700 dark:text-gray-200">{{ $certificate->certificate_number }}</p>
                    </div>

                    <div class="px-5 pb-5 flex flex-col gap-2">
                        <a href="{{ route('learner.certificates.show', $certificate) }}"
                           class="w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition hover:opacity-90"
                           style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                            View Certificate
                        </a>
                        @if(auth()->user()->isPremium())
                            <a href="{{ route('learner.certificates.download', $certificate) }}"
                               class="w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors">
                                Download PDF
                            </a>
                        @else
                            <a href="{{ route('subscription.index') }}"
                               class="w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                                Upgrade to Download
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm px-6 py-12 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl text-white mb-4"
                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No certificates yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Complete modules and pass required assessments to earn your first certificate.</p>
            <a href="{{ route('learner.modules.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition hover:opacity-90"
               style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                Browse Modules
            </a>
        </div>
    @endif

</div>
@endsection
