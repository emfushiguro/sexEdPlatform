<?php

namespace App\Services\Learning;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class QuestionAuthoringService
{
    private const CHOICE_TYPES = ['multiple_choice', 'true_false', 'multiple_select'];

    private const TEXT_ANSWER_TYPES = ['fill_blank_text', 'fill_blank_select', 'identification'];

    private const PERSPECTIVE_TYPE = 'perspective_feedback';

    public const TYPES = [
        'multiple_choice',
        'true_false',
        'multiple_select',
        'fill_blank_text',
        'fill_blank_select',
        'identification',
    ];

    public const CHECKPOINT_TYPES = [
        ...self::TYPES,
        self::PERSPECTIVE_TYPE,
    ];

    public function validate(Request $request): array
    {
        return $this->validateForTypes($request, self::TYPES);
    }

    public function validateCheckpoint(Request $request, ?QuizQuestion $question = null): array
    {
        if ($request->input('question_type') !== self::PERSPECTIVE_TYPE) {
            $request->merge(['points' => 1]);
        }

        return $this->validateForTypes($request, self::CHECKPOINT_TYPES, $question);
    }

    private function validateForTypes(Request $request, array $allowedTypes, ?QuizQuestion $question = null): array
    {
        $this->normalizeRequest($request);

        $type = (string) $request->input('question_type');
        $rules = $type === self::PERSPECTIVE_TYPE
            ? $this->perspectiveRules($allowedTypes)
            : $this->rules($allowedTypes);

        $validator = ValidatorFacade::make(
            array_merge($request->all(), ['image' => $request->file('image')]),
            $rules,
        );
        $validator->after(function (Validator $validator) use ($request, $question): void {
            $this->validateConfiguration($validator, $request);
            $this->validatePerspectiveTypeConversion($validator, $request, $question);
        });

        return $validator->validate();
    }

    private function perspectiveRules(array $allowedTypes): array
    {
        return [
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:'.implode(',', $allowedTypes)],
            'points' => ['required', 'integer', 'min:0', 'max:0'],
            'context_description' => ['nullable', 'string', 'max:5000'],
            'perspective_options' => ['required', 'array', 'min:2', 'max:12'],
            'perspective_options.*.id' => ['nullable', 'integer', 'distinct'],
            'perspective_options.*.text' => ['required', 'string', 'max:500'],
            'perspective_options.*.feedback' => ['required', 'string', 'max:5000'],
            'allow_own_perspective' => ['required', 'boolean'],
            'perspective_prompt' => ['nullable', 'required_if:allow_own_perspective,1', 'string', 'max:500'],
            'perspective_character_limit' => ['nullable', 'required_if:allow_own_perspective,1', 'integer', 'min:100', 'max:5000'],
            'reflection_guide' => ['nullable', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function rules(array $allowedTypes = self::TYPES): array
    {
        return [
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:'.implode(',', $allowedTypes)],
            'points' => ['required', 'integer', 'min:1'],
            'options' => ['required_if:question_type,'.implode(',', self::CHOICE_TYPES), 'array', 'min:2'],
            'options.*' => ['required_with:options', 'string'],
            'correct_options' => ['required_if:question_type,'.implode(',', self::CHOICE_TYPES), 'array', 'min:1'],
            'correct_options.*' => ['required_with:correct_options', 'integer', 'distinct'],
            'acceptable_answers' => ['required_if:question_type,'.implode(',', self::TEXT_ANSWER_TYPES), 'array', 'min:1'],
            'acceptable_answers.*' => ['required_with:acceptable_answers', 'string'],
            'case_sensitive' => ['nullable', 'boolean'],
            'word_bank' => ['nullable', 'required_if:question_type,fill_blank_select', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'remove_existing_image' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function normalizeRequest(Request $request): void
    {
        $type = (string) $request->input('question_type');

        if ($type === self::PERSPECTIVE_TYPE) {
            $request->merge([
                'points' => 0,
                'allow_own_perspective' => $request->boolean('allow_own_perspective') ? 1 : 0,
                'perspective_options' => array_values((array) $request->input('perspective_options', [])),
            ]);
            $request->request->remove('options');
            $request->request->remove('correct_options');
            $request->request->remove('acceptable_answers');
            $request->request->remove('case_sensitive');
            $request->request->remove('word_bank');

            return;
        }

        if (in_array($type, self::CHOICE_TYPES, true)) {
            $options = array_values(array_map(
                fn ($option) => trim((string) $option),
                (array) $request->input('options', []),
            ));
            $correct = array_values(array_map(
                fn ($index) => filter_var($index, FILTER_VALIDATE_INT) !== false ? (int) $index : $index,
                (array) $request->input('correct_options', []),
            ));
            $request->merge([
                'options' => $type === 'true_false' ? ['True', 'False'] : $options,
                'correct_options' => $correct,
            ]);
        } else {
            $request->request->remove('options');
            $request->request->remove('correct_options');
        }

        if (in_array($type, self::TEXT_ANSWER_TYPES, true)) {
            $request->merge([
                'acceptable_answers' => array_values(array_map(
                    fn ($answer) => trim((string) $answer),
                    (array) $request->input('acceptable_answers', []),
                )),
                'case_sensitive' => $request->boolean('case_sensitive'),
            ]);
        } else {
            $request->request->remove('acceptable_answers');
            $request->request->remove('case_sensitive');
        }

        if ($type === 'fill_blank_select') {
            $words = array_values(array_filter(array_map(
                fn ($word) => trim((string) $word),
                explode(',', (string) $request->input('word_bank')),
            ), fn ($word) => $word !== ''));
            $request->merge(['word_bank' => implode(', ', $words)]);
        } else {
            $request->request->remove('word_bank');
        }
    }

    private function validateConfiguration(Validator $validator, Request $request): void
    {
        $type = (string) $request->input('question_type');
        $questionText = trim(html_entity_decode(strip_tags(
            str_replace(['&nbsp;', '&#160;'], ' ', (string) $request->input('question_text')),
        )));

        if ($questionText === '') {
            $validator->errors()->add('question_text', 'Question text is required.');
        }

        if (in_array($type, self::CHOICE_TYPES, true)) {
            $options = (array) $request->input('options', []);
            $correct = (array) $request->input('correct_options', []);
            $invalidIndices = array_filter($correct, fn ($index) => ! array_key_exists((int) $index, $options));

            if ($invalidIndices !== []) {
                $validator->errors()->add('correct_options', 'Every correct answer must refer to an answer option.');
            }
            if (in_array($type, ['multiple_choice', 'true_false'], true) && count($correct) !== 1) {
                $validator->errors()->add('correct_options', 'Select exactly one correct answer.');
            }
            if ($type === 'true_false' && ! in_array($correct[0] ?? null, [0, 1], true)) {
                $validator->errors()->add('correct_options', 'Select True or False as the correct answer.');
            }
        }

        if (in_array($type, self::TEXT_ANSWER_TYPES, true)) {
            foreach ((array) $request->input('acceptable_answers', []) as $answer) {
                $invalid = match ($type) {
                    'fill_blank_text' => str_contains($answer, ';')
                        || collect(explode('|', $answer))->contains(fn ($alternative) => trim($alternative) === ''),
                    'fill_blank_select' => str_contains($answer, ';') || str_contains($answer, '|'),
                    'identification' => str_contains($answer, '|'),
                    default => false,
                };
                if ($invalid) {
                    $validator->errors()->add('acceptable_answers', 'Answers contain a reserved separator or an empty alternative.');
                    break;
                }
            }
        }

        if (in_array($type, ['fill_blank_text', 'fill_blank_select'], true)) {
            $blankCount = substr_count((string) $request->input('question_text'), '_____');
            $answers = (array) $request->input('acceptable_answers', []);
            if ($blankCount < 1) {
                $validator->errors()->add('question_text', 'Add at least one blank using five underscores (_____).');
            }
            if ($blankCount !== count($answers)) {
                $validator->errors()->add('acceptable_answers', 'Add exactly one answer for each blank.');
            }
        }

        if ($type === 'fill_blank_select') {
            $words = array_map('trim', explode(',', (string) $request->input('word_bank')));
            if (count($words) > 10) {
                $validator->errors()->add('word_bank', 'Word bank cannot exceed 10 words.');
            }
            foreach ((array) $request->input('acceptable_answers', []) as $answer) {
                if (! in_array($answer, $words, true)) {
                    $validator->errors()->add('acceptable_answers', 'Every correct answer must appear in the Word Bank.');
                    break;
                }
            }
        }
    }

    private function validatePerspectiveTypeConversion(
        Validator $validator,
        Request $request,
        ?QuizQuestion $question,
    ): void {
        if (! $question || $question->question_type === $request->input('question_type')) {
            return;
        }

        $involvesPerspective = in_array(self::PERSPECTIVE_TYPE, [
            $question->question_type,
            (string) $request->input('question_type'),
        ], true);

        if ($involvesPerspective && $question->checkpointProgress()->exists()) {
            $validator->errors()->add(
                'question_type',
                'The checkpoint type cannot be changed after a learner has interacted with it.',
            );
        }
    }

    public function createQuestion(array $data, array $owner): QuizQuestion
    {
        return $this->withinTransaction(function () use ($data, $owner): QuizQuestion {
            $payload = $this->questionPayload($data, $owner);
            $this->deleteNewImageOnRollback($data, $payload['image_path']);
            $question = QuizQuestion::create($payload);
            $this->replaceOptions($question, $data);

            return $question->load('options');
        });
    }

    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
    {
        $oldImagePath = $question->image_path;

        return $this->withinTransaction(function () use ($question, $data, $oldImagePath): QuizQuestion {
            $payload = $this->questionPayload($data, [], $question->image_path);
            $this->deleteNewImageOnRollback($data, $payload['image_path']);
            $question->update($payload);
            $this->replaceOptions($question, $data);

            $imageWasReplaced = ($data['image'] ?? null) instanceof UploadedFile;
            $removeExisting = ! empty($data['remove_existing_image']);
            if ($oldImagePath && ($imageWasReplaced || $question->question_type !== 'identification' || $removeExisting)) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($oldImagePath));
            }

            return $question->refresh()->load('options');
        });
    }

    private function questionPayload(array $data, array $owner, ?string $existingImagePath = null): array
    {
        $answers = array_map('trim', $data['acceptable_answers'] ?? []);
        $acceptableAnswers = match ($data['question_type']) {
            'identification' => implode('|', $answers),
            'fill_blank_text', 'fill_blank_select' => implode(';', $answers),
            default => null,
        };
        $usesTextAnswers = in_array($data['question_type'], self::TEXT_ANSWER_TYPES, true);
        $usesImage = $data['question_type'] === 'identification';
        $isPerspective = $data['question_type'] === self::PERSPECTIVE_TYPE;

        return array_merge($owner, [
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'points' => $isPerspective ? 0 : (int) $data['points'],
            'context_description' => $isPerspective ? ($data['context_description'] ?? null) : null,
            'acceptable_answers' => $acceptableAnswers,
            'case_sensitive' => ! $isPerspective && $usesTextAnswers && ! empty($data['case_sensitive']),
            'word_bank' => $data['question_type'] === 'fill_blank_select'
                ? array_map('trim', explode(',', $data['word_bank']))
                : null,
            'image_path' => $usesImage
                ? (($data['image'] ?? null) instanceof UploadedFile
                    ? $data['image']->store($this->imageDirectory(), 'public')
                    : (! empty($data['remove_existing_image']) ? null : ($data['image_path'] ?? $existingImagePath)))
                : null,
            'explanation' => $data['explanation'] ?? null,
            'allow_own_perspective' => $isPerspective && ! empty($data['allow_own_perspective']),
            'perspective_prompt' => $isPerspective ? ($data['perspective_prompt'] ?? null) : null,
            'perspective_character_limit' => $isPerspective
                ? ($data['perspective_character_limit'] ?? null)
                : null,
            'reflection_guide' => $isPerspective ? ($data['reflection_guide'] ?? null) : null,
        ]);
    }

    private function replaceOptions(QuizQuestion $question, array $data): void
    {
        if ($data['question_type'] === self::PERSPECTIVE_TYPE) {
            $this->syncPerspectiveOptions($question, $data['perspective_options']);

            return;
        }

        $question->options()->delete();

        if (! in_array($data['question_type'], ['multiple_choice', 'true_false', 'multiple_select'], true)
            || ! isset($data['options'])
            || ! is_array($data['options'])) {
            return;
        }

        $correct = array_map('intval', $data['correct_options'] ?? []);

        foreach (array_values($data['options']) as $index => $optionText) {
            $question->options()->create([
                'option_text' => $optionText,
                'is_correct' => in_array($index, $correct, true),
                'order' => $index,
            ]);
        }
    }

    private function syncPerspectiveOptions(QuizQuestion $question, array $submitted): void
    {
        $existing = $question->options()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($submitted) as $order => $optionData) {
            $id = isset($optionData['id']) ? (int) $optionData['id'] : null;

            if ($id !== null && ! $existing->has($id)) {
                throw ValidationException::withMessages([
                    'perspective_options' => 'Every response option must belong to this checkpoint.',
                ]);
            }

            $option = $id !== null ? $existing->get($id) : $question->options()->make();
            $option->fill([
                'option_text' => $optionData['text'],
                'feedback' => $optionData['feedback'],
                'is_correct' => false,
                'order' => $order,
            ]);
            $option->save();
            $keptIds[] = $option->id;
        }

        $question->options()->whereNotIn('id', $keptIds)->delete();
    }

    private function imageDirectory(): string
    {
        return 'quiz-images/user-'.(int) Auth::id();
    }

    private function deleteNewImageOnRollback(array $data, ?string $imagePath): void
    {
        if (($data['image'] ?? null) instanceof UploadedFile && $imagePath) {
            DB::afterRollBack(fn () => Storage::disk('public')->delete($imagePath));
        }
    }

    private function withinTransaction(callable $callback): mixed
    {
        return DB::transactionLevel() > 0 ? $callback() : DB::transaction($callback);
    }
}
