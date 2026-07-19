<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\ConnectorInvitation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationInboxController extends Controller
{
    public function index(Request $request): View
    {
        return view('connectors.invitations.index', [
            'invitations' => ConnectorInvitation::query()
                ->with(['connector', 'role', 'inviter'])
                ->where('invited_user_id', $request->user()->id)
                ->where('status', 'pending')
                ->latest()
                ->paginate(12),
        ]);
    }
}
