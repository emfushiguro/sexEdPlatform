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

            <h2 class="text-3xl font-bold text-white mb-3 leading-tight z-10">Young learner?</h2>
            <p class="text-white/75 text-base max-w-[200px] leading-relaxed z-10">A parent account is needed first</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Parent/Guardian Required</h2>
        <p class="mt-2 text-sm text-gray-600">Children under 13 years old need a parent or guardian to create their account.</p>
    </div>

    <!-- Purple info block -->
    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-6">
        <p class="text-sm text-purple-800">
            For your child's safety and to comply with online privacy laws, we require parental consent for users under 13 years old.
        </p>
    </div>

    <!-- Steps -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">How It Works:</h3>
        <div class="space-y-4">
            <!-- Step 1 -->
            <div class="flex items-start">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-purple-primary text-white flex items-center justify-center font-bold">
                    1
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-semibold text-gray-900">Parent/Guardian Registers</h4>
                    <p class="text-sm text-gray-600">Create your parent account (must be 18+ years old)</p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="flex items-start">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-purple-primary text-white flex items-center justify-center font-bold">
                    2
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-semibold text-gray-900">Verify Your Email</h4>
                    <p class="text-sm text-gray-600">We'll send a verification link to your Gmail</p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="flex items-start">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-purple-primary text-white flex items-center justify-center font-bold">
                    3
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-semibold text-gray-900">Create Child Account</h4>
                    <p class="text-sm text-gray-600">After verification, you can create an account for your child</p>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="flex items-start">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-purple-primary text-white flex items-center justify-center font-bold">
                    4
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-semibold text-gray-900">Monitor Progress</h4>
                    <p class="text-sm text-gray-600">Track your child's learning progress and quiz results</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="space-y-3">
        <a href="{{ route('parent.register') }}" class="w-full inline-flex justify-center items-center px-6 py-3 bg-brand-purple-primary text-white text-base font-medium rounded-xl hover:bg-brand-purple-light focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-purple-primary transition shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Register as Parent/Guardian
        </a>

        <a href="{{ route('learner.login') }}" class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-purple-primary transition">
            Already have an account? Login
        </a>
    </div>

    <!-- Privacy Notice -->
    <div class="mt-8 p-4 bg-gray-50 rounded-lg">
        <p class="text-xs text-gray-600 text-center">
            🔒 We take your child's privacy seriously. Read our 
            <a href="{{ route('privacy') }}" class="text-brand-purple-primary hover:text-brand-purple-light underline" target="_blank">Privacy Policy</a> 
            and <a href="{{ route('terms') }}" class="text-brand-purple-primary hover:text-brand-purple-light underline" target="_blank">Terms of Service</a>.
        </p>
    </div>
</x-auth-split-layout>
