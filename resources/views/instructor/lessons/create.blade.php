@extends('layouts.instructor-app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <div class="rounded-2xl bg-white shadow-sm border border-gray-100 p-12 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl text-white mb-4"
             style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900">Lesson creation moved to modal workflow</h1>
        <p class="mt-3 text-sm text-gray-600 max-w-lg mx-auto leading-relaxed">
            Use the lesson creation action from the Manage Lessons page to add new lessons through the modern slideout modal.
        </p>

        <a href="{{ route('instructor.lessons.index') }}"
           class="inline-flex mt-6 items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all"
           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            Go to Manage Lessons
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
@endsection
