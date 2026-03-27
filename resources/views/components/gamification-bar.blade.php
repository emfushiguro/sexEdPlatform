@props(['gamification', 'isPremium' => false])

@php
    $level = $gamification->level ?? 1;
    $totalPoints = $gamification->total_points ?? 0;
    $currentScore = $gamification->score ?? 0;
    $streak = $gamification->streak_count ?? 0;
    
    // Calculate level progress
    $nextLevelScore = $level * 100;
    $currentLevelProgress = $currentScore % 100;
    $progressPercentage = min(100, ($currentLevelProgress / 100) * 100);
    
    // Get remaining quiz attempts for today
    $userId = auth()->id();
    $dailyAttempts = \App\Models\QuizAttempt::where('user_id', $userId)
        ->whereDate('started_at', today())
        ->count();
    $maxDailyAttempts = $isPremium ? '∞' : 3;
    $remainingAttempts = $isPremium ? 'Unlimited' : max(0, 3 - $dailyAttempts);
@endphp

<!-- SoloLearn-style Top Gamification Bar -->
<div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Level Section -->
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/50">
                        <span class="text-white font-bold text-lg">{{ $level }}</span>
                    </div>
                    <div class="absolute -bottom-1 -right-1 bg-yellow-400 rounded-full w-5 h-5 flex items-center justify-center">
                        <span class="text-xs"></span>
                    </div>
                </div>
                <div class="text-white">
                    <p class="text-xs font-medium opacity-90">Level {{ $level }}</p>
                    <div class="flex items-center space-x-2">
                        <div class="w-24 bg-white/20 rounded-full h-1.5">
                            <div class="bg-yellow-400 h-1.5 rounded-full transition-all duration-300" style="width: {{ $progressPercentage }}%"></div>
                        </div>
                        <span class="text-xs font-semibold">{{ $currentLevelProgress }}/100</span>
                    </div>
                </div>
            </div>

            <!-- Points Section -->
            <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2">
                <span class="text-2xl"></span>
                <div class="text-white">
                    <p class="text-xs opacity-80">Total Points</p>
                    <p class="text-lg font-bold">{{ number_format($totalPoints) }}</p>
                </div>
            </div>

            <!-- Streak Section -->
            <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2">
                <span class="text-2xl"></span>
                <div class="text-white">
                    <p class="text-xs opacity-80">Day Streak</p>
                    <p class="text-lg font-bold">{{ $streak }} {{ $streak === 1 ? 'day' : 'days' }}</p>
                </div>
            </div>

            <!-- Quiz Attempts Section -->
            <div class="flex items-center space-x-2 {{ $isPremium ? 'bg-gradient-to-r from-yellow-400 to-orange-500' : 'bg-white/10' }} backdrop-blur-sm rounded-full px-4 py-2">
                <span class="text-2xl">{{ $isPremium ? '' : '' }}</span>
                <div class="{{ $isPremium ? 'text-gray-900' : 'text-white' }}">
                    <p class="text-xs {{ $isPremium ? 'opacity-80' : 'opacity-80' }}">
                        {{ $isPremium ? 'Premium' : 'Quiz Attempts' }}
                    </p>
                    <p class="text-lg font-bold">
                        @if($isPremium)
                            Unlimited
                        @else
                            {{ $remainingAttempts }}/3 left
                        @endif
                    </p>
                </div>
            </div>

            <!-- Premium Upgrade Button (if free user) -->
            @if(!$isPremium)
            <a href="{{ route('subscription.upgrade') }}" 
               class="bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-gray-900 font-bold px-5 py-2 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2">
                <span></span>
                <span>Go PRO</span>
            </a>
            @endif
        </div>
    </div>
</div>
