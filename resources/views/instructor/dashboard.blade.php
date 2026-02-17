<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Total Learners -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Learners</p>
                                <p class="text-3xl font-bold text-blue-600">{{ $stats['total_learners'] }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Users -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Premium Users</p>
                                <p class="text-3xl font-bold text-purple-600">{{ $stats['premium_users'] }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Published Modules -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Published Modules</p>
                                <p class="text-3xl font-bold text-green-600">{{ $stats['published_modules'] }}/{{ $stats['total_modules'] }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Quizzes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Quizzes</p>
                                <p class="text-3xl font-bold text-orange-600">{{ $stats['total_quizzes'] }}</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Enrollment Requests -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition cursor-pointer"
                     onclick="window.location='{{ route('instructor.enrollments.index') }}'">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Pending Requests</p>
                                <p class="text-3xl font-bold text-red-600">{{ $stats['pending_enrollments'] }}</p>
                                @if($stats['pending_enrollments'] > 0)
                                    <p class="text-xs text-red-500 mt-1">Needs review →</p>
                                @endif
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificates Issued -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Certificates Issued</p>
                                <p class="text-3xl font-bold text-yellow-600">{{ $stats['total_certificates'] }}</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Modules by Enrollment -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Modules by Enrollment</h3>
                        <div class="space-y-3">
                            @forelse($moduleStats as $module)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $module->title }}</p>
                                        <p class="text-xs text-gray-600">{{ $module->grade_level ?? 'All levels' }}</p>
                                    </div>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                        {{ $module->enrollments_count }} enrolled
                                    </span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No modules yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Recent Certificates -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Certificates</h3>
                        <div class="space-y-3">
                            @forelse($recentCertificates as $cert)
                                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-lg">🏆</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ $cert->user->learnerProfile->username ?? 'User' }}</p>
                                        <p class="text-sm text-gray-600 truncate">{{ $cert->module->title }}</p>
                                        <p class="text-xs text-gray-500">{{ $cert->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No certificates issued yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="{{ route('instructor.modules.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-lg text-center font-semibold transition">
                    Manage Modules
                </a>
                <a href="{{ route('instructor.lessons.index') }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-4 rounded-lg text-center font-semibold transition">
                    Manage Lessons
                </a>
                <a href="{{ route('instructor.quizzes.index') }}" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-4 rounded-lg text-center font-semibold transition">
                    Manage Quizzes
                </a>
                <a href="{{ route('instructor.users.index') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-4 rounded-lg text-center font-semibold transition">
                    Manage Users
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
