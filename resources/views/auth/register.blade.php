<x-auth-split-layout
    :showTabs="true"
    activeTab="register"
    :loginRoute="route('learner.login')"
    :registerRoute="route('register')"
>
    <x-slot name="panel">
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            {{-- Small logo top-left --}}
            <div class="absolute top-8 left-8">
                <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="h-10 w-auto opacity-80">
            </div>
            {{-- Icon bubble --}}
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-8 shadow-lg">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                </svg>
            </div>
            {{-- Headline --}}
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Start your learning journey</h2>
            {{-- Sub-text --}}
            <p class="text-white/80 text-lg max-w-xs">A safe, age-appropriate space to grow</p>
        </div>
    </x-slot>

    <x-wizard-stepper />

    <!-- Heading -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-purple-900">Create your account</h2>
        <p class="mt-1 text-sm text-gray-500">Step 1 of 2 — Personal Information</p>
    </div>

    <!-- Registration Form -->
                <form method="POST" action="{{ route('register') }}" x-data="{
                    birthdate: '{{ old('birthdate') }}',
                    age: null,
                    loading: false,
                    calculateAge() {
                        if (this.birthdate) {
                            const today = new Date();
                            const birth = new Date(this.birthdate);
                            let age = today.getFullYear() - birth.getFullYear();
                            const monthDiff = today.getMonth() - birth.getMonth();
                            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                                age--;
                            }
                            this.age = age > 0 && age < 120 ? age : null;
                        } else {
                            this.age = null;
                        }
                    }
                }" x-init="calculateAge()" @submit="loading = true">
                    @csrf

                    <div class="space-y-4">

                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input 
                                id="first_name" 
                                type="text" 
                                name="first_name" 
                                value="{{ old('first_name') }}"
                                required 
                                autofocus 
                                autocomplete="given-name"
                                placeholder="Juan"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                            />
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Middle Initial (Optional) -->
                        <div>
                            <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">Middle Initial <span class="text-gray-400 font-normal">(Optional)</span></label>
                            <input 
                                id="middle_initial" 
                                type="text" 
                                name="middle_initial" 
                                value="{{ old('middle_initial') }}"
                                maxlength="10"
                                autocomplete="additional-name"
                                placeholder="D."
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                            />
                            <p class="mt-1 text-xs text-gray-500">Example: D. or De la</p>
                            @error('middle_initial')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input 
                                id="last_name" 
                                type="text" 
                                name="last_name" 
                                value="{{ old('last_name') }}"
                                required 
                                autocomplete="family-name"
                                placeholder="dela Cruz"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                            />
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Suffix (Optional) -->
                        <div>
                            <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix <span class="text-gray-400 font-normal">(Optional)</span></label>
                            <select 
                                id="suffix" 
                                name="suffix"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                            >
                                <option value="">-- None --</option>
                                <option value="Jr." {{ old('suffix') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                                <option value="Sr." {{ old('suffix') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                                <option value="II"  {{ old('suffix') == 'II'  ? 'selected' : '' }}>II</option>
                                <option value="III" {{ old('suffix') == 'III' ? 'selected' : '' }}>III</option>
                                <option value="IV"  {{ old('suffix') == 'IV'  ? 'selected' : '' }}>IV</option>
                                <option value="V"   {{ old('suffix') == 'V'   ? 'selected' : '' }}>V</option>
                            </select>
                            @error('suffix')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Birth Date -->
                        <div>
                            <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">Birth Date</label>
                            <input 
                                type="date" 
                                id="birthdate" 
                                name="birthdate" 
                                x-model="birthdate"
                                @change="calculateAge()"
                                value="{{ old('birthdate') }}"
                                required 
                                min="{{ now()->subYears(100)->format('Y-m-d') }}"
                                max="{{ now()->subYears(5)->format('Y-m-d') }}"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                            />
                            
                            <!-- Age Display -->
                            <div x-show="age !== null" class="mt-2">
                                <div x-show="age >= 13" class="flex items-center text-sm text-green-600">
                                    <svg class="w-5 h-5 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>You are <strong x-text="age"></strong> years old — eligible to register!</span>
                                </div>
                                <div x-show="age < 13" class="flex items-center text-sm text-orange-600">
                                    <svg class="w-5 h-5 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>You are <strong x-text="age"></strong> years old — a parent/guardian must register for you</span>
                                </div>
                            </div>

                            <p class="mt-1 text-xs text-gray-500">You must be at least 5 years old to use this platform</p>
                            @error('birthdate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Next Button -->
                        <div class="pt-2">
                            <button 
                                type="submit"
                                :disabled="loading"
                                class="w-full bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-base hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <span x-show="!loading">Next →</span>
                                <span x-show="loading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Checking...</span>
                                </span>
                            </button>

                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-600">
                                    Already registered? 
                                    <a href="{{ route('learner.login') }}" class="text-brand-purple-primary font-medium hover:text-brand-purple-dark transition-colors">Login</a>
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Links -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                            <a href="#" class="hover:text-brand-purple-primary transition-colors">Help</a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('terms') }}" class="hover:text-brand-purple-primary transition-colors">Terms</a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('privacy') }}" class="hover:text-brand-purple-primary transition-colors">Privacy</a>
                        </div>
                    </div>
                </form>

</x-auth-split-layout>
