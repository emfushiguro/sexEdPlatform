@php
    $roleMap = [
        'learner' => 'bg-brand-50 text-brand-700',
        'instructor' => 'bg-purple-50 text-purple-700',
        'admin' => 'bg-error-50 text-error-700',
    ];

    $statusMap = [
        'active' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'inactive' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'suspended' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        'archived' => 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
    ];
@endphp

@forelse($users as $user)
    @php
        $accountType = $user->account_type ?: $user->deriveAccountType();
        $rowNumber = (($users->currentPage() - 1) * $users->perPage()) + $loop->iteration;
    @endphp
    <tr class="transition hover:bg-brand-50/55">
        <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ $rowNumber }}</td>
        <td class="px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-brand-100 flex items-center justify-center text-brand-700 text-sm font-bold flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                </div>
            </div>
        </td>
        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ ucfirst(str_replace('-', ' ', (string) $accountType)) }}</td>
        <td class="px-6 py-4">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $roleMap[$user->role] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($user->role) }}</span>
        </td>
        <td class="px-6 py-4">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusMap[$user->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($user->status) }}</span>
        </td>
        <td class="px-6 py-4 text-sm text-gray-600">{{ optional($user->created_at)->format('M d, Y') }}</td>
        <td class="px-6 py-4">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.users.show', $user) }}" title="View" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-brand-200 bg-brand-50 text-brand-700 transition hover:bg-brand-100">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </a>
                <a href="{{ route('admin.users.edit', $user) }}" title="Edit" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                @if($user->id !== auth()->id())
                    <button
                        type="button"
                        title="Delete"
                        @click="openDeleteModal(@js($user->name), @js(route('admin.users.destroy', $user)))"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-6 py-14 text-center">
            <div class="mx-auto max-w-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19a4 4 0 0 0-8 0m8 0h5m-5 0a4 4 0 0 1 8 0m-9-8a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm10 0a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-gray-900">No users match these filters</h3>
                <p class="mt-1 text-sm text-gray-500">Try broadening one or more filters, then refresh the result set.</p>
            </div>
        </td>
    </tr>
@endforelse
