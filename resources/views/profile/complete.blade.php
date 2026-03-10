<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center overflow-hidden">
            <div class="absolute top-0 left-0 w-40 h-40 bg-white/5 rounded-br-full"></div>
            <div class="absolute bottom-0 right-0 w-56 h-56 bg-white/5 rounded-tl-full"></div>

            <div class="relative mb-6 z-10">
                <div class="w-32 h-32 bg-white/15 rounded-3xl flex items-center justify-center shadow-2xl backdrop-blur-sm border border-white/20">
                    <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="w-24 h-24 object-contain drop-shadow-lg">
                </div>
            </div>

            <h1 class="text-2xl font-bold text-white mb-1 tracking-wide z-10">Concious Connections</h1>
            <div class="w-12 h-0.5 bg-white/40 rounded-full mx-auto mb-6 z-10"></div>

            <h2 class="text-3xl font-bold text-white mb-3 leading-tight z-10">One last step!</h2>
            <p class="text-white/75 text-base max-w-[200px] leading-relaxed z-10">Personalize your learning experience</p>
        </div>
    </x-slot>

    <x-wizard-stepper />

    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Welcome, {{ Auth::user()->full_name }}!</h3>
        <p class="mt-2 text-sm text-gray-600">Just a couple more details to start learning. All fields marked with * are required.</p>
        
        @if(Auth::user()->email_verified_at)
            <div class="mt-3 bg-green-50 border-l-4 border-green-500 p-3">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800">
                            <strong>Email verified:</strong> {{ Auth::user()->email }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('profile.store') }}">
        @csrf

        <!-- Username -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Username (Display Name) *</label>
            <input type="text" name="username" value="{{ old('username', $learnerProfile?->username) }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-purple-primary focus:ring-brand-purple-primary"
                placeholder="e.g., cool_learner_123" pattern="[a-z0-9_-]+" maxlength="30">
            <p class="mt-1 text-xs text-gray-500">3-30 characters: lowercase letters, numbers, underscores, and hyphens only</p>
            @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <!-- Gender -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Gender</label>
            <select name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-purple-primary focus:ring-brand-purple-primary">
                <option value="">Select (optional)</option>
                <option value="male" {{ old('gender', $learnerProfile?->gender) === 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $learnerProfile?->gender) === 'female' ? 'selected' : '' }}>Female</option>
                <option value="prefer_not_to_say" {{ old('gender', $learnerProfile?->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
            </select>
            @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <!-- Municipality/City (Cavite only) -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Municipality/City (Cavite) *</label>
            <select name="city_code" id="city_code" required 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-purple-primary focus:ring-brand-purple-primary">
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
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-purple-primary focus:ring-brand-purple-primary">
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
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-purple-primary focus:ring-brand-purple-primary"
                placeholder="Tell us a bit about yourself...">{{ old('bio', $learnerProfile?->bio) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Maximum 500 characters</p>
            @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="bg-brand-purple-primary hover:bg-brand-purple-light text-white font-bold py-2 px-6 rounded-xl shadow-lg transition-all duration-200">
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
</x-auth-split-layout>
