@extends('layouts.connector-app')

@section('title', 'Create Seminar')
@section('page-title', 'Create Seminar')

@section('content')
    <div class="mx-auto max-w-4xl rounded-lg border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('connector.seminars.store', $connector) }}">
            @csrf
            @include('connectors.seminars._form', ['submitLabel' => 'Create Draft'])
        </form>
    </div>
@endsection
