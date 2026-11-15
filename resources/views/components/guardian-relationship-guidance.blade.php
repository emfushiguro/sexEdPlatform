@props(['relationshipType'])
@php
    $guidance = config('guardian_relationship_guidance.'.($relationshipType === 'foster_parent' ? 'foster_parent' : \App\Support\GuardianRelationshipTypes::pathway($relationshipType)));
@endphp
@if($guidance)
<details {{ $attributes->class(['rounded-xl border border-purple-100 bg-purple-50/50 p-4 text-sm text-gray-700']) }}>
    <summary class="cursor-pointer font-semibold text-purple-800">Evidence guidance for {{ \App\Support\GuardianRelationshipTypes::label($relationshipType) }}</summary>
    <p class="mt-2">{{ $guidance['note'] }}</p>
    <p class="mt-2 text-xs">These examples guide review; they are not an exhaustive list or automatic approval. Documents must support this dependent and the claimed role. An adult's registration consent and optional information sharing remain separate.</p>
    @foreach($guidance['sources'] as $label => $url)
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="mt-2 block font-semibold text-purple-800 underline">{{ $label }}<span class="sr-only"> (opens in a new tab)</span></a>
    @endforeach
</details>
@endif
