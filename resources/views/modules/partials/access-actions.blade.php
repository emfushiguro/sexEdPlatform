<button type="button" aria-label="Actions for {{ $row['user']->name }}" aria-haspopup="dialog" aria-controls="access-actions-{{ $row['user']->id }}"
    @click="document.getElementById('access-actions-{{ $row['user']->id }}').showModal()"
    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700">Actions</button>

<x-module-access-dialog :id="'access-actions-'.$row['user']->id" title="Member actions">
    @include('seminars._participant-profile', ['user' => $row['user']])
    <div class="mt-5 space-y-3">
        <button type="button" aria-haspopup="dialog" aria-controls="access-learner-{{ $row['user']->id }}"
            @click="$event.currentTarget.closest('dialog').close(); document.getElementById('access-learner-{{ $row['user']->id }}').showModal()"
            class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-left text-sm font-semibold text-gray-700">View learner information</button>
        @if($row['canGrant'] && !$row['hasAssignment'])
            @if($adminActions ?? false)
                <button type="submit" form="admin-member-action-{{ $row['user']->id }}" name="action" value="grant" class="block w-full rounded-lg bg-brand-700 px-4 py-3 text-left text-sm font-semibold text-white">Grant Access</button>
            @else
                <button type="button" aria-haspopup="dialog" aria-controls="grant-access-{{ $row['user']->id }}"
                    @click="$event.currentTarget.closest('dialog').close(); document.getElementById('grant-access-{{ $row['user']->id }}').showModal()"
                    class="block w-full rounded-lg bg-brand-700 px-4 py-3 text-left text-sm font-semibold text-white">Grant Access</button>
            @endif
        @endif
        @if($row['canRevoke'])
            @if($adminActions ?? false)
                <button type="submit" form="admin-member-action-{{ $row['user']->id }}" name="action" value="revoke" class="block w-full rounded-lg border border-rose-200 px-4 py-3 text-left text-sm font-semibold text-rose-700">Revoke Access</button>
            @else
                <button type="button" aria-haspopup="dialog" aria-controls="revoke-access-{{ $row['user']->id }}"
                    @click="$event.currentTarget.closest('dialog').close(); document.getElementById('revoke-access-{{ $row['user']->id }}').showModal()"
                    class="block w-full rounded-lg border border-rose-200 px-4 py-3 text-left text-sm font-semibold text-rose-700">Revoke Access</button>
            @endif
        @elseif(!$row['canGrant'])
            <p class="text-sm text-gray-500">Grant unavailable: {{ $row['eligibilityReason'] }}.</p>
        @endif
    </div>
</x-module-access-dialog>
