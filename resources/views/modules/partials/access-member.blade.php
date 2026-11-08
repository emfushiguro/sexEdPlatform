@include('seminars._participant-profile', ['user' => $row['user']])
<p class="mt-2 text-xs font-normal text-gray-500">{{ $row['ageLabel'] }}</p>
<x-module-access-dialog :id="'access-learner-'.$row['user']->id" title="Learner information">
    @include('seminars._participant-profile', ['user' => $row['user']])
    <dl class="mt-5 space-y-3 text-sm font-normal">
        @foreach([
            'Learner age group' => $row['ageLabel'].($row['age'] !== null ? ' · '.$row['age'].' years old' : ''),
            'Organizational role' => $row['membership']?->role?->name ?? 'Former member',
            'Membership' => ucfirst($row['membership']?->status ?? 'removed'),
            'Module audience' => $entitlement->module ? implode(', ', $entitlement->module->learnerCategoryLabels()) : 'Unavailable module',
            'Eligibility' => $row['eligibilityReason'],
            'Connector assignment' => str($row['assignment']?->status ?? 'unassigned')->headline(),
        ] as $label => $value)
            <div><dt class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</dt><dd class="mt-1 break-words text-gray-900">{{ $value }}</dd></div>
        @endforeach
    </dl>
    @if($row['hasIndependent'])<p class="mt-4 text-sm text-emerald-700">Independent access remains available.</p>@endif
    @if($row['hasOtherConnector'])<p class="mt-4 text-sm text-emerald-700">Access through another Connector remains available.</p>@endif
</x-module-access-dialog>
