@props(['steps' => null])

@php
// Normalize step keys: support both old (active/done) and new (isActive/isCompleted) formats
$steps = $steps ? array_map(function ($step) {
    $done   = $step['isCompleted'] ?? $step['done']   ?? false;
    $active = $step['isActive']    ?? $step['active']  ?? false;
    return [
        'label'       => $step['label'] ?? '',
        'isCompleted' => $done,
        'isActive'    => $active,
        'isUpcoming'  => !$done && !$active,
    ];
}, $steps) : null;
@endphp

@if($steps)
<div class="max-w-lg mx-auto mb-6">
    <div class="bg-white rounded-2xl border border-purple-100/60 shadow-sm px-6 py-4">
        <div class="flex items-center justify-between relative">

            @foreach($steps as $step)
                {{-- Step circle + label --}}
                <div class="flex flex-col items-center flex-shrink-0" style="min-width: 2.5rem;">
                    @if($step['isCompleted'])
                        {{-- Completed: filled gradient circle with checkmark --}}
                        <div class="w-8 h-8 rounded-full flex items-center justify-center"
                             style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                    @elseif($step['isActive'])
                        {{-- Active: white circle with purple ring and bold number --}}
                        <div class="w-8 h-8 rounded-full bg-white ring-2 ring-purple-400 shadow-sm flex items-center justify-center">
                            <span class="text-sm font-bold text-purple-700">{{ $loop->index + 1 }}</span>
                        </div>
                    @else
                        {{-- Upcoming: white circle with grey border --}}
                        <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-400">{{ $loop->index + 1 }}</span>
                        </div>
                    @endif

                    {{-- Label --}}
                    <span class="mt-1.5 text-center leading-tight"
                          style="font-size: 0.65rem; white-space: nowrap;"
                          @class([
                              'font-semibold text-purple-700' => $step['isActive'],
                              'font-medium text-purple-500'   => $step['isCompleted'],
                              'font-medium text-gray-400'     => $step['isUpcoming'],
                          ])>
                        {{ $step['label'] }}
                    </span>
                </div>

                {{-- Connector line after this circle (not after the last step) --}}
                @if(!$loop->last)
                    <div class="flex-1 h-0.5 mx-1 {{ $step['isCompleted'] ? 'bg-gradient-to-r from-purple-600 to-indigo-700' : 'bg-gray-200' }}"></div>
                @endif
            @endforeach

        </div>
    </div>
</div>
@endif
