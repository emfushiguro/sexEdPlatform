@extends('layouts.connector-app')

@section('title', 'Edit Seminar')
@section('page-title', 'Edit Seminar')

@section('content')
    <div class="mx-auto max-w-4xl rounded-lg border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('connector.seminars.update', [$connector, $seminar]) }}">
            @csrf
            @method('PUT')
            @include('connectors.seminars._form', ['submitLabel' => 'Save Changes'])
        </form>
    </div>
@endsection
