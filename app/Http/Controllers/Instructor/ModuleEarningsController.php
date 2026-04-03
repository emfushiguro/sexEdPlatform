<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorEarningsVisibility;
use App\Models\ModuleSaleLedger;
use App\Models\User;
use App\Services\Monetization\CommissionPolicyResolver;
use Illuminate\Http\Request;
use RuntimeException;

class ModuleEarningsController extends Controller
{
    public function __construct(
        private readonly CommissionPolicyResolver $commissionPolicyResolver,
    ) {
    }

    public function index(Request $request)
    {
        $instructorId = (int) $request->user()->id;

        $baseQuery = ModuleSaleLedger::query()
            ->forInstructor($instructorId)
            ->whereDoesntHave('visibility', function ($visibilityQuery) use ($instructorId) {
                $visibilityQuery
                    ->where('instructor_id', $instructorId)
                    ->whereNotNull('deleted_at');
            });

        $transactions = (clone $baseQuery)
            ->with(['module:id,title', 'learner:id,name,first_name,last_name'])
            ->latest('occurred_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_sales' => (clone $baseQuery)->count(),
            'gross_revenue' => (float) (clone $baseQuery)->sum('gross_amount'),
            'platform_commission' => (float) (clone $baseQuery)->sum('commission_amount'),
            'net_earnings' => (float) (clone $baseQuery)->sum('instructor_earnings_amount'),
        ];

        $effectiveCommissionPolicy = $this->resolveEffectiveCommissionPolicyPayload($request->user());

        return view('instructor.earnings.index', compact('transactions', 'stats', 'effectiveCommissionPolicy'));
    }

    public function show(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        $instructorId = (int) $request->user()->id;

        abort_unless((int) $moduleSaleLedger->instructor_id === $instructorId, 404);

        $isHidden = $moduleSaleLedger->visibility()
            ->where('instructor_id', $instructorId)
            ->whereNotNull('deleted_at')
            ->exists();

        abort_if($isHidden, 404);

        $moduleSaleLedger->load([
            'module:id,title',
            'learner:id,name,first_name,last_name,email',
            'payment:id,transaction_id,paid_at,method,status',
            'modulePurchase:id,module_id,purchased_at,status',
        ]);

        return view('instructor.earnings.show', [
            'ledger' => $moduleSaleLedger,
            'effectiveCommissionPolicy' => $this->resolveEffectiveCommissionPolicyPayload($request->user()),
        ]);
    }

    public function destroyVisibility(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        $instructorId = (int) $request->user()->id;

        abort_unless((int) $moduleSaleLedger->instructor_id === $instructorId, 404);

        InstructorEarningsVisibility::query()->updateOrCreate(
            [
                'module_sale_ledger_id' => $moduleSaleLedger->id,
                'instructor_id' => $instructorId,
            ],
            [
                'deleted_at' => now(),
                'deleted_by' => $instructorId,
                'delete_reason' => $request->string('delete_reason')->toString() ?: null,
            ]
        );

        return redirect()->route('instructor.earnings.index')
            ->with('success', 'Sale row hidden from your earnings list.');
    }

    private function resolveEffectiveCommissionPolicyPayload(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        try {
            $policy = $this->commissionPolicyResolver->resolveForInstructor((int) $user->id);

            return [
                'commission_percent' => (float) $policy->commission_percent,
                'tax_basis' => (string) $policy->tax_basis,
                'refund_policy' => (string) $policy->refund_policy,
            ];
        } catch (RuntimeException) {
            return null;
        }
    }
}
