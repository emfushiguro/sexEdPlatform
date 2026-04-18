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
        $query = ModuleSaleLedger::query()->with([
            'module:id,title,thumbnail,created_by',
            'instructor:id,name,email',
            'instructor.instructorProfile:id,user_id,profile_photo_path',
            'learner:id,name,email',
            'learner.learnerProfile:id,user_id,avatar_path',
            'payment:id,transaction_id,method,status,paid_at',
            'modulePurchase:id,module_id,purchased_at,status',
        ])->where('sale_status', '!=', 'archived');

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

        $statsQuery = clone $query;

        $stats = [
            'total_transactions' => (clone $statsQuery)->count(),
            'total_module_revenue' => (float) (clone $statsQuery)->sum('gross_amount'),
            'total_platform_commission' => (float) (clone $statsQuery)->sum('commission_amount'),
            'total_instructor_earnings' => (float) (clone $statsQuery)->sum('instructor_earnings_amount'),
            'total_modules_sold' => (clone $statsQuery)->whereNotNull('module_id')->distinct('module_id')->count('module_id'),
        ];

        $transactions = (clone $query)
            ->latest('occurred_at')
            ->paginate(15)
            ->withQueryString();

        $rollups = (clone $query)
            ->selectRaw('instructor_id, COUNT(*) as sales_count, SUM(gross_amount) as gross_amount, SUM(commission_amount) as commission_amount, SUM(instructor_earnings_amount) as earnings_amount')
            ->groupBy('instructor_id')
            ->with([
                'instructor:id,name,email',
                'instructor.instructorProfile:id,user_id,profile_photo_path',
            ])
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

    public function showTransaction(ModuleSaleLedger $moduleSaleLedger)
    {
        $transaction = ModuleSaleLedger::query()
            ->with([
                'module:id,title,thumbnail,created_by',
                'instructor:id,name,email',
                'instructor.instructorProfile:id,user_id,profile_photo_path',
                'learner:id,name,email',
                'learner.learnerProfile:id,user_id,avatar_path',
                'payment:id,user_id,transaction_id,method,status,paid_at,payment_details,amount',
                'modulePurchase:id,module_id,purchased_at,status,amount,currency',
            ])
            ->findOrFail($moduleSaleLedger->id);

        return view('admin.monetization.module-revenue-transaction-show', [
            'transaction' => $transaction,
        ]);
    }

    public function showInstructor(Request $request, User $instructor)
    {
        $query = ModuleSaleLedger::query()
            ->where('instructor_id', $instructor->id)
            ->where('sale_status', '!=', 'archived')
            ->with([
                'module:id,title,thumbnail,created_by',
                'payment:id,transaction_id,method,status,paid_at',
                'learner:id,name,email',
                'learner.learnerProfile:id,user_id,avatar_path',
                'modulePurchase:id,module_id,purchased_at,status',
            ]);

        if ($request->filled('module_id')) {
            $query->where('module_id', (int) $request->integer('module_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', (string) $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', (string) $request->string('date_to'));
        }

        $statsQuery = clone $query;
        $stats = [
            'total_transactions' => (clone $statsQuery)->count(),
            'total_module_revenue' => (float) (clone $statsQuery)->sum('gross_amount'),
            'total_platform_commission' => (float) (clone $statsQuery)->sum('commission_amount'),
            'total_instructor_earnings' => (float) (clone $statsQuery)->sum('instructor_earnings_amount'),
        ];

        $transactions = (clone $query)
            ->latest('occurred_at')
            ->paginate(15)
            ->withQueryString();

        $modules = Module::query()
            ->where('created_by', $instructor->id)
            ->where('access_type', 'paid')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.monetization.module-revenue-instructor-show', [
            'instructor' => $instructor,
            'transactions' => $transactions,
            'stats' => $stats,
            'modules' => $modules,
        ]);
    }

    public function archive(ModuleSaleLedger $moduleSaleLedger)
    {
        $moduleSaleLedger->forceFill([
            'sale_status' => 'archived',
        ])->save();

        return redirect()->route('admin.monetization.module-revenue.index')
            ->with('success', 'Transaction archived successfully.');
    }

    public function destroy(ModuleSaleLedger $moduleSaleLedger)
    {
        $moduleSaleLedger->delete();

        return redirect()->route('admin.monetization.module-revenue.index')
            ->with('success', 'Transaction deleted successfully.');
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
