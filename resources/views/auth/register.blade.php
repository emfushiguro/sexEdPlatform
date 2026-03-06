<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Create Your Account</h2>
        <p class="mt-2 text-sm text-gray-600">Join our sex education platform</p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{
        birthdate: '{{ old('birthdate') }}',
        age: null,
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
    }" x-init="calculateAge()">
        @csrf

        <!-- Personal Information Section -->
        <div class="space-y-4">
            <div class="border-b border-gray-200 pb-2">
                <h3 class="text-sm font-semibold text-gray-700">Personal Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- First Name -->
                <div>
                    <x-input-label for="first_name" :value="__('First Name')" />
                    <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" placeholder="Juan" />
                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                </div>

                <!-- Middle Initial (Optional) -->
                <div>
                    <x-input-label for="middle_initial" value="Middle Initial (Optional)" />
                    <x-text-input id="middle_initial" class="block mt-1 w-full" type="text" name="middle_initial" :value="old('middle_initial')" maxlength="10" autocomplete="additional-name" placeholder="D." />
                    <p class="mt-1 text-xs text-gray-500">Example: D. or De la</p>
                    <x-input-error :messages="$errors->get('middle_initial')" class="mt-2" />
                </div>

                <!-- Last Name -->
                <div>
                    <x-input-label for="last_name" :value="__('Last Name')" />
                    <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" placeholder="Cruz" />
                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                </div>

                <!-- Suffix (Optional) -->
                <div>
                    <x-input-label for="suffix" value="Suffix (Optional)" />
                    <select id="suffix" name="suffix" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- None --</option>
                        <option value="Jr." {{ old('suffix') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                        <option value="Sr." {{ old('suffix') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                        <option value="II" {{ old('suffix') == 'II' ? 'selected' : '' }}>II</option>
                        <option value="III" {{ old('suffix') == 'III' ? 'selected' : '' }}>III</option>
                        <option value="IV" {{ old('suffix') == 'IV' ? 'selected' : '' }}>IV</option>
                        <option value="V" {{ old('suffix') == 'V' ? 'selected' : '' }}>V</option>
                    </select>
                    <x-input-error :messages="$errors->get('suffix')" class="mt-2" />
                </div>
            </div>

            <!-- Birthdate - Full Width -->
            <div>
                <x-input-label for="birthdate" :value="__('Birth Date')" />
                <input 
                    type="date" 
                    id="birthdate" 
                    name="birthdate" 
                    x-model="birthdate"
                    @change="calculateAge()"
                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                    :value="old('birthdate')" 
                    required 
                    min="{{ now()->subYears(100)->format('Y-m-d') }}"
                    max="{{ now()->subYears(5)->format('Y-m-d') }}" />
                
                <!-- Age Display -->
                <div x-show="age !== null" class="mt-2">
                    <div x-show="age >= 13" class="flex items-center text-sm text-green-600">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>You are <strong x-text="age"></strong> years old - eligible to register!</span>
                    </div>
                    <div x-show="age < 13" class="flex items-center text-sm text-orange-600">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>You are <strong x-text="age"></strong> years old - a parent/guardian must register for you</span>
                    </div>
                </div>

                <p class="mt-1 text-xs text-gray-500">You must be at least 5 years old to use this platform</p>
                <x-input-error :messages="$errors->get('birthdate')" class="mt-2" />
            </div>
        </div>

        <!-- Account Information Section -->
        <div class="mt-6 space-y-4">
            <div class="border-b border-gray-200 pb-2">
                <h3 class="text-sm font-semibold text-gray-700">Account Information</h3>
            </div>

            <!-- Email Address - Full Width -->
            <div>
                <x-input-label for="email" value="Email (Gmail only)" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="yourname@gmail.com" />
                <p class="mt-1 text-xs text-gray-500">We'll send a verification link to this email</p>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full"
                                    type="password"
                                    name="password"
                                    required autocomplete="new-password" />
                    <p class="mt-1 text-xs text-gray-500">
                        Min 8 chars with uppercase, lowercase, numbers & symbols
                    </p>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                    type="password"
                                    name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Terms and Privacy -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-xs text-gray-700">
                By creating an account, you agree to our 
                <a href="{{ route('terms') }}" class="text-indigo-600 hover:text-indigo-800 underline" target="_blank">Terms of Service</a> 
                and 
                <a href="{{ route('privacy') }}" class="text-indigo-600 hover:text-indigo-800 underline" target="_blank">Privacy Policy</a>.
            </p>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Create Account') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
