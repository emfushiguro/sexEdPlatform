<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\SubmitSuspensionAppealRequest;
use App\Models\UserSuspension;
use App\Services\Moderation\SuspensionAppealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class SuspensionAppealController extends Controller
{
    public function __construct(private readonly SuspensionAppealService $suspensionAppealService)
    {
    }

    public function create(UserSuspension $userSuspension): View
    {
        $currentUserId = Auth::id();

        abort_unless($currentUserId !== null && (int) $userSuspension->user_id === (int) $currentUserId, 403);

        $userSuspension->loadMissing([
            'enforcementAction',
            'moderationCase',
            'appeals.threadMessages.sender:id,name',
        ]);

        return view('moderation.appeals.create', [
            'suspension' => $userSuspension,
        ]);
    }

    public function store(SubmitSuspensionAppealRequest $request, UserSuspension $userSuspension): RedirectResponse
    {
        abort_unless((int) $userSuspension->user_id === (int) $request->user()->id, 403);

        try {
            $this->suspensionAppealService->submitAppeal(
                suspension: $userSuspension,
                user: $request->user(),
                reason: (string) $request->string('appeal_reason'),
                evidencePayload: $request->input('evidence_payload'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['appeal_reason' => $exception->getMessage()]);
        }

        return redirect()
            ->route('moderation.suspension-status')
            ->with('success', 'Appeal submitted successfully. Our moderators will review your request.');
    }
}
