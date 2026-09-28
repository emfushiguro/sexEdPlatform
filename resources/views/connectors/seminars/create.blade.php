@extends('layouts.connector-app')

@section('title', 'Create Educational Event')
@section('page-title', 'Create Educational Event')

@section('content')
    <div class="mx-auto max-w-4xl rounded-lg border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('connector.seminars.store', $connector) }}">
            @csrf
            @include('connectors.seminars._form', ['submitLabel' => 'Create Draft'])
        </form>
    </div>
@endsection
