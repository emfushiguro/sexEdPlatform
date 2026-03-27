<x-auth-split-layout 
    logo="/media/Logo.png"
    gradientFrom="#0A205C"
    gradientTo="#354FAE"
>
    {{-- Session Status (hidden - using toasts instead) --}}
    <div style="display: none;">
        <x-auth-session-status class="mb-6" :status="session('status')" />
    </div>
    
        <!-- Heading -->
    <div class="mb-8">
        <h2 class="text-4xl font-bold flex items-center gap-2" style="color: #0A205C;">
            Educators
            <img src="{{ asset('/media/Logo.png') }}" class="w-28 h-24 object-contain inline-block" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E♂️%3C/text%3E%3C/svg%3E%27">
        </h2>
        <h2 class="text-4xl font-bold flex items-center gap-2" style="color: #0A205C;">Portal</h2>
    </div>

    <!-- Login Form -->
    <form method="POST" action="{{ route('instructor.login.submit') }}" x-data="{ 
        showPassword: false,
        loading: false 
    }" @submit="loading = true">
        @csrf

        <!-- Email Input -->
        <div class="mb-4">
            <label for="email" class="sr-only">Email</label>
            <div class="relative">
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus 
                    autocomplete="username"
                    placeholder="Email"
                    class="instructor-input w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200"
                />
            </div>
            {{-- Errors shown via toast notifications --}}
        </div>

        <!-- Password Input with Toggle -->
        <div class="mb-2">
            <label for="password" class="sr-only">Password</label>
            <div class="relative">
                <input 
                    id="password" 
                    :type="showPassword ? 'text' : 'password'"
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Password"
                    class="instructor-input w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200"
                />
                <!-- Eye Icon Toggle -->
                <button 
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Forgot Password Link -->
        <div class="text-right mb-6">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="instructor-link text-sm hover:underline transition-colors">
                    Forgot Password?
                </a>
            @endif
        </div>

        <!-- Remember Me Checkbox -->
        <div class="mb-6">
            <label class="flex items-center cursor-pointer group">
                <input 
                    type="checkbox" 
                    name="remember" 
                    class="instructor-checkbox w-4 h-4 border-gray-300 rounded cursor-pointer"
                />
                <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Remember me</span>
            </label>
        </div>

        <!-- Login Button -->
        <button 
            type="submit"
            :disabled="loading"
            class="instructor-button-gradient w-full py-3.5 px-4 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center"
        >
            <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="loading ? 'Logging in...' : 'Login'">Login</span>
        </button>

        <!-- Learner Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                or login as 
                <a href="{{ route('login') }}" class="instructor-link font-medium hover:underline transition-colors">
                    Learner
                </a>
            </p>
        </div>

        <!-- Footer Links -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                <a href="#" class="instructor-link hover:underline transition-colors">Help</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('terms') }}" class="instructor-link hover:underline transition-colors">Terms</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('privacy') }}" class="instructor-link hover:underline transition-colors">Privacy</a>
            </div>
        </div>
    </form>
</x-auth-split-layout>
