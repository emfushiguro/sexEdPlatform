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

                        <!-- Birthdate -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Birthdate *</label>
                            <input type="date" name="birthdate" value="{{ old('birthdate', $learnerProfile?->birthdate?->format('Y-m-d')) }}" required
                                max="{{ date('Y-m-d', strtotime('-5 years')) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">You must be at least 5 years old to use the platform</p>
                            @error('birthdate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
                            <select name="city_code" id="city_code" required 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select your municipality/city</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->code }}" 
                                        {{ old('city_code', $learnerProfile?->city_code) === $city->code ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Barangay -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Barangay *</label>
                            <select name="barangay_code" id="barangay_code" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select municipality first</option>
                                @if(old('barangay_code', $learnerProfile?->barangay_code))
                                    @foreach($barangays ?? [] as $barangay)
                                        <option value="{{ $barangay->code }}"
                                            {{ old('barangay_code', $learnerProfile?->barangay_code) === $barangay->code ? 'selected' : '' }}>
                                            {{ $barangay->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('barangay_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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

                    <!-- Dynamic Barangay Loading Script -->
                    <script>
                        document.getElementById('city_code').addEventListener('change', function() {
                            const cityCode = this.value;
                            const barangaySelect = document.getElementById('barangay_code');
                            
                            if (!cityCode) {
                                barangaySelect.innerHTML = '<option value="">Select municipality first</option>';
                                return;
                            }
                            
                            barangaySelect.innerHTML = '<option value="">Loading barangays...</option>';
                            
                            fetch(`/api/barangays/${cityCode}`)
                                .then(response => response.json())
                                .then(data => {
                                    barangaySelect.innerHTML = '<option value="">Select your barangay</option>';
                                    data.forEach(barangay => {
                                        const option = document.createElement('option');
                                        option.value = barangay.code;
                                        option.textContent = barangay.name;
                                        barangaySelect.appendChild(option);
                                    });
                                })
                                .catch(error => {
                                    console.error('Error loading barangays:', error);
                                    barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
                                });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
