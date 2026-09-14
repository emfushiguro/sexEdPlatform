<div
    data-video-upload-overlay
    class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 px-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="videoUploadStatus"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div data-upload-spinner class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-purple-100 border-t-purple-700"></div>

        <div data-upload-progress-panel class="hidden">
            <div class="mb-3 flex items-center justify-between gap-4">
                <span class="text-sm font-semibold text-gray-700">Upload progress</span>
                <span data-upload-percentage class="text-lg font-bold text-purple-700">0%</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-purple-100">
                <div
                    data-upload-progress
                    class="h-full rounded-full bg-purple-700 transition-[width] duration-150"
                    style="width: 0%"
                    role="progressbar"
                    aria-label="Video upload progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="0"
                ></div>
            </div>
        </div>

        <p id="videoUploadStatus" data-upload-status class="mt-4 text-center text-lg font-semibold text-gray-900" aria-live="polite">
            Saving topic...
        </p>
        <p data-upload-detail class="mt-1 text-center text-sm text-gray-500">
            Please keep this page open.
        </p>
    </div>
</div>

<div
    data-video-upload-form-error
    class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    role="alert"
></div>
