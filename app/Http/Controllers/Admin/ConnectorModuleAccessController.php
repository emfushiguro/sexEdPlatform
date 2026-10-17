<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManageConnectorModuleAccessRequest;
use App\Models\AdminActivityLog;
use App\Models\Connector;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModuleSeatAssignment;
use App\Services\Connectors\ConnectorModuleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConnectorModuleAccessController extends Controller
{
    public function __construct(private readonly ConnectorModuleAccessService $access) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $filters = $request->validate([
            'connector_id' => ['nullable', 'integer', 'min:1'],
            'entitlement_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:200'],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'membership' => ['nullable', 'string', 'max:32'],
            'access' => ['nullable', 'in:with,without,pending,revoked'],
        ]);
        $connectors = Connector::query()->orderBy('name')->get(['id', 'name', 'status']);
        $connector = ! empty($filters['connector_id']) ? Connector::findOrFail($filters['connector_id']) : null;
        $entitlements = $connector?->connectorModuleEntitlements()->with('module')->latest('id')->get() ?? collect();
        $entitlement = ! empty($filters['entitlement_id']) ? $entitlements->firstWhere('id', $filters['entitlement_id']) : null;
        abort_if(! empty($filters['entitlement_id']) && ! $entitlement, 404);
        // shortcut: load the roster for shared eligibility counts; move filters to SQL for very large organizations.
        $roster = $entitlement ? $this->access->managementRoster($entitlement) : collect();
        $counts = [
            'eligible' => $roster->where('canGrant', true)->count(),
            'with' => $roster->where('hasAccess', true)->count(),
            'without' => $roster->where('canGrant', true)->where('hasAccess', false)->count(),
            'used' => $entitlement?->assignments()->where('consumes_seat', true)->whereNull('released_at')
                ->whereIn('status', ['active', 'pending_guardian'])->count() ?? 0,
        ];
        $filtered = $roster->filter(function (array $row) use ($filters): bool {
            $search = mb_strtolower(trim($filters['search'] ?? ''));

            return ($search === '' || str_contains(mb_strtolower($row['user']->name.' '.$row['user']->email), $search))
                && (empty($filters['role_id']) || (int) $row['membership']?->connector_role_id === (int) $filters['role_id'])
                && (empty($filters['membership']) || ($row['membership']?->status ?? 'removed') === $filters['membership'])
                && match ($filters['access'] ?? '') {
                    'with' => $row['hasAccess'], 'without' => ! $row['hasAccess'],
                    'pending' => $row['assignment']?->status === 'pending_guardian',
                    'revoked' => $row['assignment']?->status === 'revoked', default => true,
                };
        })->values();
        $members = new LengthAwarePaginator($filtered->forPage(LengthAwarePaginator::resolveCurrentPage(), 50)->values(), $filtered->count(), 50, null, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);
        $history = AdminActivityLog::query()->with('adminUser')
            ->where('entity_type', ConnectorModuleSeatAssignment::class)
            ->where('meta_json->entitlement_id', $entitlement?->id ?? 0)
            ->latest('id')->paginate(20, ['*'], 'history_page')->withQueryString();

        return view('admin.connectors.module-access.index', compact('connectors', 'connector', 'entitlements', 'entitlement', 'members', 'counts', 'filters', 'history') + [
            'roles' => $connector?->roles()->orderBy('name')->get() ?? collect(),
            'membershipStatuses' => $roster->map(fn (array $row) => $row['membership']?->status ?? 'removed')->unique()->sort(),
        ]);
    }

    public function review(ManageConnectorModuleAccessRequest $request, Connector $connector, ConnectorModuleEntitlement $entitlement): View|RedirectResponse
    {
        abort_unless((int) $entitlement->connector_id === (int) $connector->id, 404);
        $data = $request->validated();
        try {
            $selected = $this->access->reviewMembers($entitlement, $request->user(), $data['action'], $data['user_ids']);
        } catch (ValidationException $exception) {
            return redirect()->route('admin.connectors.module-access.index', ['connector_id' => $connector->id, 'entitlement_id' => $entitlement->id])
                ->withErrors($exception->errors());
        }

        return view('admin.connectors.module-access.review', compact('connector', 'entitlement', 'data', 'selected'));
    }

    public function update(ManageConnectorModuleAccessRequest $request, Connector $connector, ConnectorModuleEntitlement $entitlement): RedirectResponse
    {
        abort_unless((int) $entitlement->connector_id === (int) $connector->id, 404);
        $data = $request->validated();
        try {
            $this->access->manageMembers($entitlement, $request->user(), $data['action'], $data['user_ids']);
        } catch (ValidationException $exception) {
            return redirect()->route('admin.connectors.module-access.index', ['connector_id' => $connector->id, 'entitlement_id' => $entitlement->id])
                ->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $exception;
            }
            report($exception);

            return redirect()->route('admin.connectors.module-access.index', ['connector_id' => $connector->id, 'entitlement_id' => $entitlement->id])
                ->withErrors(['operation' => 'The request could not be completed. Reload the roster to confirm current access before retrying.']);
        }

        return redirect()->route('admin.connectors.module-access.index', ['connector_id' => $connector->id, 'entitlement_id' => $entitlement->id])
            ->with('success', 'Selected users processed successfully. Guardian approval requirements and independent access remain in effect.');
    }
}
