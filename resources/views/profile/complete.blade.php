<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Complete Your Profile</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Welcome, {{ Auth::user()->first_name }}!</h3>
                        <p class="mt-2 text-sm text-gray-600">Please complete your profile to start learning. All fields marked with * are required.</p>
                    </div>

                    <form method="POST" action="{{ route('profile.store') }}">
                        @csrf

                        <!-- Username -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Username (Display Name) *</label>
                            <input type="text" name="username" value="{{ old('username', $learnerProfile?->username) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="e.g., cool_learner_123" pattern="[a-z0-9_-]+" maxlength="30">
                            <p class="mt-1 text-xs text-gray-500">3-30 characters: lowercase letters, numbers, underscores, and hyphens only</p>
                            @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Age Range -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Grade Level *</label>
                            <select name="age_range" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select your grade level</option>
                                <option value="grade_4_up" {{ old('age_range', $learnerProfile?->age_range) === 'grade_4_up' ? 'selected' : '' }}>Grade 4 and Up</option>
                                <option value="grade_6_up" {{ old('age_range', $learnerProfile?->age_range) === 'grade_6_up' ? 'selected' : '' }}>Grade 6 and Up</option>
                                <option value="grade_8_up" {{ old('age_range', $learnerProfile?->age_range) === 'grade_8_up' ? 'selected' : '' }}>Grade 8 and Up</option>
                                <option value="grade_10_up" {{ old('age_range', $learnerProfile?->age_range) === 'grade_10_up' ? 'selected' : '' }}>Grade 10 and Up</option>
                                <option value="adult_18_plus" {{ old('age_range', $learnerProfile?->age_range) === 'adult_18_plus' ? 'selected' : '' }}>Adult (18+)</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">This helps us show you age-appropriate content</p>
                            @error('age_range')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Gender -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Gender</label>
                            <select name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select (optional)</option>
                                <option value="male" {{ old('gender', $learnerProfile?->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $learnerProfile?->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="prefer_not_to_say" {{ old('gender', $learnerProfile?->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                            </select>
                            @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Municipality -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Municipality/City (Cavite) *</label>
                            <select name="municipality" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select your municipality</option>
                                <option value="Alfonso" {{ old('municipality', $learnerProfile?->municipality) === 'Alfonso' ? 'selected' : '' }}>Alfonso</option>
                                <option value="Amadeo" {{ old('municipality', $learnerProfile?->municipality) === 'Amadeo' ? 'selected' : '' }}>Amadeo</option>
                                <option value="Bacoor" {{ old('municipality', $learnerProfile?->municipality) === 'Bacoor' ? 'selected' : '' }}>Bacoor</option>
                                <option value="Carmona" {{ old('municipality', $learnerProfile?->municipality) === 'Carmona' ? 'selected' : '' }}>Carmona</option>
                                <option value="Cavite City" {{ old('municipality', $learnerProfile?->municipality) === 'Cavite City' ? 'selected' : '' }}>Cavite City</option>
                                <option value="Dasmariñas" {{ old('municipality', $learnerProfile?->municipality) === 'Dasmariñas' ? 'selected' : '' }}>Dasmariñas</option>
                                <option value="General Emilio Aguinaldo" {{ old('municipality', $learnerProfile?->municipality) === 'General Emilio Aguinaldo' ? 'selected' : '' }}>General Emilio Aguinaldo</option>
                                <option value="General Mariano Alvarez" {{ old('municipality', $learnerProfile?->municipality) === 'General Mariano Alvarez' ? 'selected' : '' }}>General Mariano Alvarez (GMA)</option>
                                <option value="General Trias" {{ old('municipality', $learnerProfile?->municipality) === 'General Trias' ? 'selected' : '' }}>General Trias</option>
                                <option value="Imus" {{ old('municipality', $learnerProfile?->municipality) === 'Imus' ? 'selected' : '' }}>Imus</option>
                                <option value="Indang" {{ old('municipality', $learnerProfile?->municipality) === 'Indang' ? 'selected' : '' }}>Indang</option>
                                <option value="Kawit" {{ old('municipality', $learnerProfile?->municipality) === 'Kawit' ? 'selected' : '' }}>Kawit</option>
                                <option value="Magallanes" {{ old('municipality', $learnerProfile?->municipality) === 'Magallanes' ? 'selected' : '' }}>Magallanes</option>
                                <option value="Maragondon" {{ old('municipality', $learnerProfile?->municipality) === 'Maragondon' ? 'selected' : '' }}>Maragondon</option>
                                <option value="Mendez" {{ old('municipality', $learnerProfile?->municipality) === 'Mendez' ? 'selected' : '' }}>Mendez (Mendez-Nuñez)</option>
                                <option value="Naic" {{ old('municipality', $learnerProfile?->municipality) === 'Naic' ? 'selected' : '' }}>Naic</option>
                                <option value="Noveleta" {{ old('municipality', $learnerProfile?->municipality) === 'Noveleta' ? 'selected' : '' }}>Noveleta</option>
                                <option value="Rosario" {{ old('municipality', $learnerProfile?->municipality) === 'Rosario' ? 'selected' : '' }}>Rosario</option>
                                <option value="Silang" {{ old('municipality', $learnerProfile?->municipality) === 'Silang' ? 'selected' : '' }}>Silang</option>
                                <option value="Tagaytay" {{ old('municipality', $learnerProfile?->municipality) === 'Tagaytay' ? 'selected' : '' }}>Tagaytay</option>
                                <option value="Tanza" {{ old('municipality', $learnerProfile?->municipality) === 'Tanza' ? 'selected' : '' }}>Tanza</option>
                                <option value="Ternate" {{ old('municipality', $learnerProfile?->municipality) === 'Ternate' ? 'selected' : '' }}>Ternate</option>
                                <option value="Trece Martires" {{ old('municipality', $learnerProfile?->municipality) === 'Trece Martires' ? 'selected' : '' }}>Trece Martires</option>
                            </select>
                            @error('municipality')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Bio -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Bio (Optional)</label>
                            <textarea name="bio" rows="3" maxlength="500"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Tell us a bit about yourself...">{{ old('bio', $learnerProfile?->bio) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Maximum 500 characters</p>
                            @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                Complete Profile & Start Learning
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
