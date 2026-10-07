@extends('layouts.connector-app')

@section('title', $module->title)
@section('page-title', 'Module Details')

@section('content')
<div class="space-y-6">
    @include('connectors.modules.partials.tabs')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('connector.modules', $connector) }}" class="text-sm font-semibold text-brand-700">Back to marketplace</a>
        @if($canManage)
            <span class="rounded-lg bg-brand-700 px-3 py-2 text-sm font-semibold text-white">Purchase for Connector</span>
        @endif
    </div>
    @include('modules.partials.details-overview', ['module' => $module])
    @include('modules.partials.details-curriculum', ['lessons' => $lessons, 'canOpenLessons' => $canOpenLessons])
</div>
@endsection
