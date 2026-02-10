<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Add Question to: {{ $quiz->title }}
            </h2>
            <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Quiz
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('instructor.quizzes.store-question', $quiz) }}" id="questionForm">
                        @csrf

                        <!-- Question Text -->
                        <div class="mb-6">
                            <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Question Text *
                            </label>
                            <textarea 
                                id="question_text" 
                                name="question_text" 
                                rows="3" 
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Enter your question here...">{{ old('question_text') }}</textarea>
                            @error('question_text')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Question Type -->
                        <div class="mb-6">
                            <label for="question_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Question Type *
                            </label>
                            <select 
                                id="question_type" 
                                name="question_type" 
                                required
                                onchange="updateQuestionType()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select question type...</option>
                                <option value="multiple_choice" {{ old('question_type') === 'multiple_choice' ? 'selected' : '' }}>
                                    Multiple Choice (Single Answer)
                                </option>
                                <option value="true_false" {{ old('question_type') === 'true_false' ? 'selected' : '' }}>
                                    True/False
                                </option>
                                <option value="multiple_select" {{ old('question_type') === 'multiple_select' ? 'selected' : '' }}>
                                    Multiple Select (Multiple Answers)
                                </option>
                            </select>
                            @error('question_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Points -->
                        <div class="mb-6">
                            <label for="points" class="block text-sm font-medium text-gray-700 mb-2">
                                Points *
                            </label>
                            <input 
                                type="number" 
                                id="points" 
                                name="points" 
                                min="1" 
                                value="{{ old('points', 1) }}"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('points')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Options Container -->
                        <div id="optionsContainer" class="mb-6" style="display: none;">
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-700">
                                    Answer Options *
                                </label>
                                <button 
                                    type="button" 
                                    id="addOptionBtn"
                                    onclick="addOption()" 
                                    class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded">
                                    + Add Option
                                </button>
                            </div>
                            <div id="optionsList" class="space-y-3">
                                <!-- Options will be added here dynamically -->
                            </div>
                            <p class="mt-2 text-xs text-gray-500" id="optionsHint"></p>
                            @error('options')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-4">
                            <a href="{{ route('instructor.quizzes.show', $quiz) }}" 
                               class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                Add Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let optionIndex = 0;
        let questionType = '';

        function updateQuestionType() {
            questionType = document.getElementById('question_type').value;
            const optionsContainer = document.getElementById('optionsContainer');
            const optionsList = document.getElementById('optionsList');
            const optionsHint = document.getElementById('optionsHint');
            const addOptionBtn = document.getElementById('addOptionBtn');
            
            optionsList.innerHTML = '';
            optionIndex = 0;

            if (questionType === 'true_false') {
                // Auto-create True/False options
                optionsContainer.style.display = 'block';
                addOption('True', true);
                addOption('False', false);
                addOptionBtn.style.display = 'none';
                optionsHint.textContent = 'Select the correct answer (radio button)';
            } else if (questionType === 'multiple_choice') {
                optionsContainer.style.display = 'block';
                addOption();
                addOption();
                addOptionBtn.style.display = 'inline-block';
                optionsHint.textContent = 'Select ONE correct answer (radio button). Add more options if needed.';
            } else if (questionType === 'multiple_select') {
                optionsContainer.style.display = 'block';
                addOption();
                addOption();
                addOptionBtn.style.display = 'inline-block';
                optionsHint.textContent = 'Select ALL correct answers (checkboxes). Add more options if needed.';
            } else {
                optionsContainer.style.display = 'none';
            }
        }

        function addOption(text = '', readonly = false) {
            const optionsList = document.getElementById('optionsList');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3';
            div.innerHTML = `
                <div class="flex items-center">
                    ${questionType === 'multiple_select' 
                        ? `<input type="checkbox" name="correct_options[]" value="${optionIndex}" class="w-5 h-5 text-blue-600 rounded">`
                        : `<input type="radio" name="correct_options[]" value="${optionIndex}" class="w-5 h-5 text-blue-600" required>`
                    }
                </div>
                <input 
                    type="text" 
                    name="options[]" 
                    value="${text}"
                    ${readonly ? 'readonly' : ''}
                    placeholder="Option ${optionIndex + 1}"
                    required
                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 ${readonly ? 'bg-gray-50' : ''}">
                ${readonly ? '' : `
                    <button 
                        type="button" 
                        onclick="this.parentElement.remove()" 
                        class="px-3 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded">
                        Remove
                    </button>
                `}
            `;
            optionsList.appendChild(div);
            optionIndex++;
        }

        // Initialize if there's an old value
        @if(old('question_type'))
            updateQuestionType();
        @endif
    </script>
</x-app-layout>
