<ol aria-label="Instructor application stages" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([['Identity information', 'identity'], ['Identity review', 'review'], ['Instructor application', 'application'], ['Final decision', 'final']] as [$label, $key])
        @php $active = $stage === $key; @endphp
        <li @if($active) aria-current="step" @endif class="rounded-xl border p-3 {{ $active ? 'border-purple-500 bg-purple-50' : 'border-gray-200 bg-white' }}">
            <span class="text-sm font-semibold text-gray-900">{{ $label }}</span>
        </li>
    @endforeach
</ol>
