@php
    $appealState = (string) ($appealStatusValue ?? 'none');
    $appealTone = match ($appealState) {
        'pending_review', 'appeal_pending' => 'bg-amber-50 text-amber-800 border-amber-200',
        'clarification_requested' => 'bg-purple-50 text-purple-800 border-purple-200',
        'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-800 border-rose-200',
        default => 'bg-gray-50 text-gray-600 border-gray-200',
    };
    $appealLabel = match ($appealState) {
        'pending_review', 'appeal_pending' => 'Pending Review',
        'clarification_requested' => 'Awaiting clarification',
        'none', '' => 'No appeal submitted',
        default => ucfirst(str_replace('_', ' ', $appealState)),
    };
@endphp
<span class="inline-flex max-w-full items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $appealTone }}">{{ $appealLabel }}</span>
