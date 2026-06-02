@extends('layouts.connector-app')

@section('title', 'Modules')
@section('page-title', 'Modules')

@section('content')
<section class="rounded-xl border border-dashed bg-white p-10 text-center">
    <h2 class="text-lg font-bold">Module publishing is not enabled yet</h2>
    <p class="mt-2 text-sm text-gray-500">Connector modules remain gated until content governance and plan entitlements are available.</p>
    <button disabled class="mt-5 rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500">Create Module</button>
</section>
@endsection
