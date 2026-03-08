<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Children - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="max-w-5xl mx-auto mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Children</h1>
                    <p class="mt-2 text-sm text-gray-600">Manage and monitor your children's learning accounts</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('parent.create-child') }}" 
                       class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150">
                        + Add Another Child
                    </a>
                </div>
            </div>
        </div>

        <!-- Parent Info Card -->
        <div class="max-w-5xl mx-auto mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ auth()->user()->full_name }}</h3>
                            <p class="text-sm text-gray-600">Parent Account • {{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total Children</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $children->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Children List -->
        <div class="max-w-5xl mx-auto">
            @if($children->isEmpty())
                <!-- Empty State -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Children Added Yet</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        Get started by creating a learning account for your child. You'll be able to monitor their progress 
                        and view their quiz results.
                    </p>
                    <a href="{{ route('parent.create-child') }}" 
                       class="inline-flex items-center bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create First Child Account
                    </a>
                </div>
            @else
                <!-- Children Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($children as $child)
                        <div class="bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-200">
                            <!-- Card Header -->
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start">
                                        <!-- Avatar -->
                                        <div class="flex-shrink-0">
                                            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xl font-bold">
                                                    {{ strtoupper(substr($child->first_name, 0, 1)) }}{{ strtoupper(substr($child->last_name, 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Child Info -->
                                        <div class="ml-4">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $child->full_name }}</h3>
                                            <p class="text-sm text-gray-600">{{ $child->age }} years old</p>
                                            @if($child->userProfile?->username)
                                                <p class="text-xs text-gray-500 mt-1">@{{ $child->userProfile->username }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- Status Badge -->
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                        {{ $child->learnerProfile?->requires_parental_consent ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $child->learnerProfile?->requires_parental_consent ? 'Under 13' : 'Teen' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Stats Section -->
                            <div class="px-6 py-4 bg-gray-50">
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <!-- Modules Enrolled -->
                                    <div>
                                        <p class="text-2xl font-bold text-blue-600">{{ $child->moduleEnrollments()->count() }}</p>
                                        <p class="text-xs text-gray-600 mt-1">Modules</p>
                                    </div>
                                    <!-- Quiz Attempts -->
                                    <div>
                                        <p class="text-2xl font-bold text-green-600">{{ $child->quizAttempts()->count() }}</p>
                                        <p class="text-xs text-gray-600 mt-1">Quizzes</p>
                                    </div>
                                    <!-- Achievements -->
                                    <div>
                                        <p class="text-2xl font-bold text-purple-600">{{ $child->achievements()->count() }}</p>
                                        <p class="text-xs text-gray-600 mt-1">Achievements</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar (if has enrollments) -->
                            @if($child->moduleEnrollments()->count() > 0)
                                @php
                                    $totalEnrollments = $child->moduleEnrollments()->count();
                                    $completedModules = $child->moduleEnrollments()->whereNotNull('completed_at')->count();
                                    $progressPercent = $totalEnrollments > 0 ? round(($completedModules / $totalEnrollments) * 100) : 0;
                                @endphp
                                <div class="px-6 py-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $progressPercent }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                                             style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- Activity Info -->
                            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                                <div class="flex items-center text-xs text-gray-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Account created {{ $child->created_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <!-- View Progress Button -->
                                <button 
                                    onclick="alert('Progress view coming soon! You\'ll be able to see detailed learning metrics here.')"
                                    class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    View Progress
                                </button>

                                <!-- View Quiz Answers Button -->
                                @if($child->quizAttempts()->count() > 0)
                                    <button 
                                        onclick="alert('Quiz results view coming soon! You\'ll be able to review all quiz attempts and answers.')"
                                        class="text-sm text-green-600 hover:text-green-700 font-medium flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Quiz Results
                                    </button>
                                @endif

                                <!-- Manage Button -->
                                <button 
                                    onclick="alert('Account management coming soon! You\'ll be able to edit child info, reset password, and manage permissions.')"
                                    class="text-sm text-gray-600 hover:text-gray-700 font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Manage
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Help Section -->
        <div class="max-w-5xl mx-auto mt-8">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-semibold text-blue-900 mb-2">Parent Monitoring Features</h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• <strong>View Progress:</strong> Track module completions, lesson views, and learning milestones</li>
                            <li>• <strong>Quiz Results:</strong> Review quiz attempts, selected answers, and scores</li>
                            <li>• <strong>Achievements:</strong> See badges, certificates, and rewards earned</li>
                            <li>• <strong>Activity Logs:</strong> Monitor login times and content access (coming soon)</li>
                            <li>• <strong>Content Approval:</strong> Set permissions for accessing modules (coming soon)</li>
                        </ul>
                        <p class="text-xs text-blue-700 mt-3">
                            Need help? Contact support at support@example.com
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
