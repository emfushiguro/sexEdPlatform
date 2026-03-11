@props([
    'showTabs' => true,
    'activeTab' => 'login', // 'login' or 'register'
    'loginRoute' => null,
    'registerRoute' => null,
    'gradientFrom' => '#A30EB2',
    'gradientMid' => '#730DB1',
    'gradientTo' => '#3B0CB1',
    'logo' => '/media/Logo.png',
    'brandText' => 'Taboo',
    'panel' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Sex Ed Platform') }}</title>
    
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
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-6xl bg-white rounded-2xl shadow-2xl overflow-hidden relative">
            
            <!-- Tab Switcher (Absolute Top Right of Card) -->
            @if($showTabs)
            <div class="absolute top-6 right-6 sm:top-8 sm:right-8 z-20 flex gap-3">
                @if($loginRoute)
                <a href="{{ $loginRoute }}" 
                   class="px-7 py-2.5 rounded-full text-sm font-semibold transition-all duration-200
                          {{ $activeTab === 'login' 
                             ? 'bg-brand-purple-primary text-white shadow-lg hover:shadow-xl' 
                             : 'text-brand-purple-primary border-2 border-brand-purple-primary bg-white hover:bg-brand-purple-primary/10 shadow-sm' }}">
                    Login
                </a>
                @endif
                
                @if($registerRoute)
                <a href="{{ $registerRoute }}" 
                   class="px-7 py-2.5 rounded-full text-sm font-semibold transition-all duration-200
                          {{ $activeTab === 'register' 
                             ? 'bg-brand-purple-primary text-white shadow-lg hover:shadow-xl' 
                             : 'text-brand-purple-primary border-2 border-brand-purple-primary bg-white hover:bg-brand-purple-primary/10 shadow-sm' }}">
                    Register
                </a>
                @endif
            </div>
            @endif
            
            <div class="flex flex-col lg:flex-row min-h-[600px]">
                
                <!-- LEFT SIDE: Form Area -->
                <div class="w-full lg:w-1/2 p-8 sm:p-12 lg:p-16 relative flex flex-col">
                    
                    <!-- Form Content (Centered) -->
                    <div class="flex-1 flex items-center">
                        <div class="w-full max-w-md mx-auto">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
                
                <!-- RIGHT SIDE: Branding Area -->
                <div class="w-full lg:w-1/2 relative overflow-hidden" 
                     style="background: linear-gradient(135deg, {{ $gradientFrom }} 0%, {{ $gradientMid }} 50%, {{ $gradientTo }} 100%);">
                    
                    <!-- Decorative blur blobs -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
                        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
                    </div>

                    @if($panel && $panel->isNotEmpty())
                        {{ $panel }}
                    @else
                    <!-- Default: Logo fallback -->
                    <div class="relative h-full flex flex-col items-center justify-center p-12">
                        <div class="relative animate-fade-in">
                            <img src="{{ asset($logo) }}" 
                                 alt="Logo" 
                                 class="w-80 h-80 sm:w-96 sm:h-96 lg:w-[28rem] lg:h-[28rem] object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            
                            <!-- Fallback if logo doesn't exist -->
                            <div class="hidden text-center">
                                <div class="text-9xl mb-4">🏳️‍🌈</div>
                                <p class="text-white/50 text-sm">Logo placeholder</p>
                            </div>
                        </div>
                        
                        @if($brandText)
                        <h1 class="mt-8 text-5xl sm:text-6xl font-bold text-white tracking-wider font-['Brush_Script_MT',cursive] drop-shadow-lg animate-fade-in" 
                            style="animation-delay: 0.2s; font-family: 'Brush Script MT', cursive;">
                            {{ $brandText }}
                        </h1>
                        @endif
                    </div>
                    @endif
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Toast container -->
    <div id="toast-container"></div>
    
    <!-- Toast Notification Script -->
    <script>
        console.log('Auth split layout JavaScript is running');
        console.log('window.toast available?', typeof window.toast);
        
        // Function to wait for toast to be available (runs only once)
        (function() {
            let hasRun = false; // Prevent multiple executions
            
            function waitForToast(callback, maxAttempts = 50) {
                if (hasRun) {
                    console.log('Toast handler already executed, skipping...');
                    return;
                }
                
                let attempts = 0;
                const interval = setInterval(() => {
                    attempts++;
                    console.log('Checking for toast... attempt', attempts, 'window.toast:', typeof window.toast);
                    
                    if (typeof window.toast !== 'undefined') {
                        clearInterval(interval);
                        hasRun = true; // Mark as executed
                        console.log('Toast loaded! Executing callback...');
                        callback();
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('Toast notification system failed to load after', maxAttempts, 'attempts');
                    }
                }, 100);
            }

            // Wait for toast, then show messages
            waitForToast(function() {
                @if(session('success'))
                    console.log('Showing success toast');
                    window.toast.success("{{ addslashes(session('success')) }}");
                @endif

                @if($errors->any())
                    console.log('Showing error toasts');
                    @foreach($errors->all() as $error)
                        window.toast.error("{{ addslashes($error) }}");
                    @endforeach
                @endif

                @if(session('status'))
                    console.log('Showing status toast');
                    window.toast.info("{{ addslashes(session('status')) }}");
                @endif

                @if(session('info'))
                    console.log('Showing info toast');
                    window.toast.info("{{ addslashes(session('info')) }}");
                @endif

                @if(session('warning'))
                    console.log('Showing warning toast');
                    window.toast.warning("{{ addslashes(session('warning')) }}");
                @endif
            });
        })();
    </script>
    <!-- Legal + Help Modals (available on every auth page) -->
    <x-legal-modals />
    
</body>
</html>
