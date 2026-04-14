<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Alpine.js x-cloak style -->
        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased" x-data>
        <div class="min-h-screen bg-gray-100">
            @auth
                @if(Auth::user()->can('access learner platform') || Auth::user()->can('access parent dashboard'))
                    @include('layouts.learner-navigation')
                @else
                    @include('layouts.navigation')
                @endif
            @else
                @include('layouts.navigation')
            @endauth

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>

        <!-- Toast Notifications -->
        @stack('scripts')
        <script>
            // Function to wait for toast to be available
            function waitForToast(callback, maxAttempts = 50) {
                let attempts = 0;
                const interval = setInterval(() => {
                    attempts++;
                    if (typeof window.toast !== 'undefined') {
                        clearInterval(interval);
                        callback();
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('Toast notification system failed to load');
                    }
                }, 100); // Check every 100ms
            }

            // Wait for toast to be available, then show messages
            waitForToast(function() {
                // Show success message if present
                @if(session('success'))
                    window.toast.success("{{ addslashes(session('success')) }}");
                @endif

                // Show error messages
                @if(session('error'))
                    window.toast.error("{{ addslashes(session('error')) }}");
                @endif

                @if($errors->any())
                    @foreach($errors->all() as $error)
                        window.toast.error("{{ addslashes($error) }}");
                    @endforeach
                @endif

                // Show info message if present
                @if(session('info'))
                    window.toast.info("{{ addslashes(session('info')) }}");
                @endif

                // Show warning message if present
                @if(session('warning'))
                    window.toast.warning("{{ addslashes(session('warning')) }}");
                @endif
            });
        </script>
        
        @include('chat.partials.global-popup')
    </body>
</html>
