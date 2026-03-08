<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            {{-- Small logo top-left --}}
            <div class="absolute top-8 left-8">
                <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="h-10 w-auto opacity-80">
            </div>
            {{-- Icon bubble --}}
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-8 shadow-lg">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            {{-- Headline --}}
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Guide their journey</h2>
            {{-- Sub-text --}}
            <p class="text-white/80 text-lg max-w-xs">Create a parent account to support your child's learning</p>
        </div>
    </x-slot>

    <x-wizard-stepper />

    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Create Parent Account</h2>
        <p class="mt-1 text-sm text-gray-600">Register as a parent to manage your child's learning</p>
    </div>

    <!-- Purple Info Banner -->
    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-6">
        <p class="text-sm text-purple-800">
            <strong>Parent accounts allow you to:</strong> Create and manage accounts for children under 13, 
            monitor their learning progress, view quiz results, and approve content.
        </p>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Registration Form -->
    <form method="POST" action="{{ route('parent.register.store') }}" 
          x-data="{
              birthdate: '',
              age: null,
              calculateAge() {
                  if (!this.birthdate) {
                      this.age = null;
                      return;
                  }
                  const today = new Date();
                  const birth = new Date(this.birthdate);
                  let age = today.getFullYear() - birth.getFullYear();
                  const monthDiff = today.getMonth() - birth.getMonth();
                  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                      age--;
                  }
                  this.age = age;
              }
          }">
                    @csrf

                    <!-- Personal Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-brand-purple-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            Personal Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input id="first_name" name="first_name" type="text" required 
                                       value="{{ old('first_name') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
                                       placeholder="Juan">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Middle Initial -->
                            <div>
                                <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">
                                    Middle Initial
                                </label>
                                <input id="middle_initial" name="middle_initial" type="text" 
                                       value="{{ old('middle_initial') }}"
                                       maxlength="10"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
                                       placeholder="D.">
                                <p class="mt-1 text-xs text-gray-500">Optional - Your middle name or initial</p>
                                @error('middle_initial')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input id="last_name" name="last_name" type="text" required 
                                       value="{{ old('last_name') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
                                       placeholder="Cruz">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Suffix -->
                            <div>
                                <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">
                                    Suffix
                                </label>
                                <select id="suffix" name="suffix"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent">
                                    <option value="">Select Suffix (Optional)</option>
                                    <option value="Jr." {{ old('suffix') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                                    <option value="Sr." {{ old('suffix') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                                    <option value="II" {{ old('suffix') == 'II' ? 'selected' : '' }}>II</option>
                                    <option value="III" {{ old('suffix') == 'III' ? 'selected' : '' }}>III</option>
                                    <option value="IV" {{ old('suffix') == 'IV' ? 'selected' : '' }}>IV</option>
                                    <option value="V" {{ old('suffix') == 'V' ? 'selected' : '' }}>V</option>
                                </select>
                                @error('suffix')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Birthdate -->
                        <div class="mt-4">
                            <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">
                                Birthdate <span class="text-red-500">*</span>
                            </label>
                            <input id="birthdate" name="birthdate" type="date" required 
                                   value="{{ old('birthdate') }}"
                                   x-model="birthdate"
                                   @change="calculateAge()"
                                   min="{{ now()->subYears(100)->format('Y-m-d') }}"
                                   max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent">
                            
                            <!-- Age Display -->
                            <div x-show="age !== null" class="mt-2">
                                <template x-if="age >= 18">
                                    <div class="flex items-center text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                        <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>You are <strong x-text="age"></strong> years old - eligible to create parent account!</span>
                                    </div>
                                </template>
                                <template x-if="age < 18">
                                    <div class="flex items-center text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>You must be 18 or older to create a parent account.</span>
                                    </div>
                                </template>
                            </div>

                            @error('birthdate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-brand-purple-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            Account Information
                        </h3>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address <span class="text-red-500">*</span>
                                <span class="text-xs text-blue-600 font-normal">(Gmail only)</span>
                            </label>
                            <input id="email" name="email" type="email" required 
                                   value="{{ old('email') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
                                   placeholder="parent@gmail.com">
                            <p class="mt-1 text-xs text-gray-500">Must be a Gmail address. We'll send a verification email.</p>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input id="password" name="password" type="password" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
                                       placeholder="••••••••">
                                <p class="mt-1 text-xs text-gray-500">Min. 8 characters, mixed case, numbers & symbols</p>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
                                       placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Privacy -->
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <label class="flex items-start">
                            <input type="checkbox" required
                                   class="mt-1 h-4 w-4 text-brand-purple-primary focus:ring-brand-purple-primary border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">
                                I agree to the 
                                <a href="{{ route('terms') }}" target="_blank" class="text-brand-purple-primary hover:text-brand-purple-light underline">Terms of Service</a> 
                                and 
                                <a href="{{ route('privacy') }}" target="_blank" class="text-brand-purple-primary hover:text-brand-purple-light underline">Privacy Policy</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" 
                                class="w-full bg-brand-purple-primary text-white font-semibold py-3 px-4 rounded-xl hover:bg-brand-purple-light focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:ring-offset-2 transition duration-150 shadow-lg">
                            Create Parent Account
                        </button>
                    </div>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Already have a parent account? 
                        <a href="{{ route('login') }}" class="text-brand-purple-primary hover:text-brand-purple-light font-medium">Login here</a>
                    </p>
                    <p class="text-sm text-gray-600 mt-2">
                        Not a parent? 
                        <a href="{{ route('register') }}" class="text-brand-purple-primary hover:text-brand-purple-light font-medium">Register as learner</a>
                    </p>
                </div>

</x-auth-split-layout>
