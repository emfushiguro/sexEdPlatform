<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account Review | Conscious Connections')</title>
    <link rel="icon" type="image/png" href="{{ asset('media/Logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important} body{font-family:'Poppins',sans-serif}</style>
</head>
<body data-moderation-layout class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 text-gray-900 antialiased">
    <header class="border-b border-purple-100 bg-white">
        <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-4 sm:px-6">
            <img src="{{ asset('media/Logo.png') }}" alt="Conscious Connections" class="h-12 w-12 shrink-0 object-contain">
            <div>
                <p class="text-base font-bold text-purple-900 sm:text-lg">Conscious Connections</p>
                <p class="text-xs text-gray-600">Account status and appeals</p>
            </div>
        </div>
    </header>
    <main id="main-content" class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 sm:py-10">
        @yield('content')
    </main>
    <footer class="px-4 pb-6 text-center text-xs text-gray-600">Conscious Connections · Account Review</footer>
    @stack('scripts')
    @include('components.logout-confirmation-modal')
</body>
</html>
