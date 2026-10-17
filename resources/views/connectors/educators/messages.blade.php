@extends('layouts.connector-app')

@section('title', 'Negotiation messages')
@section('page-title', 'Negotiation messages')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600">Your private instructor conversations for {{ $connector->name }}.</p>
        <a href="{{ route('connector.educators', $connector) }}" class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Find an instructor</a>
    </div>
    @include('chat.index')
@endsection
