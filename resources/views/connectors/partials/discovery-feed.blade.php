@if($connectors->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
        <h3 class="text-base font-semibold text-gray-900">No verified connectors yet</h3>
        <p class="mt-1 text-sm text-gray-500">Verified connectors will appear here when they are available for discovery.</p>
    </div>
@else
    <div class="grid gap-4 md:grid-cols-2">
        @foreach($connectors as $connector)
            @php
                $publiclyVerified = in_array($connector->verification_status, ['verified', 'legacy_verified'], true);
                $verificationUpdateInProgress = in_array($connector->verification_status, ['under_review', 'resubmission_required', 'reverification_required'], true);
                $logoUrl = $connector->status === 'verified' && filled($connector->logo_path)
                    ? Storage::disk(config('connector_verification.logo_disk', 'public'))->url($connector->logo_path)
                    : null;
            @endphp
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex gap-4">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $connector->name }} logo" class="h-12 w-12 flex-shrink-0 rounded-xl object-cover">
                    @else
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-purple-100 text-lg font-bold text-purple-700">
                            {{ strtoupper(mb_substr($connector->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="truncate text-base font-bold text-gray-900">{{ $connector->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $categories[$connector->category] ?? str($connector->category)->headline() }}</p>
                            </div>
                            @if($publiclyVerified)
                                <div class="sm:text-right">
                                    <span class="inline-flex w-fit rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold uppercase text-green-700">Verified</span>
                                    <p class="mt-1 text-xs text-gray-500">Verified by Conscious Connections based on submitted organizational and authorization documents.</p>
                                </div>
                            @elseif($verificationUpdateInProgress)
                                <span class="inline-flex w-fit rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600">Verification update in progress</span>
                            @endif
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
