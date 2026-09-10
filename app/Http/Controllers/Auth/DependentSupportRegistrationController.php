<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\DependentSupport\StoreDependentSupportInformationRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\DependentSupportInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DependentSupportRegistrationController extends Controller
{
    public const SESSION_KEY = 'pending_child_support_setup';

    public const MARKER_MINUTES = 30;

    public function __construct(
        private readonly DependentSupportInformationService $supportInformation,
    ) {}

    public function show(Request $request): Response
    {
        [$relationship, $dependent] = $this->context($request);

        return response()
            ->view('auth.child.step6-support-information', compact('relationship', 'dependent'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(StoreDependentSupportInformationRequest $request): RedirectResponse
    {
        [$relationship] = $this->context($request);
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);

        if ($request->boolean('has_relevant_support_information')) {
            $this->supportInformation->saveDuringRegistration(
                $relationship,
                $guardian,
                $request->supportPayload(),
            );
        }

        return $this->finish($request, $request->boolean('has_relevant_support_information'));
    }

    public function skip(Request $request): RedirectResponse
    {
        $this->context($request);

        return $this->finish($request, false);
    }

    private function context(Request $request): array
    {
        $guardian = $request->user();
        $marker = $request->session()->get(self::SESSION_KEY);

        abort_unless($guardian instanceof User
            && $guardian->status === User::STATUS_ACTIVE
            && $guardian->isParentVerificationApproved()
            && $guardian->hasCompletedGuardianOnboarding()
            && is_array($marker)
            && (int) ($marker['expires_at'] ?? 0) >= now()->timestamp, 404);

        $relationship = ParentChildAccount::query()
            ->whereKey((int) ($marker['parent_child_account_id'] ?? 0))
            ->where('parent_user_id', $guardian->id)
            ->where('child_user_id', (int) ($marker['dependent_user_id'] ?? 0))
            ->firstOrFail();

        abort_if(in_array($relationship->relationship_status, [
            ParentChildAccount::STATUS_REJECTED,
            ParentChildAccount::STATUS_REVOKED,
            ParentChildAccount::STATUS_INACTIVE,
        ], true), 404);

        return [$relationship, $relationship->child()->firstOrFail()];
    }

    private function finish(Request $request, bool $saved): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('parent.create-child.done')->with(
            'support_information_result',
            $saved ? 'saved' : 'skipped',
        );
    }
}
