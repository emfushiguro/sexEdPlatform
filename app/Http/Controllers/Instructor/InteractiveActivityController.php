<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InteractiveActivity;
use App\Models\Lesson;
use App\Services\Content\ContentOwnershipGuard;
use App\Services\Learning\InteractiveActivities\InteractiveActivityAuthoringService;
use App\Support\ContentPanelContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InteractiveActivityController extends Controller
{
    public function __construct(
        private readonly InteractiveActivityAuthoringService $authoring,
        private readonly ContentOwnershipGuard $ownershipGuard,
    ) {}

    public function edit(InteractiveActivity $interactiveActivity)
    {
        $lesson = $this->lessonFor($interactiveActivity);
        $this->authorize('update', $interactiveActivity->lessonTopic);
        $this->ensureAdminCanMutateLesson($lesson);

        return view('instructor.topics.edit-interactive-activity', [
            'activity' => $interactiveActivity->load('lessonTopic'),
            'lesson' => $lesson,
            'formAction' => route($this->routeName('interactive-activities.update'), $interactiveActivity),
        ]);
    }

    public function preview(Request $request)
    {
        $lesson = Lesson::query()->findOrFail((int) $request->input('lesson_id'));
        $this->authorize('update', $lesson);
        $this->ensureAdminCanMutateLesson($lesson);
        $data = $this->authoring->validate($request, $lesson);
        $activity = $this->authoring->preview($lesson, $data, $request->user());
        $activity['preview_evaluate_url'] = route($this->routeName('interactive-activities.preview-evaluate'));

        return response()->json([
            'html' => view('learner.lessons.partials.interactive-activities.shell', [
                'activity' => $activity,
                'continueUrl' => null,
                'inside' => false,
                'preview' => true,
            ])->render(),
        ]);
    }

    public function evaluatePreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preview_token' => ['required', 'string'],
            'action' => ['required', 'in:match,check_sequence,practice'],
        ]);
        $context = $this->authoring->decodePreviewToken($validated['preview_token'], $request->user());
        $lesson = Lesson::query()->findOrFail((int) $context['lesson_id']);
        $this->authorize('update', $lesson);
        $this->ensureAdminCanMutateLesson($lesson);

        $answer = ['action' => $validated['action']];
        if ($validated['action'] === 'match') {
            if ($context['activity_type'] !== 'matching') {
                throw ValidationException::withMessages(['action' => 'The Preview action does not match the activity type.']);
            }
            $answer += $request->validate([
                'connections' => ['sometimes', 'array', 'max:12'],
                'connections.*' => ['array'],
                'connections.*.left_id' => ['required', 'string'],
                'connections.*.right_id' => ['required', 'string'],
                'left_id' => ['required_without:connections', 'string'],
                'right_id' => ['required_without:connections', 'string'],
            ]);
        } elseif ($validated['action'] === 'check_sequence') {
            if ($context['activity_type'] !== 'sequencing') {
                throw ValidationException::withMessages(['action' => 'The Preview action does not match the activity type.']);
            }
            $answer += $request->validate([
                'item_order' => ['required', 'array'],
                'item_order.*' => ['string'],
            ]);
        }

        return response()->json($this->authoring->evaluatePreview($context, $answer));
    }

    public function update(Request $request, InteractiveActivity $interactiveActivity)
    {
        $lesson = $this->lessonFor($interactiveActivity);
        $this->authorize('update', $interactiveActivity->lessonTopic);
        $this->ensureAdminCanMutateLesson($lesson);
        $data = $this->authoring->validate($request, $lesson, $interactiveActivity);
        $this->authoring->update($interactiveActivity, $data);

        return redirect()
            ->route($this->routeName('lessons.show'), $lesson)
            ->with('success', 'Interactive activity updated successfully.');
    }

    public function destroy(InteractiveActivity $interactiveActivity)
    {
        $lesson = $this->lessonFor($interactiveActivity);
        $this->authorize('delete', $interactiveActivity->lessonTopic);
        $this->ensureAdminCanMutateLesson($lesson);
        $this->authoring->delete($interactiveActivity);

        return redirect()
            ->route($this->routeName('lessons.show'), $lesson)
            ->with('success', 'Interactive activity removed successfully.');
    }

    private function lessonFor(InteractiveActivity $activity): Lesson
    {
        return $activity->lessonTopic()->with('lesson.module')->firstOrFail()->lesson;
    }

    private function ensureAdminCanMutateLesson(Lesson $lesson): void
    {
        if (! app(ContentPanelContext::class)->isAdmin()) {
            return;
        }

        abort_unless(
            $this->ownershipGuard->canAdminMutateOwnerType($this->ownershipGuard->ownerTypeForLesson($lesson)),
            403,
            'Admins can only modify platform-owned learning content.',
        );
    }

    private function routeName(string $suffix): string
    {
        return app(ContentPanelContext::class)->name($suffix);
    }
}
