<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 font-semibold text-left text-gray-600">Feature</th>
                @foreach($planCards as $plan)
                    <th scope="col" class="px-4 py-3 font-semibold text-center text-gray-600">
                        <span>{{ $plan['name'] }}</span>
                        @if(!empty($plan['is_baseline']))
                            <span class="block mt-1 text-[10px] font-bold uppercase tracking-wider text-purple-600">Baseline</span>
                        @elseif($plan['is_current'])
                            <span class="block mt-1 text-[10px] font-bold uppercase tracking-wider text-purple-600">Current</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($comparisonFeatures as $feature)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-3 text-gray-700">{{ $feature['label'] }}</td>
                    @foreach($planCards as $plan)
                        <td class="px-4 py-3 text-center">
                            @if(in_array($feature['key'], $plan['feature_keys'], true))
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700" aria-label="Included">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.414l-7.2 7.2a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-6 h-6 font-bold text-gray-400 bg-gray-100 rounded-full" aria-label="Not included">-</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
