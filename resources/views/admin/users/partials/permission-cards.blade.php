@php
    $permissions = $permissions ?? [];
    $selectedPermissions = $selectedPermissions ?? [];
    $permissionDescriptions = $permissionDescriptions ?? [];
    $inputName = $inputName ?? 'direct_permissions[]';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1">
    @foreach($permissions as $permission)
        <label class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
            <span class="inline-flex items-start gap-2">
                <input
                    type="checkbox"
                    name="{{ $inputName }}"
                    value="{{ $permission }}"
                    @checked(in_array($permission, $selectedPermissions, true))
                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/40 mt-0.5"
                >
                <span>
                    <span class="font-semibold block">{{ $permission }}</span>
                    @if(!empty($permissionDescriptions[$permission] ?? null))
                        <span class="text-[11px] text-gray-500">{{ $permissionDescriptions[$permission] }}</span>
                    @endif
                </span>
            </span>
        </label>
    @endforeach
</div>
