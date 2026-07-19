@if($connectors->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
        <h3 class="text-base font-semibold text-gray-900">No verified connectors yet</h3>
        <p class="mt-1 text-sm text-gray-500">Verified connectors will appear here when they are available for discovery.</p>
    </div>
@else
    <div class="grid gap-4 md:grid-cols-2">
        @foreach($connectors as $connector)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-purple-100 text-lg font-bold text-purple-700">
                        {{ strtoupper(mb_substr($connector->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="truncate text-base font-bold text-gray-900">{{ $connector->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $categories[$connector->category] ?? str($connector->category)->headline() }}</p>
                            </div>
                            <span class="inline-flex w-fit rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold uppercase text-green-700">Verified</span>
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm text-gray-600">{{ $connector->description ?: 'No description provided yet.' }}</p>
                        <p class="mt-3 text-xs text-gray-500">{{ $connector->address_line ?: 'Location available on request' }}</p>
                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <a href="{{ route('connectors.show', $connector) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">View Connector</a>
                            @php
                                $state = $connector->user_is_member ? 'member' : ($connector->user_has_pending_request ? 'pending' : ($connector->user_has_pending_invitation ? 'invited' : 'request'));
                            @endphp
                            @include('connectors.partials.membership-button', ['connector' => $connector, 'state' => $state])
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
