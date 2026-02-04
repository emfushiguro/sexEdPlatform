<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Modules', 'url' => route('admin.modules.index')],
            ['label' => 'Edit']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('admin.modules.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Module</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.modules.update', $module) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" value="{{ old('title', $module->title) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="3" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $module->description) }}</textarea>
                            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Thumbnail Image</label>
                            @if($module->thumbnail)
                                <img src="{{ asset('storage/' . $module->thumbnail) }}" alt="Current thumbnail" class="mb-2 h-32 rounded">
                            @endif
                            <input type="file" name="thumbnail" accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">Leave empty to keep current image</p>
                            @error('thumbnail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        @php
                            // Determine current age bracket
                            $currentBracket = 'kids';
                            if ($module->min_age === 13 && $module->max_age === 17) {
                                $currentBracket = 'teens';
                            } elseif ($module->min_age === 18) {
                                $currentBracket = 'adults';
                            }
                        @endphp

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Age Bracket <span class="text-red-500">*</span></label>
                            <select name="age_bracket" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select target age group</option>
                                <option value="kids" {{ old('age_bracket', $currentBracket) === 'kids' ? 'selected' : '' }}>Kids (5-12 years)</option>
                                <option value="teens" {{ old('age_bracket', $currentBracket) === 'teens' ? 'selected' : '' }}>Teens (13-17 years)</option>
                                <option value="adults" {{ old('age_bracket', $currentBracket) === 'adults' ? 'selected' : '' }}>Adults (18+ years)</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select the age group this module is designed for</p>
                            @error('age_bracket')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Duration</label>
                            <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-sm text-gray-600">
                                {{ $module->lessons()->sum('duration') ?? 0 }} minutes (auto-calculated from lessons)
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Duration is automatically calculated based on lesson durations</p>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $module->is_published) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm font-medium text-gray-700">Published</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">Check to make this module visible to learners</p>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('admin.modules.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Module</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
