<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\InviteConnectorMemberRequest;
use App\Models\Connector;
use App\Models\ConnectorInvitation;
use App\Models\ConnectorRole;
use App\Models\User;
use App\Services\Connectors\ConnectorInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function __construct(private readonly ConnectorInvitationService $invitations)
    {
    }

    public function store(InviteConnectorMemberRequest $request, Connector $connector): RedirectResponse
    {
        $target = User::where('email', $request->string('email'))->firstOrFail();
        $role = ConnectorRole::where('connector_id', $connector->id)->findOrFail($request->integer('connector_role_id'));

        $this->invitations->invite($connector, $request->user(), $target, $role);

        return back()->with('success', 'Invitation sent.');
    }

    public function accept(Request $request, Connector $connector, ConnectorInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->connector_id === $connector->id, 404);

        $this->invitations->accept($invitation, $request->user());

        return redirect()->route('connector.dashboard', $connector)->with('success', 'Invitation accepted.');
    }

    public function reject(Request $request, Connector $connector, ConnectorInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->connector_id === $connector->id, 404);

        $this->invitations->reject($invitation, $request->user());

        return redirect()->route('connector.status', $connector)->with('success', 'Invitation rejected.');
    }

    public function resend(Request $request, Connector $connector, ConnectorInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->connector_id === $connector->id, 404);

        $this->invitations->resend($invitation->loadMissing(['connector', 'inviter', 'invitedUser']), $request->user());

        return back()->with('success', 'Invitation resent.');
    }

    public function destroy(Request $request, Connector $connector, ConnectorInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->connector_id === $connector->id, 404);

        $this->invitations->cancel($invitation->loadMissing('connector'), $request->user());

        return back()->with('success', 'Invitation cancelled.');
    }
}
