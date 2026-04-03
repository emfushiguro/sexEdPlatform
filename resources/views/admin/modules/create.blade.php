@extends('layouts.admin')

@section('content')
    @include('admin.modules.partials.form', [
        'title' => 'Create Admin Module',
        'action' => route('admin.modules.store'),
        'method' => 'POST',
        'module' => null,
    ])
@endsection
