<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['canGrant'] ? ($row['age'] !== null ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800') : 'bg-rose-50 text-rose-800' }}">
    {{ $row['canGrant'] ? ($row['age'] !== null ? 'Eligible' : 'Age unavailable') : 'Not eligible' }}
</span>
<p class="mt-1 max-w-xs text-xs text-gray-500">{{ $row['eligibilityReason'] }}</p>
