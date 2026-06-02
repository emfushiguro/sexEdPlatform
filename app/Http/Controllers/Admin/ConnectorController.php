<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveConnectorRequest;
use App\Http\Requests\Admin\RejectConnectorRequest;
use App\Http\Requests\Admin\SuspendConnectorRequest;
use App\Models\Connector;
use App\Notifications\Connectors\ConnectorModerationDecisionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConnectorController extends Controller
{
    public function index(Request $request): View
    {
        $connectors = Connector::query()
            ->with(['primaryRepresentative', 'creator', 'reviewer'])
            ->withCount(['memberships', 'invitations'])
            ->latest()
            ->get();

        return view('admin.connectors.index', [
            'connectors' => $connectors,
            'counts' => [
                'total' => Connector::count(),
                'pending' => Connector::where('status', 'pending')->count(),
                'verified' => Connector::where('status', 'verified')->count(),
                'rejected' => Connector::where('status', 'rejected')->count(),
                'suspended' => Connector::where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function show(Connector $connector): View
    {
        return view('admin.connectors.show', [
            'connector' => $connector->load(['primaryRepresentative', 'creator', 'reviewer', 'memberships.user', 'memberships.role', 'reviews.reviewer']),
        ]);
    }

    public function approve(ApproveConnectorRequest $request, Connector $connector): RedirectResponse
    {
        DB::transaction(function () use ($request, $connector) {
            $from = $connector->status;

            $connector->update([
                'status' => 'verified',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'suspended_at' => null,
            ]);

            $connector->memberships()
                ->where('user_id', $connector->primary_representative_user_id)
                ->where('status', 'pending')
                ->update(['status' => 'active', 'accepted_at' => now()]);

            $connector->reviews()->create([
                'reviewed_by' => $request->user()->id,
                'from_status' => $from,
                'to_status' => 'verified',
                'reason' => $request->input('reason') ?: 'Connector approved.',
                'reviewed_at' => now(),
            ]);
        });

        $connector->primaryRepresentative?->notify(
            new ConnectorModerationDecisionNotification($connector->fresh(), 'verified')
        );

        return back()->with('success', 'Connector approved.');
    }

    public function reject(RejectConnectorRequest $request, Connector $connector): RedirectResponse
    {
        DB::transaction(function () use ($request, $connector) {
            $from = $connector->status;
            $reason = $request->string('reason')->toString();

            $connector->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $connector->reviews()->create([
                'reviewed_by' => $request->user()->id,
                'from_status' => $from,
                'to_status' => 'rejected',
                'reason' => $reason,
                'reviewed_at' => now(),
            ]);
        });

        $connector->primaryRepresentative?->notify(
            new ConnectorModerationDecisionNotification($connector->fresh(), 'rejected')
        );

        return back()->with('success', 'Connector rejected.');
    }

    public function suspend(SuspendConnectorRequest $request, Connector $connector): RedirectResponse
    {
        DB::transaction(function () use ($request, $connector) {
            $from = $connector->status;
            $reason = $request->string('reason')->toString();

            $connector->update([
                'status' => 'suspended',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'suspended_at' => now(),
            ]);

            $connector->reviews()->create([
                'reviewed_by' => $request->user()->id,
                'from_status' => $from,
                'to_status' => 'suspended',
                'reason' => $reason,
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', 'Connector suspended.');
    }
}
