@extends($contentPanelLayout ?? 'layouts.instructor-app')

@section('content')
<div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h1 class="text-lg font-semibold text-gray-900">Quiz edit moved to modal workflow</h1>
            <p class="mt-2 text-sm text-gray-600">Use the quiz edit action from the Manage Quizzes page to update this quiz in the modal.</p>
            <p class="mt-1 text-sm text-gray-600">Timer (hours/minutes/seconds) and attempt limit are configured in that modal.</p>
            <a href="{{ route($contentRoutePrefix . '.quizzes.index', ['edit_quiz' => $quiz->id]) }}"
               class="inline-flex mt-4 items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 transition-colors">
                Open Manage Quizzes
            </a>
        </div>
    </div>
</div>
@endsection

