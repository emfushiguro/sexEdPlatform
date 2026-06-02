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

        $managedConnectors = Connector::query()
            ->with(['memberships' => fn ($query) => $query
                ->where('user_id', $user->id)
                ->with('role')])
            ->withCount(['memberships', 'invitations'])
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
            ->where('created_by', '!=', $user->id)
            ->whereDoesntHave('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'active']))
            ->latest()
            ->get();

        return view('connectors.index', [
            'managedConnectors' => $managedConnectors,
            'discoveryConnectors' => $discoveryConnectors,
            'hasConnectorAccess' => $managedConnectors->isNotEmpty(),
            'categories' => config('connector_permissions.categories', []),
        ]);
    }
}
