@php
    $returnPath = request()->query('return');
    $decodedPath = is_string($returnPath) ? rawurldecode($returnPath) : '';
    $destination = str_starts_with($decodedPath, '/')
        && ! str_starts_with($decodedPath, '//')
        && ! preg_match('/[\\\\\x00-\x20\x7f]/', $decodedPath)
            ? $returnPath : '/';
@endphp
<a href="{{ $destination }}" {{ $attributes->class(['inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-opacity hover:opacity-75']) }}>← Back</a>
