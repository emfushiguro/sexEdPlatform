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
    $fieldClass = 'mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500';
@endphp

<div class="grid gap-5 lg:grid-cols-2" x-data="{ category: @js(old('category', $seminar->category ?? 'education')), approvalMode: @js(old('registration_approval_mode', $seminar->registration_approval_mode ?? 'auto_approve')), eventType: @js($selectedType), eventFormat: @js($selectedFormat), platform: @js(old('external_platform', $seminar->external_platform ?? 'google_meet')), expiryMode: @js(old('external_link_expiry_mode', $seminar->external_link_expiry_mode ?? 'ongoing')) }">
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
        <span class="text-sm font-semibold text-gray-700">Objectives</span>
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

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Starts At (Philippine Time)</span>
        <input type="datetime-local" name="starts_at" step="1" value="{{ old('starts_at', $localStartsAt?->format($startsAtFormat)) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('starts_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Ends At (Philippine Time)</span>
        <input type="datetime-local" name="ends_at" step="1" value="{{ old('ends_at', $localEndsAt?->format($endsAtFormat)) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('ends_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Registration Deadline (Philippine Time)</span>
        <input type="datetime-local" name="registration_deadline_at" value="{{ old('registration_deadline_at', $seminar->registration_deadline_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}">
        <span class="mt-1 block text-xs text-gray-500">Leave blank to close registration when the event starts.</span>
        @error('registration_deadline_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Capacity</span>
        <input type="number" min="1" name="capacity" value="{{ old('capacity', $seminar->capacity) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
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
            <input type="datetime-local" name="external_link_visible_at" :disabled="eventFormat !== 'external'" value="{{ old('external_link_visible_at', $seminar->external_link_visible_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}">
            <span class="mt-1 block text-xs text-gray-500">Leave blank for immediate access after confirmation.</span>
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
