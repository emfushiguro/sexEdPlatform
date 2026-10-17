@php
    $previewUrl = route('admin.connectors.verification.documents.show', [$connector, $document, 'disposition' => 'inline']);
    $downloadUrl = route('admin.connectors.verification.documents.show', [$connector, $document, 'disposition' => 'attachment']);
    $isImage = str_starts_with((string) $document->mime_type, 'image/');
    $documentStatusClass = match ($document->status) {
        'accepted' => 'bg-emerald-100 text-emerald-800',
        'rejected', 'expired' => 'bg-rose-100 text-rose-800',
        'superseded' => 'bg-slate-100 text-slate-700',
        default => 'bg-amber-100 text-amber-800',
    };
@endphp
<article class="rounded-xl border border-gray-200 bg-white p-3">
    <div class="flex items-start gap-3">
        <button type="button" data-document-preview data-preview-url="{{ $previewUrl }}" data-preview-name="{{ $document->original_name }}"
                data-preview-type="{{ $isImage ? 'image' : 'pdf' }}" data-download-url="{{ $downloadUrl }}"
                @click="previewDocument($event.currentTarget)" aria-label="Enlarge {{ $document->original_name }}"
                class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 text-brand-700 hover:border-brand-300 focus:ring-2 focus:ring-brand-500">
            @if($isImage)
                <span class="sr-only">Open image preview</span>
                <img src="{{ $previewUrl }}" alt="{{ $document->original_name }} thumbnail" loading="lazy" class="h-full w-full object-cover"
                     x-on:error="$event.currentTarget.hidden = true; $event.currentTarget.previousElementSibling.className = 'text-xs'">
            @else
                <canvas data-pdf-thumbnail data-preview-url="{{ $previewUrl }}" aria-hidden="true" class="hidden max-h-full max-w-full"></canvas>
                <span class="flex flex-col items-center gap-1">
                    <svg class="h-9 w-9" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h6"/></svg>
                    <span class="text-xs font-bold">PDF</span>
                    <span class="text-[11px]">Open preview</span>
                </span>
            @endif
        </button>
        <div class="min-w-0 flex-1">
            <p class="break-words text-sm font-semibold text-gray-900">{{ $document->original_name }}</p>
            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                <span>Round {{ $document->submission_round }}</span>
                @if($document->issuer)<span>Issuer: {{ $document->issuer }}</span>@endif
                @if($document->issue_date)<span>Issued {{ $document->issue_date->format('M d, Y') }}</span>@endif
                @if($document->expiry_date)<span>Expires {{ $document->expiry_date->format('M d, Y') }}</span>@endif
            </div>
            <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $documentStatusClass }}">{{ str((string) $document->status)->headline() }}</span>
            @if($document->applicant_rejection_reason)
                <p class="mt-2 text-xs leading-5 text-rose-700"><span class="font-semibold">Applicant reason:</span> {{ $document->applicant_rejection_reason }}</p>
            @endif
        </div>
        <a href="{{ $downloadUrl }}" aria-label="Download {{ $document->original_name }}" title="Download file" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 focus:ring-2 focus:ring-brand-500">
            <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m-4-4 4 4 4-4M5 16v4h14v-4"/></svg>
        </a>
    </div>
    @if($canReview && $document->status === 'pending' && (int) $document->submission_round === $round)
        <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
            @foreach(['accept' => 'Accept evidence', 'reject' => 'Reject evidence'] as $decision => $label)
                @php($dialogId = $decision.'-evidence-'.$document->id)
                <button type="button" data-dialog="{{ $dialogId }}" @click="openDialog($event.currentTarget.dataset.dialog)"
                        class="min-h-10 rounded-xl border px-3 py-2 text-xs font-semibold {{ $decision === 'accept' ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' : 'border-rose-200 text-rose-700 hover:bg-rose-50' }}">{{ $label }}</button>
                <x-admin.connector-review-dialog :id="$dialogId" :title="$label" :description="$document->original_name"
                    :action="route('admin.connectors.verification.documents.review', [$connector, $document])" :round="$round" :submit="$label" :destructive="$decision === 'reject'">
                    <input type="hidden" name="decision" value="{{ $decision }}">
                    @if($decision === 'reject')
                        <label class="block text-sm font-semibold text-gray-700" for="{{ $dialogId }}-reason">Applicant reason</label>
                        <textarea id="{{ $dialogId }}-reason" name="applicant_reason" required maxlength="2000" rows="3" class="w-full rounded-xl border-gray-300 text-sm" placeholder="Explain what must be corrected">{{ old('review_dialog') === $dialogId ? old('applicant_reason') : '' }}</textarea>
                    @endif
                    <label class="block text-sm font-semibold text-gray-700" for="{{ $dialogId }}-note">Internal note <span class="font-normal text-gray-500">(optional)</span></label>
                    <textarea id="{{ $dialogId }}-note" name="internal_note" maxlength="2000" rows="3" class="w-full rounded-xl border-gray-300 text-sm">{{ old('review_dialog') === $dialogId ? old('internal_note') : '' }}</textarea>
                </x-admin.connector-review-dialog>
            @endforeach
        </div>
    @endif
</article>
