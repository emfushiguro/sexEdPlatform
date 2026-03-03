<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Modules']
        ]" />
        
        <div class="flex justify-between items-center mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('instructor.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Module Management') }}
                </h2>
            </div>
            <a href="{{ route('instructor.modules.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create New Module
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php
                $pendingCount = \App\Models\ModuleEnrollment::pending()->count();
            @endphp
            @if($pendingCount > 0)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm text-yellow-700">
                                <span class="font-medium">{{ $pendingCount }}</span> enrollment {{ $pendingCount === 1 ? 'request' : 'requests' }} waiting for your review
                            </p>
                        </div>
                        <a href="{{ route('instructor.enrollments.index') }}" class="text-sm font-medium text-yellow-700 hover:text-yellow-800">
                            Review Now →
                        </a>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration (min)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lessons</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quizzes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($modules as $module)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $module->title }}</div>
                                    <div class="text-sm text-gray-500">{{ Str::limit($module->description, 50) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $module->duration_minutes }} min</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $module->lessons_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $module->quizzes_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $module->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $module->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('instructor.modules.show', $module) }}" class="inline-block px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 mr-2">View</a>
                                    <a href="{{ route('instructor.modules.edit', $module) }}" class="inline-block px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 mr-2">Edit</a>
                                    <form action="{{ route('instructor.modules.destroy', $module) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700" onclick="return confirm('Delete this module and all its lessons?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No modules found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $modules->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
