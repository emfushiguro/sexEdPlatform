@extends('layouts.learner-app')

@section('title', 'Quiz Result — ' . $attempt->quiz->title)

@section('content')

@php
  $user         = auth()->user();
  $answerCount  = $attempt->answers ? count($attempt->answers) : 0;
  $correctCount = $attempt->answers ? collect($attempt->answers)->where('is_correct', true)->count() : 0;
  $wrongCount   = $answerCount - $correctCount;
  $canRetry     = $canRetry ?? ($user->isPremium() || (($remainingAttempts ?? 0) > 0));
  $timeLimitMinutes = $attempt->quiz->time_limit ? (int) ceil(((int) $attempt->quiz->time_limit) / 60) : null;
  $attemptLimit = $attemptLimit ?? ($attempt->quiz->attempt_limit !== null ? (int) $attempt->quiz->attempt_limit : null);
  $hasReachedAttemptLimit = $hasReachedAttemptLimit ?? false;
@endphp

<div class="max-w-3xl mx-auto space-y-5" x-data="{ showCompletionModal: {{ !empty($showCompletionModal) ? 'true' : 'false' }} }">

  @if(session('quiz_time_expired'))
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
      Time is up! Your quiz has been automatically submitted.
    </div>
  @endif

  @if(session('attempt_limit_reached') || ($hasReachedAttemptLimit && !$attempt->passed))
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
      You have reached the maximum attempt limit. Your result has been recorded as final.
    </div>
  @endif

  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
       HERO RESULT CARD
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

    {{-- Top band (pass = brand gradient, fail = red) --}}
    <div class="h-1.5 w-full {{ $attempt->passed ? '' : 'bg-red-500' }}"
         @if($attempt->passed) style="background:linear-gradient(90deg,#A30EB2,#3B0CB1)" @endif></div>

    <div class="p-6 sm:p-8">

      {{-- Score ring + headline --}}
      <div class="flex flex-col items-center text-center mb-6">

        {{-- Animated SVG score ring --}}
        <div class="relative w-32 h-32 mb-4">
          <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
            {{-- Track --}}
            <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor"
                    class="text-gray-100 dark:text-gray-700" stroke-width="10"/>
            {{-- Fill --}}
            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10"
                    stroke-linecap="round"
                    @if($attempt->passed)
                      stroke="url(#scoreGrad)"
                    @else
                      stroke="#ef4444"
                    @endif
                    stroke-dasharray="{{ round(2 * 3.14159 * 52 * $attempt->score / 100, 1) }} 327"
                    class="score-fill"/>
            @if($attempt->passed)
            <defs>
              <linearGradient id="scoreGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#A30EB2"/>
                <stop offset="100%" stop-color="#3B0CB1"/>
              </linearGradient>
            </defs>
            @endif
          </svg>
          {{-- Score label --}}
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-3xl font-bold {{ $attempt->passed ? 'text-gray-900 dark:text-white' : 'text-red-500' }}">
              {{ $attempt->score }}%
            </span>
            <span class="text-[10px] font-semibold uppercase tracking-widest {{ $attempt->passed ? 'text-purple-600 dark:text-purple-400' : 'text-red-400' }} mt-0.5">
              {{ $attempt->passed ? 'Passed' : 'Failed' }}
            </span>
          </div>
        </div>

        <h2 class="text-2xl font-bold {{ $attempt->passed ? 'text-gray-900 dark:text-white' : 'text-red-500' }}">
          {{ $attempt->passed ? 'You Passed!' : 'Keep Going!' }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {{ $correctCount }} of {{ $answerCount }} questions correct
          &nbsp;·&nbsp; Passing score: {{ $attempt->quiz->passing_score }}%
        </p>
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2 text-[11px]">
          @if($attemptLimit !== null)
            <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40">
              Attempt Limit: {{ $attemptLimit }}
            </span>
          @endif
          @if($timeLimitMinutes)
            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800/40">
              Time Limit: {{ $timeLimitMinutes }} {{ \Illuminate\Support\Str::plural('minute', $timeLimitMinutes) }}
            </span>
          @endif
        </div>
      </div>

      {{-- Gamification delta chips --}}
      <div class="flex flex-wrap items-center justify-center gap-2.5 mb-6">

        {{-- XP chip --}}
        @if($xpEarned)
        <div class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 animate-bounce-once">
          <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          <span class="text-sm font-bold text-amber-700 dark:text-amber-300">+{{ $xpEarned }} XP Earned</span>
        </div>
        @endif

        {{-- Shield delta chip (free users only) --}}
        @if(!$user->isPremium())
          @if($shieldDelta === 0)
          <div class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
            <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span class="text-sm font-bold text-green-700 dark:text-green-300">Shield Protected (±0)</span>
          </div>
          @elseif($shieldDelta === -1)
          <div class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <svg class="w-4 h-4 text-red-500 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span class="text-sm font-bold text-red-600 dark:text-red-400">−1 Shield Used</span>
          </div>
          @endif

          {{-- Remaining shields --}}
          @if($shieldsRemaining !== null)
          <div class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">
              {{ $shieldsRemaining }}/3 shields left today
            </span>
          </div>
          @endif
        @else
        <div class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
          <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z"/>
          </svg>
          <span class="text-sm font-bold text-amber-700 dark:text-amber-300">Premium &mdash; Unlimited Attempts</span>
        </div>
        @endif

      </div>

      {{-- Stats grid --}}
      <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $answerCount }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">Questions</div>
        </div>
        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $correctCount }}</div>
          <div class="text-xs text-green-600 dark:text-green-500 mt-0.5 font-medium">Correct</div>
        </div>
        <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
          <div class="text-2xl font-bold text-red-500 dark:text-red-400">{{ $wrongCount }}</div>
          <div class="text-xs text-red-500 dark:text-red-400 mt-0.5 font-medium">Incorrect</div>
        </div>
      </div>

      {{-- Action buttons --}}
      <div class="flex flex-col sm:flex-row gap-3">

        <a href="{{ route('learner.modules.index') }}"
           class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-150 active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2V7z"/>
          </svg>
          Back to Modules
        </a>

        @if(!$attempt->passed)
          @if($canRetry)
            <a href="{{ route('quizzes.show', $attempt->quiz) }}"
               class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
               style="background:linear-gradient(135deg,#A30EB2,#730DB1,#3B0CB1)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Try Again
            </a>
          @else
            <button disabled
                    class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 cursor-not-allowed">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              {{ ($hasReachedAttemptLimit ?? false) ? 'Attempt Limit Reached' : 'Daily Limit Reached' }}
            </button>
          @endif
        @endif

        @if($attempt->passed && isset($nextLesson) && $nextLesson)
          <a href="{{ route('learner.lessons.show', $nextLesson) }}"
             class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
             style="background:linear-gradient(135deg,#A30EB2,#730DB1,#3B0CB1)">
            Proceed to Next Lesson
          </a>
        @endif

      </div>

      {{-- Out-of-shields upsell --}}
      @if(!$attempt->passed && !$user->isPremium() && $shieldsRemaining !== null && $shieldsRemaining <= 0)
      <div class="mt-4 flex items-start gap-3 p-4 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
        <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
        </svg>
        <div>
          <p class="text-sm font-bold text-yellow-800 dark:text-yellow-200">You're out of shields for today</p>
          <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-0.5">
            Shields reset tomorrow, or
            <a href="{{ route('subscription.upgrade') }}" class="font-bold underline hover:no-underline">upgrade to Premium</a>
            for unlimited quiz attempts and shields.
          </p>
        </div>
      </div>
      @endif

    </div>
  </div>

  {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
       QUESTION SCORECARD
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
      <div class="border-l-4 border-purple-400 pl-3">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Question Scorecard</h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Which questions you got right and wrong</p>
      </div>
    </div>

    <div class="p-5">
      <div class="flex flex-wrap gap-2.5">
        @foreach($attempt->quiz->questions as $index => $question)
          @php
            $answer    = $attempt->answers[$question->id] ?? null;
            $isCorrect = $answer['is_correct'] ?? false;
          @endphp
          <div class="w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0
                      {{ $isCorrect ? 'bg-green-500' : 'bg-red-500' }}">
            {{ $index + 1 }}
          </div>
        @endforeach
      </div>
      <div class="flex items-center gap-5 mt-4 text-xs font-semibold text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-full bg-green-500 flex-shrink-0"></span>
          Correct
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-full bg-red-500 flex-shrink-0"></span>
          Wrong
        </span>
      </div>
    </div>

  </div>

  @if(!empty($showCompletionModal))
    <div
      x-show="showCompletionModal"
      x-cloak
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    >
      <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-500">Module Completion</p>
        <h3 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-gray-100">Congratulations!</h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
          You passed the final lesson quiz and completed this module.
        </p>
        <div class="mt-5 flex flex-col sm:flex-row gap-2">
          @if(!empty($lessonModule) && !empty($certificateClaimable))
            <form method="POST" action="{{ route('learner.certificates.check', $lessonModule) }}" class="flex-1">
              @csrf
              <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white hover:opacity-90 transition" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                Claim Certificate
              </button>
            </form>
          @elseif(!empty($lessonModule))
            <a href="{{ route('learner.modules.completion', $lessonModule) }}" class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white hover:opacity-90 transition" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
              Go to Completion Page
            </a>
          @endif
          <button type="button" @click="showCompletionModal = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            Close
          </button>
        </div>
      </div>
    </div>
  @endif

</div>{{-- end max-w-3xl --}}

@push('head')
<style>
  @keyframes bounce-once {
    0%, 100% { transform: translateY(0); }
    30% { transform: translateY(-6px); }
    60% { transform: translateY(-2px); }
  }
  .animate-bounce-once { animation: bounce-once 0.6s ease 0.3s both; }
</style>
@endpush

@endsection
