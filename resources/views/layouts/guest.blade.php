<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $metaTitle = trim($__env->yieldContent('title', config('app.name', 'Conscious Connections')));
            $metaDescription = trim($__env->yieldContent('meta_description', 'Conscious Connections learning platform for safe, inclusive, and accessible sexual health education.'));
            $metaImage = trim($__env->yieldContent('meta_image', asset('media/Logo.png')));
        @endphp

        <title>{{ $metaTitle }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:image" content="{{ $metaImage }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $metaImage }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>

        <!-- Toast Notifications -->
        @stack('scripts')
        
        <!-- Global Toast Notification Handler -->
        <script>
            console.log('Guest layout JavaScript is running');
            console.log('window.toast available?', typeof window.toast);
            
            // Function to wait for toast to be available
            function waitForToast(callback, maxAttempts = 50) {
                let attempts = 0;
                const interval = setInterval(() => {
                    attempts++;
                    console.log('Checking for toast... attempt', attempts, 'window.toast:', typeof window.toast);
                    if (typeof window.toast !== 'undefined') {
                        clearInterval(interval);
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
        </script>
    </body>
</html>
