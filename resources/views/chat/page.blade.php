@php
    $role = auth()->user()?->role;
    $layout = match ($role) {
        'admin' => 'layouts.admin',
        'instructor' => 'layouts.instructor-app',
        default => 'layouts.learner-app',
    };
@endphp

@extends($layout)

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('content')
    @include('chat.index')
@endsection
