<div x-show="instructorPreviewOpen"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/55 px-4"
     @click.self="instructorPreviewOpen = false">
    <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Instructor Preview</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900">Instructor Evaluation</h3>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="instructorPreviewOpen = false">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="border-b border-gray-100 px-6 py-3">
            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                <button type="button" class="rounded-full px-3 py-1.5" :class="instructorPreviewTab === 'profile' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'" @click="instructorPreviewTab = 'profile'">Instructor Basic Profile</button>
                <button type="button" class="rounded-full px-3 py-1.5" :class="instructorPreviewTab === 'indicators' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'" @click="instructorPreviewTab = 'indicators'">Credibility Indicators</button>
                <button type="button" class="rounded-full px-3 py-1.5" :class="instructorPreviewTab === 'moderation' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'" @click="instructorPreviewTab = 'moderation'">Moderation History</button>
                <button type="button" class="rounded-full px-3 py-1.5" :class="instructorPreviewTab === 'portfolio' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'" @click="instructorPreviewTab = 'portfolio'">Module Portfolio</button>
            </div>
        </div>

        <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
            <div x-show="instructorPreviewTab === 'profile'" style="display:none;" class="space-y-4">
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-4">
                    @if(data_get($workspace, 'instructor_preview.profile.profile_picture'))
                        <img src="{{ data_get($workspace, 'instructor_preview.profile.profile_picture') }}" alt="Instructor profile" class="h-16 w-16 rounded-full border border-gray-200 object-cover">
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-700">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(data_get($workspace, 'instructor_preview.profile.full_name', 'U'), 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <p class="text-base font-semibold text-gray-900">{{ data_get($workspace, 'instructor_preview.profile.full_name', 'Unknown Instructor') }}</p>
                        <p class="text-sm text-gray-500">{{ '@' . data_get($workspace, 'instructor_preview.profile.username', 'N/A') }}</p>
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2">
                        <dt class="text-gray-500">Location</dt>
                        <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'instructor_preview.profile.location', 'N/A') }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2">
                        <dt class="text-gray-500">Educational Background</dt>
                        <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'instructor_preview.profile.educational_background', 'N/A') }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 sm:col-span-2">
                        <dt class="text-gray-500">Professional Expertise</dt>
                        <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'instructor_preview.profile.professional_expertise', 'N/A') }}</dd>
                    </div>
                </dl>
            </div>

            <div x-show="instructorPreviewTab === 'indicators'" style="display:none;" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Total Modules Created</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ data_get($workspace, 'instructor_preview.indicators.total_modules_created', 0) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total Published Modules</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ data_get($workspace, 'instructor_preview.indicators.total_published_modules', 0) }}</p>
                </div>
                <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Total Enrolled Learners</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ data_get($workspace, 'instructor_preview.indicators.total_enrolled_learners', 0) }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Certificates Earned as Learner</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ data_get($workspace, 'instructor_preview.indicators.certificates_earned', 0) }}</p>
                </div>
            </div>

            <div x-show="instructorPreviewTab === 'moderation'" style="display:none;" class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                    <p class="text-gray-500">Warning Count</p>
                    <p class="font-semibold text-gray-900">{{ data_get($workspace, 'instructor_preview.moderation.warning_count', 0) }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Violation History</h4>
                    <ul class="mt-2 space-y-2">
                        @forelse((array) data_get($workspace, 'instructor_preview.moderation.violation_history', []) as $violation)
                            <li class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700">
                                <p class="font-semibold text-gray-900">{{ data_get($violation, 'reason_label', 'Unknown') }}</p>
                                <p class="mt-1">{{ data_get($violation, 'guidance_note', 'No guidance note provided.') }}</p>
                                <p class="mt-1 text-gray-500">{{ data_get($violation, 'created_at') ? \Illuminate\Support\Carbon::parse(data_get($violation, 'created_at'))->toDayDateTimeString() : 'N/A' }}</p>
                            </li>
                        @empty
                            <li class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-500">No violation history recorded.</li>
                        @endforelse
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Suspension Records</h4>
                    <ul class="mt-2 space-y-2">
                        @forelse((array) data_get($workspace, 'instructor_preview.moderation.suspension_records', []) as $record)
                            <li class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                                <p class="font-semibold">{{ data_get($record, 'action', 'Suspension Record') }}</p>
                                <p class="mt-1">{{ data_get($record, 'reason_label', 'N/A') }}</p>
                                <p class="mt-1">{{ data_get($record, 'created_at') ? \Illuminate\Support\Carbon::parse(data_get($record, 'created_at'))->toDayDateTimeString() : 'N/A' }}</p>
                            </li>
                        @empty
                            <li class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-500">No suspension records.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div x-show="instructorPreviewTab === 'portfolio'" style="display:none;">
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Total Learners</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Creation Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse((array) data_get($workspace, 'instructor_preview.module_portfolio', []) as $portfolio)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ data_get($portfolio, 'title', 'Untitled Module') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ data_get($portfolio, 'status', 'Draft') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ data_get($portfolio, 'enrolled_learners_count', 0) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ data_get($portfolio, 'created_at') ? \Illuminate\Support\Carbon::parse(data_get($portfolio, 'created_at'))->toDayDateTimeString() : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No modules in instructor portfolio.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
