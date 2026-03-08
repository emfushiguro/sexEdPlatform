<x-auth-split-layout
    logo="/media/Logo.png"
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
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Welcome back</h2>
            {{-- Sub-text --}}
            <p class="text-white/80 text-lg max-w-xs">Continue your learning journey</p>
        </div>
    </x-slot>

    {{-- Session Status (hidden - using toasts instead) --}}
    <div style="display: none;">
        <x-auth-session-status class="mb-6" :status="session('status')" />
    </div>

    <!-- Heading -->
    <div class="mb-8">
        <h2 class="text-4xl font-bold text-purple-900 flex items-center gap-2">
            Login to
            <img src="{{ asset('/media/Logo.png') }}" alt="Taboo" class="w-28 h-24 object-contain inline-block" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E♂️%3C/text%3E%3C/svg%3E%27">
        </h2>
        <h2 class="text-4xl font-bold text-purple-900 flex items-center gap-2">your account</h2>
    </div>

    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}" x-data="{ 
        showPassword: false,
        loading: false 
    }" @submit="loading = true">
        @csrf

        <!-- Email/Username Input -->
        <div class="mb-4">
            <label for="email" class="sr-only">Email or Username</label>
            <div class="relative">
                <input 
                    id="email" 
                    type="text" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus 
                    autocomplete="username"
                    placeholder="Email or username"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
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
                    class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
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
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Forgot Password Link -->
        <div class="text-right mb-6">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-gray-500 hover:text-brand-purple-primary transition-colors">
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
                    class="w-4 h-4 text-brand-purple-primary border-gray-300 rounded focus:ring-brand-purple-primary focus:ring-2 cursor-pointer"
                />
                <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Remember me</span>
            </label>
        </div>

        <!-- Login Button -->
        <button 
            type="submit"
            :disabled="loading"
            class="w-full py-3.5 px-4 bg-brand-purple-primary hover:bg-brand-purple-light text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center"
        >
            <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="loading ? 'Logging in...' : 'Login'">Login</span>
        </button>

        <!-- Instructor Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                or login as 
                <a href="{{ route('instructor.login') }}" class="font-medium text-brand-purple-primary hover:text-brand-purple-light transition-colors">
                    Instructor
                </a>
            </p>
        </div>

        <!-- Register Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't have an account yet? 
                <a href="{{ route('register') }}" class="font-semibold text-brand-purple-primary hover:text-brand-purple-light transition-colors">
                    Register
                </a>
            </p>
        </div>

        <!-- Footer Links -->
        <div class="mt-8 pt-6 border-t border-gray-200">
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
