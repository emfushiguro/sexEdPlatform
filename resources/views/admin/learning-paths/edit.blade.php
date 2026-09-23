@extends('layouts.admin')

@section('title', 'Edit Learning Path')
@section('page-title', 'Edit Learning Path')

@section('content')
    <div class="mb-5 flex justify-end">
        @can('view', $path)
            <a href="{{ route('admin.learning-paths.preview', $path) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">
                Preview learning path
            </a>
        @endcan
    </div>
    @include('admin.learning-paths.form')
@endsection
