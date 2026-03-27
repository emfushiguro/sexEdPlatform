@extends('layouts.admin')

@section('content')
    @include('admin.modules.partials.form', [
        'title' => 'Edit Admin Module',
        'action' => route('admin.modules.update', $module),
        'method' => 'PUT',
        'module' => $module,
    ])
@endsection
