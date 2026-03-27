@extends('layouts.admin')

@section('content')
    <div class="space-y-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pending Content Reviews</h1>
            <p class="text-sm text-gray-500">Instructor submissions waiting for admin decisions.</p>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($reviewRequests as $reviewRequest)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $reviewRequest->module->title }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $reviewRequest->status }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ optional($reviewRequest->submitted_at)->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.content-reviews.show', $reviewRequest) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-sm text-gray-500">No content reviews are waiting right now.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
