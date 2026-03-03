<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Enrollment Requests']
        ]" />
        
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-4">Pending Enrollment Requests</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($pendingEnrollments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Learner
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Module
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Requested
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($pendingEnrollments as $enrollment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $enrollment->user->name }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ $enrollment->user->email }}
                                                        </div>
                                                        @if($enrollment->user->learnerProfile)
                                                            <div class="text-xs text-gray-400">
                                                                {{ $enrollment->user->learnerProfile->username }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">{{ $enrollment->module->title }}</div>
                                                <div class="text-xs text-gray-500">
                                                    Age: {{ $enrollment->module->min_age }}-{{ $enrollment->module->max_age }} years
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $enrollment->created_at->diffForHumans() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-2">
                                                    <a href="{{ route('instructor.enrollments.show', $enrollment) }}" 
                                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition">
                                                        View Details
                                                    </a>
                                                    <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition"
                                                            onclick="return confirm('Approve this enrollment request?')">
                                                            Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition"
                                                            onclick="return confirm('Reject this enrollment request?')">
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $pendingEnrollments->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No pending requests</h3>
                            <p class="mt-1 text-sm text-gray-500">All enrollment requests have been processed.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
