<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Enrollment Requests', 'url' => route('instructor.enrollments.index')],
            ['label' => 'Review Learner']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('instructor.enrollments.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review Enrollment Request</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content - Learner Profile -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Learner Information
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Full Name</label>
                                    <p class="text-gray-900">{{ $enrollment->user->name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Username</label>
                                    <p class="text-gray-900">
                                        @if($enrollment->user->learnerProfile)
                                            {{ $enrollment->user->learnerProfile->username }}
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email</label>
                                    <p class="text-gray-900">{{ $enrollment->user->email }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Age</label>
                                    <p class="text-gray-900">
                                        @if($enrollment->user->learnerProfile)
                                            {{ $enrollment->user->learnerProfile->getAge() }} years old
                                            @php
                                                $age = $enrollment->user->learnerProfile->getAge();
                                                $moduleMinAge = $enrollment->module->min_age;
                                                $moduleMaxAge = $enrollment->module->max_age;
                                                $isAgeAppropriate = $age >= $moduleMinAge && $age <= $moduleMaxAge;
                                            @endphp
                                            @if($isAgeAppropriate)
                                                <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">✓ Age-appropriate</span>
                                            @else
                                                <span class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-1 rounded">⚠ Outside target age range</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Gender</label>
                                    <p class="text-gray-900">
                                        @if($enrollment->user->learnerProfile)
                                            {{ ucfirst(str_replace('_', ' ', $enrollment->user->learnerProfile->gender)) }}
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Account Created</label>
                                    <p class="text-gray-900">{{ $enrollment->user->created_at->format('M d, Y') }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-sm font-medium text-gray-500">Location</label>
                                    <p class="text-gray-900">
                                        @if($enrollment->user->learnerProfile)
                                            @php
                                                $profile = $enrollment->user->learnerProfile;
                                                $barangayName = '';
                                                $cityName = '';
                                                
                                                // Get barangay name
                                                if ($profile->barangay && is_object($profile->barangay)) {
                                                    $barangayName = $profile->barangay->name;
                                                } elseif (is_string($profile->barangay)) {
                                                    $barangayName = $profile->barangay;
                                                }
                                                
                                                // Get city name
                                                if ($profile->city) {
                                                    $cityName = $profile->city->name;
                                                }
                                            @endphp
                                            
                                            @if($barangayName && $cityName)
                                                {{ $barangayName }}, {{ $cityName }}
                                            @elseif($cityName)
                                                {{ $cityName }}
                                            @else
                                                -
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Module Requested -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                Module Requested
                            </h3>

                            <div class="flex gap-4">
                                @if($enrollment->module->thumbnail)
                                    <img src="{{ asset('storage/' . $enrollment->module->thumbnail) }}" 
                                         alt="{{ $enrollment->module->title }}" 
                                         class="w-24 h-24 object-cover rounded">
                                @endif
                                <div class="flex-1">
                                    <h4 class="font-semibold text-lg">{{ $enrollment->module->title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ Str::limit($enrollment->module->description, 150) }}</p>
                                    <div class="flex gap-3 mt-2 text-xs text-gray-500">
                                        <span>Age: {{ $enrollment->module->min_age }}-{{ $enrollment->module->max_age }} years</span>
                                        <span>•</span>
                                        <span>{{ $enrollment->module->lessons_count ?? 0 }} lessons</span>
                                        <span>•</span>
                                        <span>Requested {{ $enrollment->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Learning History -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Recent Learning Activity
                            </h3>

                            @if($recentEnrollments->count() > 0)
                                <div class="space-y-3">
                                    @foreach($recentEnrollments as $recent)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <div>
                                                <p class="font-medium text-sm">{{ $recent->module->title }}</p>
                                                <p class="text-xs text-gray-500">
                                                    Enrolled {{ $recent->enrolled_at?->diffForHumans() ?? $recent->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                @if($recent->completed_at)
                                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Completed</span>
                                                @else
                                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $recent->completion_percentage }}% Progress</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 text-center py-4">No previous enrollments</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Stats & Actions -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Learner Statistics</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm text-gray-500">Total Enrollments</label>
                                    <p class="text-2xl font-bold text-gray-900">{{ $totalEnrollments }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500">Completed Modules</label>
                                    <p class="text-2xl font-bold text-green-600">{{ $completedModules }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500">Completion Rate</label>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $completionRate }}%"></div>
                                        </div>
                                        <span class="text-sm font-semibold">{{ $completionRate }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    @if($enrollment->status === \App\Enums\EnrollmentStatus::Pending)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-4">Review Decision</h3>
                                
                                <div class="space-y-3">
                                    <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition"
                                                onclick="return confirm('Approve this enrollment request?')">
                                            ✓ Approve Enrollment
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition"
                                                onclick="return confirm('Reject this enrollment request? The learner will be notified.')">
                                            ✗ Reject Request
                                        </button>
                                    </form>

                                    <a href="{{ route('instructor.enrollments.index') }}" 
                                       class="block w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-lg transition">
                                        ← Back to Requests
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-center">
                                    @if($enrollment->status === \App\Enums\EnrollmentStatus::Approved)
                                        <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                                            <svg class="w-12 h-12 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-green-800 font-semibold">Already Approved</p>
                                            <p class="text-xs text-green-600 mt-1">{{ $enrollment->enrolled_at->format('M d, Y') }}</p>
                                        </div>
                                    @else
                                        <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4">
                                            <svg class="w-12 h-12 text-red-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-red-800 font-semibold">Request Rejected</p>
                                        </div>
                                    @endif

                                    <a href="{{ route('instructor.enrollments.index') }}" 
                                       class="block w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-lg transition mt-4">
                                        ← Back to Requests
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
