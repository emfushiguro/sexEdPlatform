<section class="rounded-xl border border-gray-200 bg-white p-6">
    <h2 class="text-lg font-semibold text-gray-900">External event access</h2>
    <p class="mt-1 text-sm text-gray-600">Changes to these details notify confirmed participants and accepted speakers. They open the event page to access the current link.</p>
    <form method="POST" action="{{ $action }}" class="mt-5 grid gap-4 sm:grid-cols-2">
        @csrf
        @method('PUT')
        <label class="sm:col-span-2">
            <span class="block text-sm font-medium text-gray-700">External event link</span>
            <input type="url" name="external_url" required value="{{ old('external_url', $seminar->external_url) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            @error('external_url') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="block text-sm font-medium text-gray-700">Link release (Philippine Time)</span>
            <input type="datetime-local" name="external_link_visible_at" value="{{ old('external_link_visible_at', $seminar->external_link_visible_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            <span class="text-xs text-gray-500">Blank means immediate access.</span>
            @error('external_link_visible_at') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="block text-sm font-medium text-gray-700">Link expiry</span>
            <select name="external_link_expiry_mode" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                @foreach(['ongoing' => 'Ongoing', 'event_end' => 'At event end', 'custom' => 'Custom time'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('external_link_expiry_mode', $seminar->external_link_expiry_mode ?? 'ongoing') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('external_link_expiry_mode') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="block text-sm font-medium text-gray-700">Custom expiry (Philippine Time)</span>
            <input type="datetime-local" name="external_link_expires_at" value="{{ old('external_link_expires_at', $seminar->external_link_expires_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            @error('external_link_expires_at') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="sm:col-span-2">
            <span class="block text-sm font-medium text-gray-700">Delivery instructions</span>
            <textarea name="delivery_instructions" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">{{ old('delivery_instructions', $seminar->delivery_instructions) }}</textarea>
            @error('delivery_instructions') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <div class="sm:col-span-2"><button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Save access details</button></div>
    </form>
</section>
