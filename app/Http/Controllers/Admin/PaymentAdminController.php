<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AdminActivityLogService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with([
            'user.learnerProfile',
            'subscription.plan',
            'modulePurchase.module.creator',
            'moduleSaleLedger.module.creator',
            'moduleSaleLedger.instructor',
            'moduleSaleLedger.learner',
        ])->whereNull('archived_at');

        $payments = $query->latest()->get();
        
        // Payment statistics
        $statsQuery = Payment::query()->whereNull('archived_at');

        $stats = [
            'total_revenue' => (clone $statsQuery)->where('status', PaymentStatus::Completed)->sum('amount'),
            'completed' => (clone $statsQuery)->where('status', PaymentStatus::Completed)->count(),
            'pending' => (clone $statsQuery)->where('status', PaymentStatus::Pending)->count(),
            'processing' => (clone $statsQuery)->where('status', PaymentStatus::Processing)->count(),
            'failed' => (clone $statsQuery)->where('status', PaymentStatus::Failed)->count(),
            'module_purchase' => (clone $statsQuery)->where('payment_details->payment_scope', 'module_purchase')->count(),
            'subscription_payment' => (clone $statsQuery)->where('payment_details->payment_scope', 'subscription')->count(),
        ];
        
        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'user.profile',
            'user.learnerProfile.city',
            'user.learnerProfile.barangay',
            'user.gamification',
            'subscription.plan',
            'subscription.planPrice',
            'modulePurchase.module.creator',
            'moduleSaleLedger.module.creator',
            'moduleSaleLedger.instructor',
            'moduleSaleLedger.learner',
            'refunds',
            'invoice',
        ]);

        return view('admin.payments.show', compact('payment'));
    }

    public function archive(Payment $payment): RedirectResponse
    {
        if ($payment->archived_at !== null) {
            return redirect()->back()->with('info', 'Payment is already archived.');
        }

        $payment->forceFill([
            'archived_at' => now(),
        ])->save();

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment archived successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        if ($payment->status === PaymentStatus::Completed) {
            return redirect()->back()->with('error', 'Completed payments cannot be permanently deleted.');
        }

        $payment->delete();

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment permanently deleted.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['user', 'subscription.plan']);

        return view('payments.receipt', compact('payment'));
    }

    /**
     * Mark payment as completed (admin override).
     * Uses SubscriptionService::activate() so the SubscriptionCreated event fires
     * (invoice + welcome email dispatched automatically).
     */
    public function markAsCompleted(Payment $payment)
    {
        $before = $payment->only(['id', 'status', 'paid_at', 'subscription_id']);

        if ($payment->status === PaymentStatus::Completed) {
            return back()->with('info', 'Payment is already completed');
        }

        try {
            // Marking payment as completed triggers PaymentObserver which calls
            // SubscriptionService::activate() automatically.
            $payment->update([
                'status'  => PaymentStatus::Completed,
                'paid_at' => now(),
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'manually_completed_by_admin' => true,
                    'admin_completed_at'          => now()->toDateTimeString(),
                ]),
            ]);

            // Safety net: activate directly via service in case PaymentObserver
            // was skipped (e.g. no linked subscription at observer time).
            if ($payment->subscription_id) {
                $subscription = $payment->subscription()->first();
                if ($subscription) {
                    app(SubscriptionService::class)->activate($subscription);
                }
            }

            app(AdminActivityLogService::class)->logModelMutation(
                action: 'payments.complete',
                entity: $payment,
                before: $before,
                after: $payment->fresh()->only(['id', 'status', 'paid_at', 'subscription_id']),
                meta: ['source' => 'admin.payments.complete'],
                request: request(),
            );

            return redirect()->route('admin.payments.index')
                ->with('success', 'Payment marked as completed and subscription activated!');

        } catch (\Exception $e) {
            Log::error('Admin markAsCompleted failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to complete payment: ' . $e->getMessage());
        }
    }

}
