<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5" data-testid="quick-actions-section">
    <h2 class="text-base font-semibold text-gray-900 mb-3">Quick Actions</h2>
    <div class="grid grid-cols-2 gap-2">
        <a href="{{ route('instructor.modules.create') }}"
           class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 transition-all duration-200 text-center group">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span class="text-xs font-medium">Create Module</span>
        </a>

        <a href="{{ route('instructor.lessons.create') }}"
           class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 transition-all duration-200 text-center group">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-xs font-medium">Add Lesson</span>
        </a>

        <a href="{{ route('instructor.quizzes.create') }}"
           class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-green-50 hover:bg-green-600 hover:text-white text-green-700 transition-all duration-200 text-center group">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span class="text-xs font-medium">Create Quiz</span>
        </a>

        <a href="{{ route('instructor.enrollments.index') }}"
           class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-amber-50 hover:bg-amber-600 hover:text-white text-amber-700 transition-all duration-200 text-center group">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <span class="text-xs font-medium">Enrollments</span>
        </a>
    </div>
</div>
