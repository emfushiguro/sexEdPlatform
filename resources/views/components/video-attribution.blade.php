@props(['topic'])

@php
    $credits = $topic->video_attribution ?? [];
    $sourceUrl = $credits['source_url'] ?? ($credits !== [] && $topic->video_provider === 'custom' ? $topic->video_url : null);
    $sourceUrl = \App\Helpers\VideoEmbedHelper::isHttpUrl($sourceUrl) ? $sourceUrl : null;
    $licenseKey = $credits['license'] ?? 'unknown';
    $license = $licenseKey === 'other'
        ? ($credits['license_name'] ?? null)
        : (\App\Services\VideoAttributionService::LICENSES[$licenseKey] ?? \App\Services\VideoAttributionService::LICENSES['unknown']);
    $hasPublicCredits = filled($credits['original_title'] ?? null) || filled($credits['creator'] ?? null)
        || $sourceUrl || filled($credits['attribution_text'] ?? null) || ($licenseKey !== 'unknown' && filled($license));
    $provider = match ($topic->video_provider) {
        'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'custom' => 'External video source', 'local' => 'Uploaded video', default => null,
    };
    $dialogId = 'video-source-'.$topic->id;
@endphp

@if($topic->type === 'video' && $hasPublicCredits)
    <div x-data class="border-t border-gray-200 pt-4 dark:border-gray-700">
        <button type="button" @click="$refs.sourceDialog.showModal()" aria-haspopup="dialog" aria-controls="{{ $dialogId }}"
                class="inline-flex min-h-[44px] items-center rounded-xl border border-purple-300 bg-white px-4 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600">
            Video source &amp; credits
        </button>
        <dialog id="{{ $dialogId }}" x-ref="sourceDialog" aria-labelledby="{{ $dialogId }}-title" aria-modal="true"
                @click.self="$refs.sourceDialog.close()"
                @keydown.tab="
                    const controls = [...$el.querySelectorAll('button, a[href]')];
                    const first = controls[0];
                    const last = controls.at(-1);
                    if ($event.shiftKey && document.activeElement === first) {
                        $event.preventDefault(); last.focus();
                    } else if (!$event.shiftKey && document.activeElement === last) {
                        $event.preventDefault(); first.focus();
                    }
                "
                class="m-auto max-h-[85vh] w-[min(92vw,32rem)] overflow-y-auto rounded-2xl bg-white p-6 text-gray-900 shadow-xl backdrop:bg-black/60">
            <div class="flex items-start justify-between gap-4">
                <h2 id="{{ $dialogId }}-title" class="text-lg font-semibold">Video source</h2>
                <button type="button" autofocus @click="$refs.sourceDialog.close()"
                        class="min-h-[44px] rounded-lg px-3 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-600">Close</button>
            </div>
            <dl class="mt-4 space-y-4 break-words text-sm">
                @foreach(['original_title' => 'Original video title', 'creator' => 'Created by'] as $field => $label)
                    @if(filled($credits[$field] ?? null))
                        <div><dt class="font-semibold">{{ $label }}</dt><dd class="mt-1">{{ $credits[$field] }}</dd></div>
                    @endif
                @endforeach
                @if($provider)
                    <div><dt class="font-semibold">Platform/provider</dt><dd class="mt-1">{{ $provider }}</dd></div>
                @endif
                @if($sourceUrl)
                    <div><dt class="font-semibold">Original source</dt><dd class="mt-1"><a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer" class="inline-block py-2 font-medium text-purple-800 underline underline-offset-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-600">View original video (opens in a new tab)</a></dd></div>
                @endif
                @if(filled($license))
                    <div><dt class="font-semibold">License</dt><dd class="mt-1">{{ $license }}</dd></div>
                @endif
                @if(filled($credits['attribution_text'] ?? null))
                    <div><dt class="font-semibold">Attribution</dt><dd class="mt-1 whitespace-pre-line">{{ $credits['attribution_text'] }}</dd></div>
                @endif
            </dl>
            <p class="mt-5 text-xs text-gray-600">Source and license details are supplied by the content author and have not been independently verified. Credit alone does not grant reuse permission.</p>
        </dialog>
    </div>
@endif
