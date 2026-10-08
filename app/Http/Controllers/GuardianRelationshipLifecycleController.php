<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeactivateGuardianRelationshipRequest;
use App\Models\ParentChildAccount;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuardianRelationshipLifecycleController extends Controller
{
    public function __construct(private readonly GuardianRelationshipVerificationService $service)
    {
    }

    public function deactivate(
        DeactivateGuardianRelationshipRequest $request,
        ParentChildAccount $parentChildAccount,
    ): RedirectResponse {
        $this->service->deactivate(
            $parentChildAccount,
            $request->user(),
            $request->validated('note'),
        );

        return back()->with('success', 'Guardian relationship deactivated.');
    }

    public function reactivate(Request $request, ParentChildAccount $parentChildAccount): RedirectResponse
    {
        abort_unless(
            in_array((int) $request->user()->id, [
                (int) $parentChildAccount->parent_user_id,
                (int) $parentChildAccount->child_user_id,
            ], true) || $request->user()->hasRole('admin'),
            403,
        );

        $this->service->requestReactivation($parentChildAccount, $request->user());

        return back()->with('success', 'Relationship reactivation submitted for administrative review.');
    }
}
