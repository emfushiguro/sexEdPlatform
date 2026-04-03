@extends('layouts.admin')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Admin Modules</h1>
            <p class="text-sm text-gray-500">Platform-owned modules authored and published by admins.</p>
        </div>
        <a href="{{ route('admin.modules.create') }}" class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">
            Create Module
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($modules as $module)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $module->title }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $module->current_review_status ?? 'approved' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.modules.show', $module) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-sm text-gray-500">No admin modules yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
