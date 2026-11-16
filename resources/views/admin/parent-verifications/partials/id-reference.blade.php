@php
    $reference = config('identity_reference_guides.'.$idType);
@endphp
@if($idType)
<details class="mt-3 rounded-xl border border-purple-100 bg-purple-50/50 p-4 text-sm text-gray-700">
    <summary class="cursor-pointer font-semibold text-purple-800">ID reference guide: {{ $idLabel }}</summary>
    @if($showLibraryLink ?? true)
        <a href="{{ route('admin.identity-references.show', ['type' => $idType]) }}" target="_blank" rel="noopener" class="mt-3 inline-flex rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white hover:bg-purple-900">View reference images<span class="sr-only"> (opens in a new tab)</span></a>
    @endif
    <p class="mt-2">Compare the document details with the account and related evidence. Appearance alone does not prove authenticity. Different valid formats may exist.</p>
    @if($reference)
        <p class="mt-2">{{ $reference['note'] }}</p>
        <a href="{{ $reference['source'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block font-semibold text-purple-800 underline">Open official source</a>
        @if(!empty($reference['rules_url']))<a href="{{ $reference['rules_url'] }}" target="_blank" rel="noopener noreferrer" class="ml-3 mt-2 inline-block font-semibold text-purple-800 underline">Read civil registry validity rules</a>@endif
        @if(!empty($reference['verification_url']))<a href="{{ $reference['verification_url'] }}" target="_blank" rel="noopener noreferrer" class="ml-3 mt-2 inline-block font-semibold text-purple-800 underline">Open official verification</a>@endif
        <p class="mt-1 text-xs text-gray-600">Source checked {{ $reference['checked'] }}. Recheck current issuer guidance before deciding.</p>
    @else
        <p class="mt-2">Reference and expiry: needs review. Consult the issuer or institution; request clarification or escalate if uncertain.</p>
    @endif
</details>
@endif
