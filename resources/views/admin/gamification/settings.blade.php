@extends('layouts.admin')

@section('title', 'Gamification Settings')
@section('page-title', 'Gamification Settings')

@section('content')
    @php
        $points = data_get($resolvedPolicy, 'points_config', []);
        $streak = data_get($resolvedPolicy, 'streak_config', []);
        $leveling = data_get($resolvedPolicy, 'leveling_config', []);
        $formula = data_get($resolvedPolicy, 'leveling_config.formula', []);
        $shields = data_get($resolvedPolicy, 'shield_config', []);
        $safeguards = data_get($resolvedPolicy, 'safeguards_config', []);
    @endphp

    <div class="space-y-6" x-data="{ tab: 'points' }">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Gamification Settings</h2>
            <p class="mt-1 text-sm text-gray-500">Manage dynamic point, streak, leveling, and shield mechanics in one place.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-wrap gap-2">
                <button type="button" @click="tab = 'points'" :class="tab === 'points' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold">Points</button>
                <button type="button" @click="tab = 'streak'" :class="tab === 'streak' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold">Streak</button>
                <button type="button" @click="tab = 'leveling'" :class="tab === 'leveling' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold">Leveling</button>
                <button type="button" @click="tab = 'shields'" :class="tab === 'shields' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold">Shields</button>
                <button type="button" @click="tab = 'safeguards'" :class="tab === 'safeguards' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold">Safeguards</button>
                <button type="button" @click="tab = 'history'" :class="tab === 'history' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold">History</button>
            </div>

            <form method="POST" action="{{ route('admin.gamification-settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div x-show="tab === 'points'" class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Topic completion points</label>
                        <input type="number" name="points_config[topic_complete_points]" value="{{ data_get($points, 'topic_complete_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Lesson completion points</label>
                        <input type="number" name="points_config[lesson_complete_points]" value="{{ data_get($points, 'lesson_complete_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Module completion points</label>
                        <input type="number" name="points_config[module_complete_points]" value="{{ data_get($points, 'module_complete_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Certificate points</label>
                        <input type="number" name="points_config[certificate_earned_points]" value="{{ data_get($points, 'certificate_earned_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Perfect score quiz points</label>
                        <input type="number" name="points_config[quiz_bands][perfect_score_points]" value="{{ data_get($points, 'quiz_bands.perfect_score_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Pass score quiz points</label>
                        <input type="number" name="points_config[quiz_bands][pass_score_points]" value="{{ data_get($points, 'quiz_bands.pass_score_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Fail attempt quiz points</label>
                        <input type="number" name="points_config[quiz_bands][fail_attempt_points]" value="{{ data_get($points, 'quiz_bands.fail_attempt_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Level-up bonus points</label>
                        <input type="number" name="points_config[level_up_bonus_points]" value="{{ data_get($points, 'level_up_bonus_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                </div>

                <div x-show="tab === 'streak'" class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Qualifying event</label>
                        <input type="text" name="streak_config[qualifying_event]" value="{{ data_get($streak, 'qualifying_event', 'topic_completion') }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Max savers held</label>
                        <input type="number" name="streak_config[max_savers_held]" value="{{ data_get($streak, 'max_savers_held', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Saver purchase cost points</label>
                        <input type="number" name="streak_config[saver_purchase_cost_points]" value="{{ data_get($streak, 'saver_purchase_cost_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Milestones</label>
                        <div class="mt-1 space-y-2">
                            @foreach(data_get($streak, 'milestones', []) as $index => $milestone)
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <input type="number" name="streak_config[milestones][{{ $index }}][days]" value="{{ data_get($milestone, 'days', 0) }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm" min="1" placeholder="Days">
                                    <input type="number" name="streak_config[milestones][{{ $index }}][bonus_points]" value="{{ data_get($milestone, 'bonus_points', 0) }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0" placeholder="Bonus points">
                                    <input type="number" name="streak_config[milestones][{{ $index }}][priority]" value="{{ data_get($milestone, 'priority', 0) }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0" placeholder="Priority">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="streak_config[auto_consume_saver]" value="1" {{ data_get($streak, 'auto_consume_saver', false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600">
                            Auto consume saver on missed day
                        </label>
                    </div>
                </div>

                <div x-show="tab === 'leveling'" class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Threshold resolution</label>
                        <input type="text" name="leveling_config[threshold_resolution]" value="{{ data_get($leveling, 'threshold_resolution', 'explicit_then_formula') }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Growth mode</label>
                        <input type="text" name="leveling_config[formula][growth_mode]" value="{{ data_get($formula, 'growth_mode', 'linear') }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Base XP per level</label>
                        <input type="number" name="leveling_config[formula][base_xp_per_level]" value="{{ data_get($formula, 'base_xp_per_level', 100) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="1">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Growth factor</label>
                        <input type="number" name="leveling_config[formula][growth_factor]" value="{{ data_get($formula, 'growth_factor', 1) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="1">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Explicit thresholds</label>
                        <div class="mt-1 grid gap-2 sm:grid-cols-5">
                            @foreach(data_get($leveling, 'explicit_thresholds', []) as $level => $xp)
                                <div>
                                    <label class="mb-1 block text-[11px] font-semibold text-gray-500">Level {{ $level }}</label>
                                    <input type="number" name="leveling_config[explicit_thresholds][{{ $level }}]" value="{{ (int) $xp }}" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'shields'" class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Daily shields default</label>
                        <input type="number" name="shield_config[daily_shields_default]" value="{{ data_get($shields, 'daily_shields_default', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Max shields per day cap</label>
                        <input type="number" name="shield_config[max_shields_per_day_cap]" value="{{ data_get($shields, 'max_shields_per_day_cap', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Single refill cost points</label>
                        <input type="number" name="shield_config[refill_single_cost_points]" value="{{ data_get($shields, 'refill_single_cost_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Full refill cost points</label>
                        <input type="number" name="shield_config[refill_full_cost_points]" value="{{ data_get($shields, 'refill_full_cost_points', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Full refill target shields</label>
                        <input type="number" name="shield_config[refill_full_target_shields]" value="{{ data_get($shields, 'refill_full_target_shields', 0) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" min="0">
                    </div>
                </div>

                <div x-show="tab === 'safeguards'" class="grid gap-4 md:grid-cols-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="safeguards_config[allow_negative_rewards]" value="1" {{ data_get($safeguards, 'allow_negative_rewards', false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600">
                        Allow negative rewards
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="safeguards_config[allow_negative_costs]" value="1" {{ data_get($safeguards, 'allow_negative_costs', false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600">
                        Allow negative costs
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="safeguards_config[enforce_monotonic_thresholds]" value="1" {{ data_get($safeguards, 'enforce_monotonic_thresholds', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600">
                        Enforce monotonic thresholds
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="safeguards_config[enforce_unique_milestone_days]" value="1" {{ data_get($safeguards, 'enforce_unique_milestone_days', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600">
                        Enforce unique milestone days
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Version label</label>
                        <input type="text" name="version_label" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. v2026-04-16-hotfix">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Change summary</label>
                        <input type="text" name="change_summary" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" placeholder="Short summary of changes">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Save Settings</button>
                </div>
            </form>

            <div x-show="tab === 'history'" class="mt-6 rounded-2xl border border-gray-200">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h4 class="text-sm font-semibold text-gray-900">Version History</h4>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($versions as $version)
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $version->version_label ?: 'Unlabeled version #' . $version->id }}</p>
                                <p class="text-xs text-gray-500">{{ $version->created_at?->format('Y-m-d H:i') ?? 'n/a' }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.gamification-settings.restore', $version->id) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Restore</button>
                            </form>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-gray-500">No version history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
