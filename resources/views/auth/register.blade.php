<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Register</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">

            <!-- Form Content -->
            <div class="p-8 sm:p-10 lg:p-12">
                
                <!-- Heading -->
                <div class="mb-6">
                    <h2 class="text-4xl font-bold text-purple-900 flex items-center gap-2">
                        Create your account
                        <img src="{{ asset('/media/Logo.png') }}" alt="Taboo" class="w-28 h-24 object-contain inline-block" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E♂️%3C/text%3E%3C/svg%3E%27">
                    </h2>
                </div>

                <!-- Registration Form -->
                <form method="POST" action="{{ route('register') }}" x-data="{
                    birthdate: '{{ old('birthdate') }}',
                    age: null,
                    showPassword: false,
                    showConfirmPassword: false,
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

                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        
                        <!-- Left Column: Personal Information -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Personal Information</h3>

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
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Middle Initial (Optional) -->
                            <div>
                                <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">Middle Initial (Optional)</label>
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
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
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
                                    placeholder="Juan"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                                />
                                @error('last_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Suffix (Optional) -->
                            <div>
                                <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix (Optional)</label>
                                <select 
                                    id="suffix" 
                                    name="suffix"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                                >
                                    <option value="">-- None --</option>
                                    <option value="Jr." {{ old('suffix') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                                    <option value="Sr." {{ old('suffix') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                                    <option value="II" {{ old('suffix') == 'II' ? 'selected' : '' }}>II</option>
                                    <option value="III" {{ old('suffix') == 'III' ? 'selected' : '' }}>III</option>
                                    <option value="IV" {{ old('suffix') == 'IV' ? 'selected' : '' }}>IV</option>
                                    <option value="V" {{ old('suffix') == 'V' ? 'selected' : '' }}>V</option>
                                </select>
                                @error('suffix')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
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
                                        <span>You are <strong x-text="age"></strong> years old - eligible to register!</span>
                                    </div>
                                    <div x-show="age < 13" class="flex items-center text-sm text-orange-600">
                                        <svg class="w-5 h-5 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>You are <strong x-text="age"></strong> years old - a parent/guardian must register for you</span>
                                    </div>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">You must be at least 5 years old to use this platform</p>
                                @error('birthdate')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Right Column: Account Information -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Account Information</h3>

                            <!-- Email Address -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (Gmail only)</label>
                                <input 
                                    id="email" 
                                    type="email" 
                                    name="email" 
                                    value="{{ old('email') }}"
                                    required 
                                    autocomplete="username"
                                    placeholder="Juan"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                                />
                                <p class="mt-1 text-xs text-gray-500">We'll send a verification link to this email</p>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <div class="relative">
                                    <input 
                                        id="password" 
                                        :type="showPassword ? 'text' : 'password'"
                                        name="password"
                                        required 
                                        autocomplete="new-password"
                                        class="w-full px-4 py-2.5 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                                    />
                                    <button 
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                                    >
                                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Min 8 chars with uppercase, lowercase, numbers & symbols
                                </p>
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <div class="relative">
                                    <input 
                                        id="password_confirmation" 
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        name="password_confirmation"
                                        required 
                                        autocomplete="new-password"
                                        class="w-full px-4 py-2.5 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                                    />
                                    <button 
                                        type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                                    >
                                        <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Register Button -->
                            <div class="mt-4">
                                <button 
                                type="submit"
                                class="w-full bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-base hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                >
                                <span x-show="!loading">Register</span>
                                <span x-show="loading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                <span>Creating account...</span>
                            </span>
                            </button>

                             <!-- Already Registered -->
                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-600">
                                    Already registered? 
                                <a href="{{ route('login') }}" class="text-brand-purple-primary font-medium hover:text-brand-purple-dark transition-colors">Login</a>
                                </p>
                            </div>
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

            </div>
        </div>
    </div>
</body>
</html>
