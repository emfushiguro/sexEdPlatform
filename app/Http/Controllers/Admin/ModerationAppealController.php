<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewSuspensionAppealRequest;
use App\Http\Requests\Moderation\StoreAppealThreadMessageRequest;
use App\Models\SuspensionAppeal;
use App\Services\Moderation\SuspensionAppealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationAppealController extends Controller
{
    public function __construct(private readonly SuspensionAppealService $suspensionAppealService)
    {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->string('status');
        $search = trim((string) $request->string('search'));

        $appeals = SuspensionAppeal::query()
            ->with([
                'user:id,name,email',
                'suspension:id,user_id,status,appeal_status',
                'reviewedByAdmin:id,name',
            ])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('appeal_reason', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.moderation.appeals.index', [
            'appeals' => $appeals,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(SuspensionAppeal $appeal): View
    {
        $appeal->loadMissing([
            'user:id,name,email,role',
            'suspension.user:id,name,email,role',
            'suspension.enforcementAction',
            'suspension.moderationCase',
            'reviewedByAdmin:id,name',
            'threadMessages.sender:id,name,role',
            'threadMessages.parentMessage:id,suspension_appeal_id,sender_user_id,message_body',
        ]);

        $threadMessages = $appeal->threadMessages()
            ->with(['sender:id,name,role', 'parentMessage:id,suspension_appeal_id,sender_user_id,message_body'])
            ->oldest('id')
            ->get();

        return view('admin.moderation.appeals.show', [
            'appeal' => $appeal,
            'threadMessages' => $threadMessages,
        ]);
    }

    public function review(ReviewSuspensionAppealRequest $request, SuspensionAppeal $appeal): RedirectResponse
    {
        $this->suspensionAppealService->reviewAppeal(
            appeal: $appeal,
            admin: $request->user(),
            action: (string) $request->string('action'),
            decisionNotes: (string) $request->string('review_decision_notes'),
        );

        return redirect()
            ->route('admin.moderation-appeals.show', $appeal)
            ->with('success', 'Appeal review decision recorded.');
    }

    public function storeThreadMessage(StoreAppealThreadMessageRequest $request, SuspensionAppeal $appeal): RedirectResponse
    {
        $validated = $request->validated();

        $parentMessage = null;
        if (!empty($validated['parent_message_id'])) {
            $parentMessage = $appeal->threadMessages()
                ->whereKey((int) $validated['parent_message_id'])
                ->first();
        }

        $this->suspensionAppealService->postThreadMessage(
            appeal: $appeal,
            sender: $request->user(),
            messageBody: (string) $validated['message_body'],
            parentMessage: $parentMessage,
        );

        return redirect()
            ->route('admin.moderation-appeals.show', $appeal)
            ->with('success', 'Thread response posted.');
    }
}
