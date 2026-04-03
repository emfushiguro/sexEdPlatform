<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleSaleLedger;
use App\Models\User;
use App\Services\AdminActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ModuleRevenueController extends Controller
{
    public function __construct(
        private readonly AdminActivityLogService $adminActivityLogService,
    ) {
    }

    public function index(Request $request)
    {
        $query = ModuleSaleLedger::query()->with(['module', 'instructor']);

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', (int) $request->integer('instructor_id'));
        }

        if ($request->filled('module_id')) {
            $query->where('module_id', (int) $request->integer('module_id'));
        }

        if ($request->filled('payout_status')) {
            $query->where('payout_status', (string) $request->string('payout_status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', (string) $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', (string) $request->string('date_to'));
        }

        $transactions = (clone $query)
            ->latest('occurred_at')
            ->get();

        $stats = [
            'total_module_sales' => $transactions->count(),
            'total_gross_revenue' => (float) $transactions->sum('gross_amount'),
            'total_platform_commission' => (float) $transactions->sum('commission_amount'),
            'total_instructor_earnings' => (float) $transactions->sum('instructor_earnings_amount'),
        ];

        $rollups = (clone $query)
            ->selectRaw('instructor_id, COUNT(*) as sales_count, SUM(gross_amount) as gross_amount, SUM(commission_amount) as commission_amount, SUM(instructor_earnings_amount) as earnings_amount')
            ->groupBy('instructor_id')
            ->with('instructor:id,name')
            ->orderByDesc('gross_amount')
            ->get();

        $instructors = User::query()
            ->where('role', 'instructor')
            ->orderBy('name')
            ->get(['id', 'name']);

        $modules = Module::query()
            ->where('access_type', 'paid')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.monetization.module-revenue', compact(
            'transactions',
            'stats',
            'rollups',
            'instructors',
            'modules'
        ));
    }

    public function updatePayoutStatus(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        $validated = $request->validate([
            'payout_status' => ['required', 'string', 'in:pending,payable,paid,reversed'],
            'payout_batch_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $currentStatus = (string) $moduleSaleLedger->payout_status;
        $nextStatus = (string) $validated['payout_status'];

        $allowedTransitions = [
            'pending' => ['payable'],
            'payable' => ['paid'],
            'paid' => [],
            'reversed' => [],
        ];

        if (!in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'payout_status' => 'Invalid payout transition requested.',
            ]);
        }

        $before = [
            'payout_status' => $currentStatus,
            'payout_batch_reference' => $moduleSaleLedger->payout_batch_reference,
        ];

        $moduleSaleLedger->payout_status = $nextStatus;
        if ($nextStatus === 'paid') {
            $moduleSaleLedger->payout_batch_reference = $validated['payout_batch_reference'] ?? $moduleSaleLedger->payout_batch_reference;
        }
        $moduleSaleLedger->save();

        $after = [
            'payout_status' => $moduleSaleLedger->payout_status,
            'payout_batch_reference' => $moduleSaleLedger->payout_batch_reference,
        ];

        $this->adminActivityLogService->logModelMutation(
            action: 'module_sale_ledger.payout_status.transition',
            entity: $moduleSaleLedger,
            before: $before,
            after: $after,
            meta: [
                'from_status' => $currentStatus,
                'to_status' => $nextStatus,
            ],
            request: $request,
        );

        return redirect()->route('admin.monetization.module-revenue.index')
            ->with('success', 'Payout status updated successfully.');
    }
}
