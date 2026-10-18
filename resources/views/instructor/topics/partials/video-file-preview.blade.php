<figure data-video-preview hidden class="mt-4 max-w-lg overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
    <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3">
        <svg class="h-4 w-4 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 10 5-3v10l-5-3M4 6h9a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"/>
        </svg>
        <span class="text-sm font-semibold text-gray-800">Selected video preview</span>
    </div>
    <video data-video-preview-player controls playsinline preload="metadata"
           class="aspect-video w-full bg-black object-contain" aria-label="Selected video preview" aria-describedby="videoPreviewStatus"></video>
    <figcaption id="videoPreviewStatus" data-video-preview-status role="status" class="px-4 py-3 text-xs leading-relaxed text-gray-600"></figcaption>
</figure>
