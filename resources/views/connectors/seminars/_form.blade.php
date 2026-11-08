@php
    $selectedAges = old('learner_age_categories', $seminar->learner_age_categories ?? []);
    $selectedAges = is_array($selectedAges) ? $selectedAges : [];
    $deliveryLocked = $seminar->exists && ($seminar->isNativeDelivery() || $seminar->registrants()->exists());
    $selectedType = $deliveryLocked ? $seminar->type : old('type', $seminar->type ?? 'webinar');
    $selectedFormat = $deliveryLocked ? $seminar->event_format : old('event_format', $seminar->event_format ?? 'external');
    $localStartsAt = $seminar->localStartsAt();
    $localEndsAt = $seminar->localEndsAt();
    $startsAtFormat = $localStartsAt && $localStartsAt->second !== 0 ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i';
    $endsAtFormat = $localEndsAt && $localEndsAt->second !== 0 ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i';
    $scheduleLocked = $seminar->exists && $seminar->registrants()->exists();
    $scheduleInitial = [
        'starts_at' => old('starts_at', $localStartsAt?->format($startsAtFormat)),
        'ends_at' => old('ends_at', $localEndsAt?->format($endsAtFormat)),
        'registration_deadline_at' => old('registration_deadline_at', $seminar->registration_deadline_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i:s')),
        'external_link_visible_at' => old('external_link_visible_at', $seminar->external_link_visible_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i:s')),
        'suggest' => ! $seminar->exists && ! session()->hasOldInput(),
        'locked' => $scheduleLocked,
    ];
    $fieldClass = 'mt-1 min-w-0 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500';
@endphp

<div x-data="seminarSchedule(@js($scheduleInitial))">
<div class="grid grid-cols-1 gap-5 lg:grid-cols-2" x-data="{ category: @js(old('category', $seminar->category ?? 'education')), approvalMode: @js(old('registration_approval_mode', $seminar->registration_approval_mode ?? 'auto_approve')), eventType: @js($selectedType), eventFormat: @js($selectedFormat), platform: @js(old('external_platform', $seminar->external_platform ?? 'google_meet')), expiryMode: @js(old('external_link_expiry_mode', $seminar->external_link_expiry_mode ?? 'ongoing')) }">
    <fieldset class="min-w-0 lg:col-span-2" x-data="{
        savedUrl: @js($seminar->thumbnailUrl()), previewUrl: null, remove: @js((bool) old('remove_thumbnail', false)), failed: false,
        revoke() { if (this.previewUrl) URL.revokeObjectURL(this.previewUrl); this.previewUrl = null; },
        select(event) { this.revoke(); this.remove = false; this.failed = false; const file = event.target.files[0]; if (file) this.previewUrl = URL.createObjectURL(file); },
        clear() { this.revoke(); this.$refs.thumbnail.value = ''; this.failed = false; },
        destroy() { this.revoke(); }
    }" @reset.window="clear(); remove = false">
        <legend class="text-sm font-semibold text-gray-700">Event thumbnail (optional)</legend>
        <p id="seminar-thumbnail-help" class="mt-1 text-sm text-gray-600">JPG, PNG or WebP, up to 2 MB. Recommended aspect ratio: 16:9.</p>
        <div class="mt-3 max-w-md" x-show="!remove && (previewUrl || savedUrl)" x-cloak>
            <div class="relative aspect-video overflow-hidden rounded-lg bg-purple-50">
                <span x-show="failed" class="absolute inset-0 flex items-center justify-center text-sm text-gray-600">Image preview unavailable</span>
                <img :src="previewUrl || savedUrl" alt="Event thumbnail preview" width="640" height="360" x-show="!failed" x-on:error="failed = true" class="absolute inset-0 h-full w-full object-cover">
            </div>
        </div>
        <label class="mt-3 block text-sm font-medium text-gray-700">Choose or replace thumbnail
            <input x-ref="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" aria-describedby="seminar-thumbnail-help" @change="select($event)" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2">
        </label>
        <button type="button" x-show="previewUrl" x-cloak @click="clear()" class="mt-2 text-sm font-semibold text-purple-700">Reset selection</button>
        @if($seminar->thumbnailUrl())
            <label class="mt-3 flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="remove_thumbnail" value="1" x-model="remove" @change="if (remove) clear()" @checked(old('remove_thumbnail')) class="rounded border-gray-300 text-purple-700">Remove saved thumbnail</label>
        @endif
        @error('thumbnail') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        @error('remove_thumbnail') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </fieldset>
    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Title</span>
        <input name="title" value="{{ old('title', $seminar->title) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('title') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Description</span>
        <textarea name="description" rows="3" required class="{{ $fieldClass }}">{{ old('description', $seminar->description) }}</textarea>
        @error('description') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Objectives (optional)</span>
        <textarea name="purpose" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('purpose', $seminar->purpose) }}</textarea>
        @error('purpose') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Event Type</span>
        @if($deliveryLocked)
            <span class="mt-1 block rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-gray-700">{{ ucfirst($seminar->type) }}</span>
            <input type="hidden" name="type" value="{{ $seminar->type }}">
        @else
            <select name="type" x-model="eventType" @change="if (eventType === 'webinar' && eventFormat === 'in_person') eventFormat = 'external'" class="{{ $fieldClass }}">
                <option value="seminar">Seminar</option>
                <option value="webinar">Webinar</option>
            </select>
        @endif
        @error('type') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Event Format</span>
        @if($deliveryLocked)
            <span class="mt-1 block rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-gray-700">{{ $seminar->isNativeDelivery() ? 'Native (Agora)' : ($seminar->event_format === 'in_person' ? 'In Person' : 'External Platform') }}</span>
            <input type="hidden" name="event_format" value="{{ $seminar->event_format }}">
            <span class="mt-1 block text-xs text-gray-500">{{ $seminar->isNativeDelivery() ? 'Existing Agora delivery is fixed.' : 'Delivery cannot change after registration.' }}</span>
        @else
            <select name="event_format" x-model="eventFormat" class="{{ $fieldClass }}">
                <option value="in_person" x-show="eventType === 'seminar'" :disabled="eventType !== 'seminar'">In Person</option>
                <option value="external">External Platform</option>
            </select>
        @endif
        @error('event_format') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Category</span>
        <select name="category" x-model="category" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            @foreach(config('seminars.categories') as $key => $label)
                <option value="{{ $key }}" @selected(old('category', $seminar->category) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block" x-show="category === 'other'" x-cloak>
        <span class="text-sm font-semibold text-gray-700">Custom Category</span>
        <input name="custom_category" value="{{ old('custom_category', $seminar->custom_category) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('custom_category') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <fieldset class="grid min-w-0 grid-cols-1 gap-4 rounded-xl border border-gray-200 p-4 lg:col-span-2 lg:grid-cols-2">
        <legend class="px-1 text-sm font-semibold text-gray-700">Event schedule (Philippine Time / PHT)</legend>
        @foreach(['starts_at' => 'Start', 'ends_at' => 'End'] as $field => $label)
            <div>
                <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <label class="min-w-0 flex-1"><span class="sr-only">{{ $label }} date (PHT)</span><input type="date" :value="part('{{ $field }}', 0)" @input="inputPart('{{ $field }}', 0, $event.target.value)" :disabled="locked" required class="{{ $fieldClass }}"></label>
                    <label class="min-w-0 flex-1"><span class="sr-only">{{ $label }} time (PHT)</span><input type="time" :step="timeStep('{{ $field }}')" :value="part('{{ $field }}', 1)" @input="inputPart('{{ $field }}', 1, $event.target.value)" :disabled="locked" required class="{{ $fieldClass }}"></label>
                </div>
                <input type="hidden" name="{{ $field }}" :value="{{ $field }}" value="{{ $scheduleInitial[$field] }}">
                @error($field) <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
        @endforeach
        <div class="sm:col-span-2">
            @if($scheduleLocked)
                <p class="text-sm text-gray-600">The event schedule cannot change after a registration exists.</p>
            @else
                <div class="flex flex-wrap items-center gap-2"><span class="text-xs text-gray-500">Set duration:</span>
                    @foreach([30, 60, 90, 120] as $minutes)
                        <button type="button" @click="duration({{ $minutes }})" :disabled="!starts_at" class="rounded-lg border border-gray-200 px-3 py-1 text-xs disabled:opacity-50">{{ $minutes }} min</button>
                    @endforeach
                    <button type="button" @click="suggest('ends_at')" class="text-xs font-semibold text-purple-700">Use suggested time</button>
                </div>
            @endif
            <p class="mt-2 text-sm" :class="durationMinutes <= 0 ? 'text-red-600' : 'text-gray-600'" aria-live="polite" x-text="feedback"></p>
            <p class="mt-1 text-sm text-gray-600" aria-live="polite" x-text="dateSummary"></p>
        </div>
    </fieldset>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Registration Deadline (Philippine Time)</span>
        <input type="datetime-local" :step="timeStep('registration_deadline_at')" name="registration_deadline_at" x-model="registration_deadline_at" @input="automatic.registration_deadline_at = false" value="{{ $scheduleInitial['registration_deadline_at'] }}" class="{{ $fieldClass }}">
        <span class="mt-1 block text-xs text-gray-500">Suggested: 30 minutes before start. Blank closes registration at event start.</span>
        <button type="button" @click="suggest('registration_deadline_at')" class="mt-1 text-xs font-semibold text-purple-700">Use suggested time</button>
        <span class="mt-1 block text-sm text-red-600" aria-live="polite" x-text="deadlineFeedback"></span>
        @error('registration_deadline_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Capacity</span>
        <input type="number" min="1" max="100000" step="1" aria-describedby="seminar-capacity-help" name="capacity" value="{{ old('capacity', $seminar->capacity) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        <span id="seminar-capacity-help" class="mt-1 block text-sm text-gray-600">Your organization decides how many participants to accommodate. Set a registration limit based on your arrangements and platform capacity, or leave blank for no registration limit.</span>
        @error('capacity') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <fieldset class="lg:col-span-2">
        <legend class="text-sm font-semibold text-gray-700">Registration Approval</legend>
        <div class="mt-2 grid gap-3 md:grid-cols-2">
            <label class="cursor-pointer rounded-2xl border p-4 transition" :class="approvalMode === 'auto_approve' ? 'border-purple-300 bg-purple-50 ring-2 ring-purple-100' : 'border-gray-200 bg-white hover:bg-gray-50'">
                <input type="radio" name="registration_approval_mode" value="auto_approve" x-model="approvalMode" class="sr-only">
                <span class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                    </span>
                    <span>
                        <span class="block font-semibold text-gray-900">Auto Approve</span>
                        <span class="mt-1 block text-sm text-gray-500">Eligible registrants are accepted immediately and counted as participants.</span>
                    </span>
                </span>
            </label>
            <label class="cursor-pointer rounded-2xl border p-4 transition" :class="approvalMode === 'manual' ? 'border-purple-300 bg-purple-50 ring-2 ring-purple-100' : 'border-gray-200 bg-white hover:bg-gray-50'">
                <input type="radio" name="registration_approval_mode" value="manual" x-model="approvalMode" class="sr-only">
                <span class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                    <span>
                        <span class="block font-semibold text-gray-900">Manual Approval</span>
                        <span class="mt-1 block text-sm text-gray-500">Registrants wait in pending status until a host approves or rejects them.</span>
                    </span>
                </span>
            </label>
        </div>
        @error('registration_approval_mode') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </fieldset>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Target Participants</span>
        <select name="target_participants" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            <option value="learners" @selected(old('target_participants', $seminar->target_participants) === 'learners')>Learners</option>
            <option value="instructors" @selected(old('target_participants', $seminar->target_participants) === 'instructors')>Instructors</option>
            <option value="learners_and_instructors" @selected(old('target_participants', $seminar->target_participants) === 'learners_and_instructors')>Learners and Instructors</option>
        </select>
        @error('target_participants') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <div class="grid gap-5 lg:col-span-2 lg:grid-cols-2" x-show="eventFormat === 'in_person'" x-cloak>
        <label class="block">
            <span class="text-sm font-semibold text-gray-700">Venue Name</span>
            <input name="location" :disabled="eventFormat !== 'in_person'" value="{{ old('location', $seminar->location) }}" class="{{ $fieldClass }}">
            @error('location') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-semibold text-gray-700">Venue Address</span>
            <input name="venue_address" :disabled="eventFormat !== 'in_person'" value="{{ old('venue_address', $seminar->venue_address) }}" class="{{ $fieldClass }}">
            @error('venue_address') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-semibold text-gray-700">Room or Area (Optional)</span>
            <input name="venue_room" :disabled="eventFormat !== 'in_person'" value="{{ old('venue_room', $seminar->venue_room) }}" class="{{ $fieldClass }}">
            @error('venue_room') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block lg:col-span-2">
            <span class="text-sm font-semibold text-gray-700">Arrival Instructions (Optional)</span>
            <textarea name="delivery_instructions" :disabled="eventFormat !== 'in_person'" rows="2" class="{{ $fieldClass }}">{{ old('delivery_instructions', $seminar->delivery_instructions) }}</textarea>
            @error('delivery_instructions') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="grid gap-5 lg:col-span-2 lg:grid-cols-2" x-show="eventFormat === 'external'" x-cloak>
        <label class="block">
            <span class="text-sm font-semibold text-gray-700">External Platform</span>
            <select name="external_platform" x-model="platform" :disabled="eventFormat !== 'external'" class="{{ $fieldClass }}">
                <option value="google_meet">Google Meet</option>
                <option value="zoom">Zoom</option>
                <option value="microsoft_teams">Microsoft Teams</option>
                <option value="google_classroom">Google Classroom</option>
                <option value="other">Other</option>
            </select>
            @error('external_platform') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block" x-show="platform === 'other'" x-cloak>
            <span class="text-sm font-semibold text-gray-700">Other Platform Name</span>
            <input name="external_platform_name" :disabled="eventFormat !== 'external' || platform !== 'other'" value="{{ old('external_platform_name', $seminar->external_platform_name) }}" class="{{ $fieldClass }}">
            @error('external_platform_name') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block lg:col-span-2">
            <span class="text-sm font-semibold text-gray-700">External Event Link</span>
            <input type="url" name="external_url" :disabled="eventFormat !== 'external'" value="{{ old('external_url', $seminar->external_url) }}" class="{{ $fieldClass }}">
            @error('external_url') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-semibold text-gray-700">Link Release (Philippine Time)</span>
            <input type="datetime-local" name="external_link_visible_at" :step="timeStep('external_link_visible_at')" x-model="external_link_visible_at" @input="automatic.external_link_visible_at = false" :disabled="eventFormat !== 'external'" value="{{ old('external_link_visible_at', $seminar->external_link_visible_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}">
            <span class="mt-1 block text-xs text-gray-500">Suggested: 15 minutes before start. Blank releases immediately after confirmation.</span>
            <button type="button" @click="suggest('external_link_visible_at')" class="mt-1 text-xs font-semibold text-purple-700">Use suggested time</button>
            @error('external_link_visible_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-semibold text-gray-700">Link Expiry</span>
            <select name="external_link_expiry_mode" x-model="expiryMode" :disabled="eventFormat !== 'external'" class="{{ $fieldClass }}">
                <option value="ongoing">Ongoing</option>
                <option value="event_end">At Event End</option>
                <option value="custom">Custom Time</option>
            </select>
            <span class="mt-1 block text-xs text-gray-500">Ongoing means the link remains available after completion.</span>
            @error('external_link_expiry_mode') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block" x-show="expiryMode === 'custom'" x-cloak>
            <span class="text-sm font-semibold text-gray-700">Custom Expiry (Philippine Time)</span>
            <input type="datetime-local" name="external_link_expires_at" :disabled="eventFormat !== 'external' || expiryMode !== 'custom'" value="{{ old('external_link_expires_at', $seminar->external_link_expires_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}">
            @error('external_link_expires_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <fieldset class="lg:col-span-2">
        <legend class="text-sm font-semibold text-gray-700">Learner Age Categories</legend>
        <div class="mt-2 flex flex-wrap gap-3">
            @foreach(config('seminars.learner_age_categories') as $key => $label)
                <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <input type="checkbox" name="learner_age_categories[]" value="{{ $key }}" @checked(in_array($key, $selectedAges, true)) class="rounded border-gray-300 text-purple-700 focus:ring-purple-500">
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('learner_age_categories') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </fieldset>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ $seminar->exists ? route('connector.seminars.show', [$connector, $seminar]) : route('connector.seminars.index', $connector) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
    <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">{{ $submitLabel }}</button>
</div>

</div>
