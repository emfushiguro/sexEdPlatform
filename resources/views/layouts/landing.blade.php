<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $metaTitle = trim($__env->yieldContent('title', 'Conscious Connections | Safe and Inclusive Sex Education'));
        $metaDescription = trim($__env->yieldContent('meta_description', 'A safe and judgment-free space for Filipino youth to learn about sexual health, relationships, and well-being.'));
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        html { scroll-behavior: auto; }
        body { font-family: 'Poppins', sans-serif; }
    </style>

    @stack('head')
</head>
<body class="antialiased" style="background-color: #F9F7FF;">

    @yield('content')

    @stack('scripts')
</body>
</html>
