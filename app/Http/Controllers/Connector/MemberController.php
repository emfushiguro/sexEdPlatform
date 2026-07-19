<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\ConnectorMembership;
use App\Models\ConnectorRole;
use App\Models\User;
use App\Notifications\Connectors\ConnectorRoleUpdatedNotification;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorInvitationService;
use App\Services\Connectors\ConnectorRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorInvitationService $invitations,
        private readonly ConnectorRoleService $roles,
    ) {
    }

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        $canManageMembers = $this->access->hasPermission($request->user(), $connector, 'connector.manage_members');

        return view('connectors.members.index', [
            'connector' => $connector->load(['memberships' => fn ($query) => $query->where('status', 'active')->with(['user.learnerProfile', 'user.instructorProfile', 'role']), 'invitations.invitedUser.learnerProfile', 'invitations.invitedUser.instructorProfile', 'invitations.inviter', 'invitations.role', 'membershipRequests.user.learnerProfile', 'membershipRequests.user.instructorProfile', 'roles']),
            'removedMembersCount' => $connector->memberships()->where('status', 'removed')->count(),
            'canManageMembers' => $canManageMembers,
            'inviteCandidates' => $canManageMembers ? User::query()
                ->select(['id', 'name', 'email', 'role'])
                ->with(['learnerProfile:id,user_id,avatar_path', 'instructorProfile:id,user_id,profile_photo_path'])
                ->whereDoesntHave('connectorMemberships', fn ($query) => $query
                    ->where('connector_id', $connector->id)
                    ->whereIn('status', ['pending', 'active']))
                ->whereDoesntHave('connectorMembershipRequests', fn ($query) => $query
                    ->where('connector_id', $connector->id)
                    ->where('status', 'pending'))
                ->orderBy('name')
                ->limit(75)
                ->get() : collect(),
        ]);
    }

    public function removed(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_members');

        return view('connectors.members.removed', [
            'connector' => $connector->load(['memberships' => fn ($query) => $query->where('status', 'removed')->with(['user.learnerProfile', 'user.instructorProfile', 'role'])->latest('removed_at'), 'roles']),
        ]);
    }

    public function updateRole(Request $request, Connector $connector, ConnectorMembership $membership): RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_members');
        abort_unless($membership->connector_id === $connector->id, 404);

        $data = $request->validate(['connector_role_id' => ['required', 'integer', 'exists:connector_roles,id']]);
        $role = ConnectorRole::where('connector_id', $connector->id)->findOrFail($data['connector_role_id']);

        $this->roles->assertCanChangeMembershipRole($membership->loadMissing('role'), $role);
        $membership->update(['connector_role_id' => $role->id]);
        $membership->refresh()->load(['connector', 'role', 'user']);
        $membership->user?->notify(new ConnectorRoleUpdatedNotification($membership));

        return back()->with('success', 'Member role updated.');
    }

    public function destroy(Request $request, Connector $connector, ConnectorMembership $membership): RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_members');
        abort_unless($membership->connector_id === $connector->id, 404);

        $this->invitations->remove($membership->loadMissing('role'));

        return back()->with('success', 'Member removed.');
    }
}
