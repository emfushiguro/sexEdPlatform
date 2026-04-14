@extends($contentPanelLayout ?? 'layouts.instructor-app')

@section('content')
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white shadow-sm border border-gray-100">
                <div class="p-6 space-y-6">
                    <!-- Display Validation Errors -->
                    @if($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                            <p class="font-bold">Please fix the following errors:</p>
                            <ul class="list-disc list-inside mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route($contentRoutePrefix . '.quizzes.update-question', ['quiz' => $quiz, 'question' => $question]) }}" id="questionForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Question Text -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <label for="question_text" class="block text-sm font-medium text-gray-700">
                                    Question Text *
                                </label>
                                <button 
                                    type="button" 
                                    id="insertBlankBtn"
                                    onclick="insertBlank()"
                                    style="display: none;"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
                                    Insert Blank (_____)
                                </button>
                            </div>
                            <textarea 
                                id="question_text" 
                                name="question_text" 
                                rows="3" 
                                required
                                oninput="updateBlankCount()"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300"
                                placeholder="Enter your question here...">{{ old('question_text', $question->question_text) }}</textarea>
                            <div id="blankCountHint" style="display: none;" class="mt-1 text-xs">
                                <span class="text-purple-700 font-medium">Blanks detected: <span id="blankCount">0</span></span>
                                <span class="text-gray-500 ml-3">Use <code class="px-1 bg-gray-100 rounded">_____</code> (5 underscores) to create blanks</span>
                            </div>
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
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                                <option value="">Select question type...</option>
                                <option value="multiple_choice" {{ old('question_type', $question->question_type) === 'multiple_choice' ? 'selected' : '' }}>
                                    Multiple Choice (Single Answer)
                                </option>
                                <option value="true_false" {{ old('question_type', $question->question_type) === 'true_false' ? 'selected' : '' }}>
                                    True/False
                                </option>
                                <option value="multiple_select" {{ old('question_type', $question->question_type) === 'multiple_select' ? 'selected' : '' }}>
                                    Multiple Select (Multiple Answers)
                                </option>
                                <option value="fill_blank_text" {{ old('question_type', $question->question_type) === 'fill_blank_text' ? 'selected' : '' }}>
                                    Fill in the Blanks (Text Input)
                                </option>
                                <option value="fill_blank_select" {{ old('question_type', $question->question_type) === 'fill_blank_select' ? 'selected' : '' }}>
                                    Fill in the Blanks (Word Selection)
                                </option>
                                <option value="identification" {{ old('question_type', $question->question_type) === 'identification' ? 'selected' : '' }}>
                                    Identification
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
                                value="{{ old('points', $question->points) }}"
                                required
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                            @error('points')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Fill Blank Text & Identification Fields -->
                        <div id="textAnswerContainer" class="mb-6" style="display: none;">
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-700">
                                    Acceptable Answer(s) *
                                </label>
                                <button 
                                    type="button" 
                                    onclick="addAcceptableAnswer()" 
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
                                    + Add Answer
                                </button>
                            </div>
                            <div id="acceptableAnswersList" class="space-y-2">
                                <!-- Acceptable answers will be added here dynamically -->
                            </div>
                            <p class="mt-2 text-xs text-gray-600">Add multiple acceptable answers (e.g., "Paris", "paris", "Paris, France"). Use blank marker _____ in the question text above for fill-in-the-blank questions.</p>
                            @error('acceptable_answers')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <!-- Case Sensitive Toggle -->
                            <div class="mt-4 flex items-start gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-xl">
                                <input 
                                    type="checkbox" 
                                    id="case_sensitive" 
                                    name="case_sensitive" 
                                    value="1"
                                    class="mt-1 w-4 h-4 text-purple-700 border-gray-200 rounded focus:ring-purple-300">
                                <div>
                                    <label for="case_sensitive" class="text-sm font-medium text-gray-700">
                                        Case Sensitive
                                    </label>
                                    <p class="text-xs text-gray-600 mt-1">If checked, answers must match exact capitalization (e.g., "Paris" ≠ "paris"). Learners will see a notice.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Word Bank for Fill Blank Select -->
                        <div id="wordBankContainer" class="mb-6" style="display: none;">
                            <label for="word_bank" class="block text-sm font-medium text-gray-700 mb-2">
                                Word Bank *
                                <span class="text-xs text-gray-500">(Comma-separated words, max 10)</span>
                            </label>
                            <input 
                                type="text" 
                                id="word_bank" 
                                name="word_bank" 
                                placeholder="E.g., hydrogen, oxygen, nitrogen, carbon"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                            <p class="mt-1 text-xs text-gray-600">Use _____ in question text for each blank. Add the correct words below in the exact order they should appear.</p>
                            @error('word_bank')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            
                            <!-- Correct answers for word selection (in order) -->
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Correct Answers (in order) *
                                    </label>
                                    <button 
                                        type="button" 
                                        onclick="addWordBankAnswer()" 
                                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
                                        + Add Answer
                                    </button>
                                </div>
                                <div id="wordBankAnswersList" class="space-y-2">
                                    <!-- Word bank answers will be added here dynamically -->
                                </div>
                                <p class="mt-2 text-xs text-gray-600">Add correct words in the exact order they should fill the blanks (e.g., Blank 1: "hydrogen", Blank 2: "oxygen")</p>
                                @error('acceptable_answers')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Image Upload for Identification -->
                        <div id="imageContainer" class="mb-6" style="display: none;">
                            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                                Image (Optional)
                                <span class="text-xs text-gray-500">(JPG, PNG, max 2MB)</span>
                            </label>
                            <input 
                                type="file" 
                                id="image" 
                                name="image" 
                                accept="image/*"
                                class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 transition">
                            @error('image')
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
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
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
                            <a href="{{ route($contentRoutePrefix . '.quizzes.show', $quiz) }}" 
                               class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition">
                                Cancel
                            </a>
                                <button type="submit"
                                    class="flex-1 flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-[0.98]"
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                                Update Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let optionIndex = 0;
        let answerIndex = 0;
        let wordBankAnswerIndex = 0;
        let questionType = '';

        function updateQuestionType() {
            questionType = document.getElementById('question_type').value;
            const optionsContainer = document.getElementById('optionsContainer');
            const optionsList = document.getElementById('optionsList');
            const optionsHint = document.getElementById('optionsHint');
            const addOptionBtn = document.getElementById('addOptionBtn');
            
            // Get new containers
            const textAnswerContainer = document.getElementById('textAnswerContainer');
            const wordBankContainer = document.getElementById('wordBankContainer');
            const imageContainer = document.getElementById('imageContainer');
            const acceptableAnswersList = document.getElementById('acceptableAnswersList');
            const wordBankAnswersList = document.getElementById('wordBankAnswersList');
            
            // Hide all containers by default
            optionsContainer.style.display = 'none';
            textAnswerContainer.style.display = 'none';
            wordBankContainer.style.display = 'none';
            imageContainer.style.display = 'none';
            
            // Hide insert blank button and hint by default
            document.getElementById('insertBlankBtn').style.display = 'none';
            document.getElementById('blankCountHint').style.display = 'none';
            
            optionsList.innerHTML = '';
            acceptableAnswersList.innerHTML = '';
            if (wordBankAnswersList) wordBankAnswersList.innerHTML = '';
            optionIndex = 0;
            answerIndex = 0;
            wordBankAnswerIndex = 0;

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
            } else if (questionType === 'fill_blank_text') {
                // Show text answer container
                textAnswerContainer.style.display = 'block';
                addAcceptableAnswer(); // Start with one answer field
                
                // Show insert blank button and hint
                document.getElementById('insertBlankBtn').style.display = 'inline-block';
                document.getElementById('blankCountHint').style.display = 'block';
                updateBlankCount();
            } else if (questionType === 'fill_blank_select') {
                // Show word bank container
                wordBankContainer.style.display = 'block';
                addWordBankAnswer(); // Start with one answer field
                
                // Show blank hint
                document.getElementById('insertBlankBtn').style.display = 'inline-block';
                document.getElementById('blankCountHint').style.display = 'block';
                updateBlankCount();
            } else if (questionType === 'identification') {
                // Show text answer and image containers
                textAnswerContainer.style.display = 'block';
                imageContainer.style.display = 'block';
                addAcceptableAnswer(); // Start with one answer field
            }
        }

        function addOption(text = '', readonly = false) {
            const optionsList = document.getElementById('optionsList');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3 p-3 rounded-xl border bg-gray-50/60 border-gray-100';
            div.innerHTML = `
                <div class="flex items-center">
                    ${questionType === 'multiple_select' 
                        ? `<input type="checkbox" name="correct_options[]" value="${optionIndex}" class="w-5 h-5 text-purple-700 rounded">`
                        : `<input type="radio" name="correct_options[]" value="${optionIndex}" class="w-5 h-5 text-purple-700" required>`
                    }
                </div>
                <input 
                    type="text" 
                    name="options[]" 
                    value="${text}"
                    ${readonly ? 'readonly' : ''}
                    placeholder="Option ${optionIndex + 1}"
                    required
                    class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 ${readonly ? 'bg-gray-50' : ''}">
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

        function addAcceptableAnswer(text = '') {
            const acceptableAnswersList = document.getElementById('acceptableAnswersList');
            const currentCount = acceptableAnswersList.querySelectorAll('input[name="acceptable_answers[]"]').length;
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3';
            div.innerHTML = `
                <input 
                    type="text" 
                    name="acceptable_answers[]" 
                    value="${text}"
                    placeholder="Acceptable answer ${currentCount + 1}"
                    required
                    class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                <button 
                    type="button" 
                    onclick="this.parentElement.remove()" 
                    class="px-3 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded">
                    Remove
                </button>
            `;
            acceptableAnswersList.appendChild(div);
            answerIndex++;
        }

        function addWordBankAnswer(text = '') {
            const wordBankAnswersList = document.getElementById('wordBankAnswersList');
            const currentCount = wordBankAnswersList.querySelectorAll('input[name="acceptable_answers[]"]').length;
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3';
            div.innerHTML = `
                <input 
                    type="text" 
                    name="acceptable_answers[]" 
                    value="${text}"
                    placeholder="Correct word for blank ${currentCount + 1}"
                    required
                    class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                <button 
                    type="button" 
                    onclick="this.parentElement.remove()" 
                    class="px-3 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded">
                    Remove
                </button>
            `;
            wordBankAnswersList.appendChild(div);
            wordBankAnswerIndex++;
        }

        function insertBlank() {
            const textarea = document.getElementById('question_text');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            
            // Insert _____ at cursor position
            const blank = '_____';
            textarea.value = text.substring(0, start) + blank + text.substring(end);
            
            // Move cursor after the blank
            textarea.selectionStart = textarea.selectionEnd = start + blank.length;
            textarea.focus();
            
            // Update blank count
            updateBlankCount();
        }

        function updateBlankCount() {
            const questionText = document.getElementById('question_text').value;
            const blankCount = (questionText.match(/_____/g) || []).length;
            document.getElementById('blankCount').textContent = blankCount;
        }

        // Initialize if there's an old value or editing existing question
        @if(old('question_type') || isset($question))
            questionType = '{{ old('question_type', $question->question_type ?? '') }}';
            
            if (questionType) {
                updateQuestionType();
                
                // Pre-populate existing data
                @if(isset($question))
                    @if(in_array($question->question_type, ['multiple_choice', 'true_false', 'multiple_select']))
                        // Pre-populate options
                        @foreach($question->options as $option)
                            addOption('{{ $option->option_text }}', {{ $question->question_type === 'true_false' ? 'true' : 'false' }});
                        @endforeach
                        
                        // Set correct options
                        setTimeout(() => {
                            @foreach($question->options as $index => $option)
                                @if($option->is_correct)
                                    const checkbox{{ $index }} = document.querySelector('{{ $question->question_type === "multiple_select" ? "input[type=checkbox]" : "input[type=radio]" }}[name="correct_options[]"][value="{{ $index }}"]');
                                    if (checkbox{{ $index }}) checkbox{{ $index }}.checked = true;
                                @endif
                            @endforeach
                        }, 100);
                    @elseif(in_array($question->question_type, ['fill_blank_text', 'identification']))
                        // Pre-populate acceptable answers
                        @if($question->acceptable_answers)
                            @foreach(explode('|', $question->acceptable_answers) as $answer)
                                addAcceptableAnswer('{{ trim($answer) }}');
                            @endforeach
                        @endif
                        
                        // Set case sensitive
                        @if($question->case_sensitive)
                            document.getElementById('case_sensitive').checked = true;
                        @endif
                    @elseif($question->question_type === 'fill_blank_select')
                        // Pre-populate word bank
                        @if($question->word_bank)
                            document.getElementById('word_bank').value = '{{ implode(", ", $question->word_bank) }}';
                        @endif
                        
                        // Pre-populate correct answers
                        @if($question->acceptable_answers)
                            @foreach(explode('|', $question->acceptable_answers) as $answer)
                                addWordBankAnswer('{{ trim($answer) }}');
                            @endforeach
                        @endif
                    @endif
                @endif
            }
        @endif
    </script>
@endsection


