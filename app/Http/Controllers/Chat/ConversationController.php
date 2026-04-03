<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StartConversationRequest;
use App\Events\Chat\MessageRequestCreated;
use App\Enums\EnrollmentStatus;
use App\Models\Conversation;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Services\Chat\ChatAuthorizationService;
use App\Services\Chat\ChatService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ConversationController extends Controller
{
    public function __construct(
        protected ChatService $chatService,
        protected ChatAuthorizationService $chatAuthorizationService,
    ) {
    }

    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $userId = (int) $user->id;

        $conversations = Conversation::query()
            ->where(function ($query) use ($userId) {
                $query->where('participant_one_id', $userId)
                    ->orWhere('participant_two_id', $userId);
            })
            ->with([
                'participantOne:id,name,role',
                'participantTwo:id,name,role',
                'module:id,title',
                'lesson:id,title',
                'quiz:id,title',
                'latestMessage' => function ($query) {
                    $query->select([
                        'messages.id',
                        'messages.conversation_id',
                        'messages.sender_id',
                        'messages.message_body',
                        'messages.created_at',
                    ]);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Conversation $conversation) use ($user, $userId) {
                $otherParticipant = (int) $conversation->participant_one_id === $userId
                    ? $conversation->participantTwo
                    : $conversation->participantOne;

                return [
                    'id' => $conversation->id,
                    'participant_one_id' => $conversation->participant_one_id,
                    'participant_two_id' => $conversation->participant_two_id,
                    'conversation_type' => $conversation->conversation_type,
                    'status' => $conversation->status,
                    'context_key' => $conversation->context_key,
                    'context_label' => $this->buildContextLabel($conversation),
                    'last_message_at' => $conversation->last_message_at,
                    'latest_message_preview' => Str::limit((string) ($conversation->latestMessage?->message_body ?? ''), 80),
                    'participant_one' => $conversation->participantOne,
                    'participant_two' => $conversation->participantTwo,
                    'other_participant' => $otherParticipant,
                    'can_send' => $this->chatAuthorizationService->canSendMessage($user, $conversation),
                    'unread_count' => $this->chatService->unreadCountForConversation($user, $conversation),
                ];
            })
            ->values();

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function start(StartConversationRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $target = User::query()->findOrFail((int) $request->validated('target_user_id'));

        try {
            $conversation = $this->chatService->createOrGetConversation(
                initiator: $actor,
                target: $target,
                conversationType: (string) $request->validated('conversation_type'),
                moduleId: $request->validated('module_id'),
                lessonId: $request->validated('lesson_id'),
                quizId: $request->validated('quiz_id'),
            );

            return response()->json([
                'requires_request' => false,
                'conversation' => $conversation,
            ], 201);
        } catch (DomainException $exception) {
            if ($exception->getMessage() !== 'Message request required before direct conversation creation.') {
                throw $exception;
            }

            $messageRequest = $this->chatService->createMessageRequest(
                requester: $actor,
                instructor: $target,
                initialMessage: (string) ($request->validated('initial_message') ?? ''),
            );

            event(new MessageRequestCreated($messageRequest));

            return response()->json([
                'requires_request' => true,
                'message_request' => $messageRequest,
            ], 202);
        } catch (AuthorizationException | InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        }
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $readState = $this->chatService->markConversationRead($user, $conversation);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        }

        return response()->json([
            'read_state' => $readState,
        ]);
    }

    public function discover(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->id;
        $search = trim((string) $request->query('q', ''));

        $supportAdmin = User::query()
            ->where('role', 'admin')
            ->where('id', '!=', $userId)
            ->orderBy('id')
            ->first(['id', 'name', 'role']);

        $payload = [
            'role' => (string) $user->role,
            'support_admin' => $supportAdmin,
            'contacts' => [],
        ];

        if ($user->role === 'admin') {
            $payload['contacts'] = [
                'learners' => $this->queryUsersByRole('learner', $search)
                    ->limit(30)
                    ->get(['id', 'name', 'role'])
                    ->map(fn (User $contact) => [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'role' => $contact->role,
                        'subtitle' => 'Learner',
                    ])
                    ->values(),
                'instructors' => $this->queryUsersByRole('instructor', $search)
                    ->limit(30)
                    ->get(['id', 'name', 'role'])
                    ->map(fn (User $contact) => [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'role' => $contact->role,
                        'subtitle' => 'Instructor',
                    ])
                    ->values(),
            ];

            return response()->json($payload);
        }

        if ($user->role === 'instructor') {
            $learners = User::query()
                ->where('role', 'learner')
                ->whereHas('moduleEnrollments', function ($query) use ($userId) {
                    $query->where('status', EnrollmentStatus::Approved)
                        ->whereHas('module', function ($moduleQuery) use ($userId) {
                            $moduleQuery->where('created_by', $userId);
                        });
                })
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->with(['moduleEnrollments' => function ($query) use ($userId) {
                    $query->where('status', EnrollmentStatus::Approved)
                        ->whereHas('module', function ($moduleQuery) use ($userId) {
                            $moduleQuery->where('created_by', $userId);
                        })
                        ->with('module:id,title');
                }])
                ->limit(50)
                ->get(['id', 'name', 'role'])
                ->map(function (User $contact) {
                    $moduleTitle = $contact->moduleEnrollments->first()?->module?->title;

                    return [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'role' => $contact->role,
                        'subtitle' => $moduleTitle ? "Enrolled in {$moduleTitle}" : 'Enrolled learner',
                    ];
                })
                ->values();

            $payload['contacts'] = [
                'learners' => $learners,
            ];

            return response()->json($payload);
        }

        $instructorIdsWithEnrollment = ModuleEnrollment::query()
            ->where('user_id', $userId)
            ->where('status', EnrollmentStatus::Approved)
            ->join('modules', 'module_enrollments.module_id', '=', 'modules.id')
            ->pluck('modules.created_by')
            ->unique();

        $instructors = User::query()
            ->where('role', 'instructor')
            ->whereHas('authoredModules', function ($query) {
                $query->where('is_published', true);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->limit(50)
            ->get(['id', 'name', 'role'])
            ->map(function (User $contact) use ($instructorIdsWithEnrollment) {
                $enrolled = $instructorIdsWithEnrollment->contains((int) $contact->id);

                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'role' => $contact->role,
                    'subtitle' => $enrolled ? 'Direct messaging enabled' : 'Message request may be required',
                    'is_enrolled_relation' => $enrolled,
                ];
            })
            ->values();

        $payload['contacts'] = [
            'instructors' => $instructors,
        ];

        return response()->json($payload);
    }

    protected function queryUsersByRole(string $role, string $search)
    {
        return User::query()
            ->where('role', $role)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');
    }

    protected function buildContextLabel(Conversation $conversation): string
    {
        return match ($conversation->conversation_type) {
            Conversation::TYPE_MODULE_CHAT => 'Module Discussion - '.($conversation->module?->title ?? 'Module'),
            Conversation::TYPE_LESSON_CHAT => 'Lesson Discussion - '.($conversation->lesson?->title ?? 'Lesson'),
            Conversation::TYPE_QUIZ_HELP => 'Quiz Help - '.($conversation->quiz?->title ?? 'Quiz'),
            Conversation::TYPE_ADMIN_SUPPORT => 'Platform Support',
            default => 'Direct Conversation',
        };
    }
}
