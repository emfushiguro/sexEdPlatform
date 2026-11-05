<figure class="rounded-xl border border-purple-100 p-3" x-data="{ unavailable: false }" x-init="$nextTick(() => { unavailable = $refs.image.complete && $refs.image.naturalWidth === 0 })">
    <figcaption class="font-semibold text-gray-900">{{ $label }}</figcaption>
    @unless($compactPersonaSummary ?? false)
        <p class="mt-1 text-xs text-gray-600">Viewed from Persona. Availability depends on Persona retention.</p>
    @endunless
    <img src="{{ route('admin.parent-verifications.persona.evidence', [$attempt, $artifact]) }}"
         x-ref="image" alt="{{ $label }} submitted for identity review" loading="lazy" referrerpolicy="no-referrer"
         x-on:error="unavailable = true" x-on:load="unavailable = false" x-show="!unavailable"
         class="mt-3 h-64 w-full rounded-lg bg-gray-50 object-contain">
    <p x-show="unavailable" x-cloak role="status" class="mt-3 text-sm text-amber-900">{{ $label }} unavailable. This pose or image may not have been collected or may no longer be available. Review the available images; retry or request a new submission if required evidence is missing.</p>
    <a href="{{ route('admin.parent-verifications.persona.evidence', [$attempt, $artifact]) }}"
       target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center font-semibold text-purple-900 underline">View {{ $label }}</a>
</figure>
