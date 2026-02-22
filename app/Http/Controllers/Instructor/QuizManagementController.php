<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class QuizManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::with(['module', 'lesson']);

        if ($request->filled('module_id')) {
            $query->where('module_id', $request->module_id);
        }

        $quizzes = $query->latest()->paginate(15);
        $modules = Module::all();

        return view('instructor.quizzes.index', compact('quizzes', 'modules'));
    }

    public function create(Request $request)
    {
        $modules = Module::with('lessons')->get();
        $lessonId = $request->query('lesson_id');
        
        return view('instructor.quizzes.create', compact('modules', 'lessonId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
        ]);

        // Ensure at least one of module_id or lesson_id is provided
        if (!$validated['module_id'] && !$validated['lesson_id']) {
            return back()->withErrors(['module_id' => 'Please select either a module or a lesson.']);
        }

        $validated['slug'] = Str::slug($validated['title']);

        Quiz::create($validated);

        return redirect()->route('instructor.quizzes.index')
            ->with('success', 'Quiz created successfully! Now add questions to it.');
    }

    public function show(Quiz $quiz)
    {
        $quiz->load(['questions.options', 'module', 'lesson']);
        return view('instructor.quizzes.show', compact('quiz'));
    }

    public function edit(Quiz $quiz)
    {
        $modules = Module::with('lessons')->get();
        return view('instructor.quizzes.edit', compact('quiz', 'modules'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
        ]);

        if (!$validated['module_id'] && !$validated['lesson_id']) {
            return back()->withErrors(['module_id' => 'Please select either a module or a lesson.']);
        }

        $validated['slug'] = Str::slug($validated['title']);

        $quiz->update($validated);

        return redirect()->route('instructor.quizzes.index')
            ->with('success', 'Quiz updated successfully!');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()->route('instructor.quizzes.index')
            ->with('success', 'Quiz deleted successfully!');
    }

    // Question management
    public function addQuestion(Quiz $quiz)
    {
        return view('instructor.quizzes.add-question', compact('quiz'));
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,multiple_select,fill_blank_text,fill_blank_select,identification',
            'points' => 'required|integer|min:1',
            
            // For multiple choice/true_false/multiple_select
            'options' => 'required_if:question_type,multiple_choice,true_false,multiple_select|array|min:2',
            'options.*' => 'required_with:options|string',
            'correct_options' => 'required_if:question_type,multiple_choice,true_false,multiple_select|array|min:1',
            'correct_options.*' => 'required_with:correct_options|integer',
            
            // For fill_blank_text, fill_blank_select, and identification
            'acceptable_answers' => 'required_if:question_type,fill_blank_text,fill_blank_select,identification|array|min:1',
            'acceptable_answers.*' => 'required_with:acceptable_answers|string',
            'case_sensitive' => 'nullable|boolean',
            
            // For fill_blank_select (word selection)
            'word_bank' => 'nullable|required_if:question_type,fill_blank_select|string',
            
            // For identification (image upload)
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        // Additional validation for word bank (max 10 words)
        if ($request->question_type === 'fill_blank_select' && $request->word_bank) {
            $words = array_map('trim', explode(',', $request->word_bank));
            if (count($words) > 10) {
                return back()->withErrors(['word_bank' => 'Word bank cannot exceed 10 words.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Handle image upload for identification questions
            $imagePath = null;
            if ($request->has('image') && $request->file('image')) {
                $imagePath = $request->file('image')->store('quiz-images', 'public');
            }

            // Create question record
            // Convert acceptable_answers array to pipe-separated string for storage
            $acceptableAnswersString = null;
            if (isset($validated['acceptable_answers']) && is_array($validated['acceptable_answers'])) {
                $acceptableAnswersString = implode('|', array_map('trim', $validated['acceptable_answers']));
            }
            
            $question = $quiz->questions()->create([
                'question_text' => $validated['question_text'],
                'question_type' => $validated['question_type'],
                'points' => $validated['points'],
                'order' => $quiz->questions()->max('order') + 1,
                'acceptable_answers' => $acceptableAnswersString,
                'case_sensitive' => $request->has('case_sensitive'),
                'word_bank' => $request->word_bank ? array_map('trim', explode(',', $request->word_bank)) : null,
                'image_path' => $imagePath,
            ]);

            // Add options for multiple choice/true_false/multiple_select types
            if (isset($validated['options'])) {
                foreach ($validated['options'] as $index => $optionText) {
                    $question->options()->create([
                        'option_text' => $optionText,
                        'is_correct' => in_array($index, $validated['correct_options']),
                        'order' => $index,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('instructor.quizzes.show', $quiz)
                ->with('success', 'Question added successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to add question: ' . $e->getMessage());
            return back()->with('error', 'Failed to add question: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show edit question form
     */
    public function editQuestion(Quiz $quiz, QuizQuestion $question)
    {
        // Verify question belongs to this quiz
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        return view('instructor.quizzes.edit-question', compact('quiz', 'question'));
    }

    /**
     * Update existing question
     */
    public function updateQuestion(Request $request, Quiz $quiz, QuizQuestion $question)
    {
        // Verify question belongs to this quiz
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,multiple_select,fill_blank_text,fill_blank_select,identification',
            'points' => 'required|integer|min:1',
            
            // For multiple choice/true_false/multiple_select
            'options' => 'required_if:question_type,multiple_choice,true_false,multiple_select|array|min:2',
            'options.*' => 'required_with:options|string',
            'correct_options' => 'required_if:question_type,multiple_choice,true_false,multiple_select|array|min:1',
            'correct_options.*' => 'required_with:correct_options|integer',
            
            // For fill_blank_text, fill_blank_select, and identification
            'acceptable_answers' => 'required_if:question_type,fill_blank_text,fill_blank_select,identification|array|min:1',
            'acceptable_answers.*' => 'required_with:acceptable_answers|string',
            'case_sensitive' => 'nullable|boolean',
            
            // For fill_blank_select (word selection)
            'word_bank' => 'nullable|required_if:question_type,fill_blank_select|string',
            
            // For identification (image upload)
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        DB::beginTransaction();
        try {
            // Handle image upload for identification questions
            $imagePath = $question->image_path; // Keep existing image
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($question->image_path) {
                    \Storage::disk('public')->delete($question->image_path);
                }
                $imagePath = $request->file('image')->store('quiz-images', 'public');
            }

            // Convert acceptable_answers array to pipe-separated string for storage
            $acceptableAnswersString = null;
            if (isset($validated['acceptable_answers']) && is_array($validated['acceptable_answers'])) {
                $acceptableAnswersString = implode('|', array_map('trim', $validated['acceptable_answers']));
            }
            
            // Update question record
            $question->update([
                'question_text' => $validated['question_text'],
                'question_type' => $validated['question_type'],
                'points' => $validated['points'],
                'acceptable_answers' => $acceptableAnswersString,
                'case_sensitive' => $request->has('case_sensitive'),
                'word_bank' => $request->word_bank ? array_map('trim', explode(',', $request->word_bank)) : null,
                'image_path' => $imagePath,
            ]);

            // Delete existing options and recreate for option-based types
            $question->options()->delete();
            
            if (isset($validated['options'])) {
                foreach ($validated['options'] as $index => $optionText) {
                    $question->options()->create([
                        'option_text' => $optionText,
                        'is_correct' => in_array($index, $validated['correct_options']),
                        'order' => $index,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('instructor.quizzes.show', $quiz)
                ->with('success', 'Question updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update question: ' . $e->getMessage());
            return back()->with('error', 'Failed to update question: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete a question
     */
    public function deleteQuestion(Quiz $quiz, QuizQuestion $question)
    {
        // Verify question belongs to this quiz
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            // Delete image if exists
            if ($question->image_path) {
                \Storage::disk('public')->delete($question->image_path);
            }

            // Delete options
            $question->options()->delete();

            // Delete question
            $question->delete();

            DB::commit();

            return redirect()->route('instructor.quizzes.show', $quiz)
                ->with('success', 'Question deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete question: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete question: ' . $e->getMessage());
        }
    }

    public function downloadTemplate(Quiz $quiz)
    {
        $filename = 'quiz_import_template.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = [
            'question_text',
            'question_type',
            'points',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_answer',
            'acceptable_answers',
            'word_bank',
            'case_sensitive',
            'image_filename'
        ];

        $samples = [
            [
                'What is 2 + 2?',
                'multiple_choice',
                '1',
                'Two',
                'Three',
                'Four',
                'Five',
                '2', // Index of correct option (0=A, 1=B, 2=C, 3=D)
                '',
                '',
                '0',
                ''
            ],
            [
                'The sky is _____ during the day.',
                'fill_blank_text',
                '1',
                '',
                '',
                '',
                '',
                '',
                'blue|Blue', // Pipe-separated alternatives for single blank
                '',
                '0', // 0=case insensitive, 1=case sensitive
                ''
            ],
            [
                'Identify this plant part.',
                'identification',
                '2',
                '',
                '',
                '',
                '',
                '',
                'leaf|leaves', // Acceptable answers (pipe-separated alternatives)
                '',
                '0',
                'plant_leaf.jpg' // Simple filename - system finds it with timestamp
            ],
            [
                'The _____ is green and the _____ is blue.',
                'fill_blank_select',
                '2',
                '',
                '',
                '',
                '',
                '',
                'grass;sky', // Semicolon-separated for multiple blanks
                'grass,sky,sun,moon,cloud', // Word bank (comma-separated)
                '0',
                ''
            ],
            [
                'Earth orbits around the Sun.',
                'true_false',
                '1',
                '', // Leave empty - options are auto-generated as "True" and "False"
                '',
                '',
                '',
                '0', // 0=True, 1=False
                '',
                '',
                '0',
                ''
            ],
            [
                'Which are fruits? (Select all that apply)',
                'multiple_select',
                '2',
                'Apple',
                'Carrot',
                'Banana',
                'Potato',
                '0,2', // Comma-separated indices of correct options
                '',
                '',
                '0',
                ''
            ]
        ];

        $callback = function() use ($columns, $samples) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($samples as $sample) {
                fputcsv($file, $sample);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function previewImport(Request $request, Quiz $quiz)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // Max 5MB
        ]);

        try {
            $file = $request->file('csv_file');
            $handle = fopen($file->getRealPath(), 'r');
            
            // Read header row
            $headers = fgetcsv($handle);
            
            if (!$headers) {
                return back()->with('error', 'CSV file is empty or invalid.');
            }

            $validRows = [];
            $invalidRows = [];
            $rowNumber = 1; // Start from 1 (header is 0)

            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }

                $row = array_combine($headers, $data);
                
                // Resolve image filename if present
                if (!empty($row['image_filename'])) {
                    $resolvedImage = $this->resolveImageFilename($row['image_filename']);
                    if ($resolvedImage) {
                        $row['_resolved_image'] = $resolvedImage;
                    }
                }
                
                $errors = $this->validateCsvRow($row, $quiz->id);

                if (empty($errors)) {
                    $validRows[] = [
                        'row_number' => $rowNumber,
                        'data' => $row
                    ];
                } else {
                    $invalidRows[] = [
                        'row_number' => $rowNumber,
                        'data' => $row,
                        'errors' => $errors
                    ];
                }
            }

            fclose($handle);

            // Store valid rows in session for confirmation
            session(['csv_import_data' => [
                'quiz_id' => $quiz->id,
                'valid_rows' => $validRows
            ]]);

            return view('instructor.quizzes.import-preview', compact('quiz', 'validRows', 'invalidRows'));

        } catch (\Exception $e) {
            \Log::error('CSV Import Preview Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to parse CSV: ' . $e->getMessage());
        }
    }

    private function validateCsvRow($row, $quizId)
    {
        $errors = [];

        // Required fields
        if (empty($row['question_text'])) {
            $errors[] = 'Question text is required.';
        }

        if (empty($row['question_type'])) {
            $errors[] = 'Question type is required.';
        } else {
            $validTypes = ['multiple_choice', 'true_false', 'multiple_select', 'fill_blank_text', 'fill_blank_select', 'identification'];
            if (!in_array($row['question_type'], $validTypes)) {
                $errors[] = 'Invalid question type. Must be one of: ' . implode(', ', $validTypes);
            }
        }

        if (empty($row['points']) || !is_numeric($row['points']) || $row['points'] < 1) {
            $errors[] = 'Points must be a positive integer.';
        }

        // Type-specific validation
        if (!empty($row['question_type'])) {
            switch ($row['question_type']) {
                case 'multiple_choice':
                case 'multiple_select':
                    if (empty($row['option_a']) || empty($row['option_b'])) {
                        $errors[] = 'Options A and B are required for option-based questions.';
                    }
                    if (empty($row['correct_answer']) && $row['correct_answer'] !== '0') {
                        $errors[] = 'Correct answer is required.';
                    }
                    break;

                case 'true_false':
                    // True/False doesn't require options in CSV - they're auto-generated as "True" and "False"
                    if (empty($row['correct_answer']) && $row['correct_answer'] !== '0') {
                        $errors[] = 'Correct answer is required (0=True, 1=False).';
                    } elseif (!in_array($row['correct_answer'], ['0', '1'])) {
                        $errors[] = 'Correct answer must be 0 (True) or 1 (False).';
                    }
                    break;

                case 'fill_blank_text':
                    if (empty($row['acceptable_answers'])) {
                        $errors[] = 'Acceptable answers are required for fill_blank_text.';
                    }
                    // Count blanks in question text
                    $blankCount = substr_count($row['question_text'], '_____');
                    if ($blankCount === 0) {
                        $errors[] = 'Fill in the blank questions must contain at least one blank (_____)';
                    }
                    // For fill_blank_text: if multiple blanks, answers should be separated by semicolons
                    // Each blank can have multiple acceptable alternatives separated by pipes
                    if ($blankCount > 1 && !empty($row['acceptable_answers'])) {
                        $blanksAnswers = explode(';', $row['acceptable_answers']);
                        if (count($blanksAnswers) !== $blankCount) {
                            $errors[] = "For $blankCount blanks, provide $blankCount answer groups separated by semicolons (e.g., 'ans1a|ans1b;ans2a|ans2b').";
                        }
                    }
                    break;

                case 'identification':
                    if (empty($row['acceptable_answers'])) {
                        $errors[] = 'Acceptable answers are required for identification.';
                    }
                    break;

                case 'fill_blank_select':
                    if (empty($row['acceptable_answers'])) {
                        $errors[] = 'Acceptable answers are required.';
                    }
                    if (empty($row['word_bank'])) {
                        $errors[] = 'Word bank is required for fill_blank_select.';
                    }
                    $blankCount = substr_count($row['question_text'], '_____');
                    if ($blankCount === 0) {
                        $errors[] = 'Question must contain at least one blank (_____)';
                    }
                    // For fill_blank_select: answers must match blank count (semicolon-separated for multiple blanks)
                    if ($blankCount > 0 && !empty($row['acceptable_answers'])) {
                        $answers = explode(';', $row['acceptable_answers']);
                        if (count($answers) !== $blankCount) {
                            $errors[] = "For $blankCount blanks, provide $blankCount answers separated by semicolons.";
                        }
                    }
                    break;
            }
        }

        // Image validation
        if (!empty($row['image_filename'])) {
            // Check if image exists (either exact match or with timestamp prefix)
            if (empty($row['_resolved_image'])) {
                $errors[] = 'Image file "' . $row['image_filename'] . '" not found in image library. Upload it first in the Image Library page.';
            }
        }

        return $errors;
    }

    private function resolveImageFilename($filename)
    {
        // Check exact match first
        if (\Storage::disk('public')->exists('quiz-images/' . $filename)) {
            return 'quiz-images/' . $filename;
        }
        
        // Try to find image with timestamp prefix (e.g., 1234567890_plant_leaf.jpg)
        $allImages = \Storage::disk('public')->files('quiz-images');
        foreach ($allImages as $imagePath) {
            $imageFilename = basename($imagePath);
            // Check if filename ends with the provided name (ignoring timestamp prefix)
            if (str_ends_with($imageFilename, $filename) || 
                preg_match('/^\d+_' . preg_quote($filename, '/') . '$/', $imageFilename)) {
                return $imagePath;
            }
        }
        
        return null;
    }

    public function confirmImport(Request $request, Quiz $quiz)
    {
        $importData = session('csv_import_data');

        if (!$importData || $importData['quiz_id'] !== $quiz->id) {
            return redirect()->route('instructor.quizzes.show', $quiz)
                ->with('error', 'Import session expired. Please upload the CSV again.');
        }

        $validRows = $importData['valid_rows'];
        $imported = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($validRows as $rowData) {
                $row = $rowData['data'];

                // Check if question already exists (by question_text)
                $existingQuestion = QuizQuestion::where('quiz_id', $quiz->id)
                    ->where('question_text', $row['question_text'])
                    ->first();

                $questionData = [
                    'quiz_id' => $quiz->id,
                    'question_text' => $row['question_text'],
                    'question_type' => $row['question_type'],
                    'points' => (int) $row['points'],
                    'case_sensitive' => !empty($row['case_sensitive']) && $row['case_sensitive'] === '1',
                ];

                // Type-specific fields
                if (in_array($row['question_type'], ['fill_blank_text', 'fill_blank_select', 'identification'])) {
                    // Convert semicolon-separated answers to pipe-separated for storage
                    // Semicolons separate multiple blanks, pipes separate alternatives for same blank
                    $questionData['acceptable_answers'] = $row['acceptable_answers'];
                }

                if ($row['question_type'] === 'fill_blank_select') {
                    $wordBank = array_map('trim', explode(',', $row['word_bank']));
                    $questionData['word_bank'] = $wordBank;
                }

                // Image handling - use resolved filename if available
                if (!empty($row['_resolved_image'])) {
                    $questionData['image_path'] = $row['_resolved_image'];
                } elseif (!empty($row['image_filename'])) {
                    // Fallback to direct filename
                    $questionData['image_path'] = 'quiz-images/' . $row['image_filename'];
                }

                if ($existingQuestion) {
                    // Update existing question
                    $existingQuestion->update($questionData);
                    // Delete old options
                    $existingQuestion->options()->delete();
                    $question = $existingQuestion;
                    $updated++;
                } else {
                    // Create new question
                    $question = QuizQuestion::create($questionData);
                    $imported++;
                }

                // Create options for option-based questions
                if (in_array($row['question_type'], ['multiple_choice', 'true_false', 'multiple_select'])) {
                    $options = [];
                    
                    if ($row['question_type'] === 'true_false') {
                        // Auto-generate True/False options
                        $options = ['True', 'False'];
                    } else {
                        // Collect options from CSV
                        foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $index => $key) {
                            if (!empty($row[$key])) {
                                $options[] = $row[$key];
                            }
                        }
                    }

                    foreach ($options as $index => $optionText) {
                        $isCorrect = false;

                        if ($row['question_type'] === 'multiple_choice' || $row['question_type'] === 'true_false') {
                            $isCorrect = (int) $row['correct_answer'] === $index;
                        } elseif ($row['question_type'] === 'multiple_select') {
                            $correctIndices = array_map('intval', explode(',', $row['correct_answer']));
                            $isCorrect = in_array($index, $correctIndices);
                        }

                        QuizOption::create([
                            'quiz_question_id' => $question->id,
                            'option_text' => $optionText,
                            'is_correct' => $isCorrect,
                        ]);
                    }
                }
            }

            DB::commit();
            session()->forget('csv_import_data');

            $message = "Successfully imported $imported new question(s)";
            if ($updated > 0) {
                $message .= " and updated $updated existing question(s)";
            }
            $message .= ".";

            return redirect()->route('instructor.quizzes.show', $quiz)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CSV Import Confirm Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to import questions: ' . $e->getMessage());
        }
    }
}
