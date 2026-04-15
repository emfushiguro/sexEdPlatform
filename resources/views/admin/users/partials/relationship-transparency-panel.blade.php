<h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Parent-Child Transparency</h3>
<p class="text-xs text-gray-500 mb-4">Relationship updates are managed from the dedicated relationship management page.</p>

@if($linkedParent)
    <div class="rounded-xl border border-brand-200 bg-brand-50 p-4 mb-4">
        <p class="text-xs text-brand-700 font-semibold uppercase tracking-wide">Linked Parent</p>
        <p class="text-sm font-semibold text-gray-900 mt-1">{{ $linkedParent->name }}</p>
        <p class="text-xs text-gray-600">{{ $linkedParent->email }}</p>
        @php
            $parentRelation = $parentRelationships->first();
            $isVerified = optional($parentRelation)->relationship_verified_at !== null;
        @endphp
        <p class="text-[11px] text-gray-500 mt-2">Verification: {{ $isVerified ? 'Verified' : 'Unverified' }}</p>
    </div>
@endif

@if($childRelationships->isNotEmpty())
    <div class="space-y-3 mb-4">
        @foreach($childRelationships as $relationship)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $relationship->child?->name ?? 'Unknown Child' }}</p>
                        <p class="text-xs text-gray-600">{{ $relationship->child?->email }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Verification: {{ $relationship->relationship_verified_at ? 'Verified' : 'Unverified' }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $relationship->relationship_verified_at ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $relationship->relationship_verified_at ? 'Verified' : 'Unverified' }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@endif

<a href="{{ route('admin.users.relationships.index') }}" data-testid="admin-users-relationships-link" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-brand-200 text-brand-700 hover:bg-brand-50 text-sm font-medium transition-colors">
    Manage Relationships
</a>
