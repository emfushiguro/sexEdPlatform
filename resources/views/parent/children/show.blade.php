@extends('layouts.learner-app')
@section('title', 'My Child')
@section('content')
<p>Child: {{ $child->name }}</p>
@endsection
