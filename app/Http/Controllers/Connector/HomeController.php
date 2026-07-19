<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $managedConnectors = Connector::query()
            ->with(['memberships' => fn ($query) => $query
                ->where('user_id', $user->id)
                ->with('role')])
            ->withCount(['memberships', 'invitations', 'membershipRequests'])
            ->where(function ($query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhereHas('memberships', fn ($membershipQuery) => $membershipQuery
                        ->where('user_id', $user->id)
                        ->whereIn('status', ['pending', 'active']));
            })
            ->latest()
            ->get();

        $discoveryConnectors = Connector::query()
            ->verified()
            ->withCount('memberships')
            ->withExists([
                'memberships as user_is_member' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', 'active'),
                'membershipRequests as user_has_pending_request' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', 'pending'),
                'invitations as user_has_pending_invitation' => fn ($query) => $query
                    ->where('invited_user_id', $user->id)
                    ->where('status', 'pending'),
            ])
            ->where('created_by', '!=', $user->id)
            ->whereDoesntHave('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'active']))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address_line', 'like', "%{$search}%");
            }))
            ->latest()
            ->get();

        return view('connectors.index', [
            'managedConnectors' => $managedConnectors,
            'discoveryConnectors' => $discoveryConnectors,
            'hasConnectorAccess' => $managedConnectors->isNotEmpty(),
            'categories' => config('connector_permissions.categories', []),
            'search' => $search,
        ]);
    }

    public function show(Request $request, Connector $connector): View
    {
        abort_unless($connector->status === 'verified', 404);

        $user = $request->user();
        $connector->loadCount(['memberships' => fn ($query) => $query->where('status', 'active'), 'seminars']);

        $membershipState = match (true) {
            $connector->memberships()->where('user_id', $user->id)->where('status', 'active')->exists() => 'member',
            $connector->membershipRequests()->where('user_id', $user->id)->where('status', 'pending')->exists() => 'pending',
            $connector->invitations()->where('invited_user_id', $user->id)->where('status', 'pending')->exists() => 'invited',
            default => 'request',
        };

        return view('connectors.show', [
            'connector' => $connector,
            'categories' => config('connector_permissions.categories', []),
            'membershipState' => $membershipState,
        ]);
    }
}
