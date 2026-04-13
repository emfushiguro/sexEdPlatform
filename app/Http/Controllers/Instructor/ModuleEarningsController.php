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
            ->with([
                'module:id,title,thumbnail',
                'learner:id,name,first_name,last_name',
                'learner.learnerProfile:id,user_id,avatar_path',
                'payment:id,transaction_id,method,status,paid_at',
                'modulePurchase:id,module_id,purchased_at,status',
            ])
            ->latest('occurred_at')
            ->paginate(15)
            ->withQueryString();

        $last7DaysBaseQuery = (clone $baseQuery)
            ->where('occurred_at', '>=', now()->subDays(7));

        $stats = [
            'total_sales' => (clone $baseQuery)->count(),
            'gross_revenue' => (float) (clone $baseQuery)->sum('gross_amount'),
            'platform_commission' => (float) (clone $baseQuery)->sum('commission_amount'),
            'net_earnings' => (float) (clone $baseQuery)->sum('instructor_earnings_amount'),
            'last_7_days_sales' => (clone $last7DaysBaseQuery)->count(),
            'last_7_days_earnings' => (float) (clone $last7DaysBaseQuery)->sum('instructor_earnings_amount'),
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
            'module:id,title,thumbnail,description,price_amount,price_currency',
            'learner:id,name,first_name,last_name,email',
            'learner.learnerProfile:id,user_id,avatar_path,username,barangay,barangay_code,city_code',
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
        return $this->hideTransactionFromInstructor(
            request: $request,
            moduleSaleLedger: $moduleSaleLedger,
            defaultReason: 'hidden_by_instructor',
            successMessage: 'Sale row hidden from your earnings list.'
        );
    }

    public function archive(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        return $this->hideTransactionFromInstructor(
            request: $request,
            moduleSaleLedger: $moduleSaleLedger,
            defaultReason: 'archived_by_instructor',
            successMessage: 'Transaction archived from your earnings list.'
        );
    }

    public function delete(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        return $this->hideTransactionFromInstructor(
            request: $request,
            moduleSaleLedger: $moduleSaleLedger,
            defaultReason: 'deleted_by_instructor',
            successMessage: 'Transaction removed from your earnings list.'
        );
    }

    private function hideTransactionFromInstructor(
        Request $request,
        ModuleSaleLedger $moduleSaleLedger,
        string $defaultReason,
        string $successMessage,
    )
    {
        $instructorId = (int) $request->user()->id;

        abort_unless((int) $moduleSaleLedger->instructor_id === $instructorId, 404);

        $deleteReason = $request->string('delete_reason')->toString();
        if ($deleteReason === '') {
            $deleteReason = $defaultReason;
        }

        InstructorEarningsVisibility::query()->updateOrCreate(
            [
                'module_sale_ledger_id' => $moduleSaleLedger->id,
                'instructor_id' => $instructorId,
            ],
            [
                'deleted_at' => now(),
                'deleted_by' => $instructorId,
                'delete_reason' => $deleteReason,
            ]
        );

        return redirect()->route('instructor.earnings.index')
            ->with('success', $successMessage);
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
