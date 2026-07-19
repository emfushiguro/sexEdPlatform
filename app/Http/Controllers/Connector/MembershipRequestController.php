<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\ConnectorMembershipRequest;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorMembershipRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MembershipRequestController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorMembershipRequestService $requests,
    ) {
    }

    public function store(Request $request, Connector $connector): RedirectResponse
    {
        $this->requests->request($connector, $request->user());

        return back()->with('success', 'Membership request sent.');
    }

    public function approve(Request $request, Connector $connector, ConnectorMembershipRequest $membershipRequest): RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_members');
        abort_unless($membershipRequest->connector_id === $connector->id && $membershipRequest->status === 'pending', 404);

        $this->requests->approve($membershipRequest->load('connector', 'user'), $request->user());

        return back()->with('success', 'Membership request approved.');
    }

    public function reject(Request $request, Connector $connector, ConnectorMembershipRequest $membershipRequest): RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_members');
        abort_unless($membershipRequest->connector_id === $connector->id && $membershipRequest->status === 'pending', 404);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:120'],
            'rejection_note' => ['required_if:rejection_reason,Other', 'nullable', 'string', 'max:1000'],
        ]);

        $this->requests->reject($membershipRequest->load('connector', 'user'), $request->user(), $data['rejection_reason'], $data['rejection_note'] ?? null);

        return back()->with('success', 'Membership request rejected.');
    }
}
