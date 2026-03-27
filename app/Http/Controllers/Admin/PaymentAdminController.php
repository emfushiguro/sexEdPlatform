<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AdminActivityLogService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'subscription']);

        $payments = $query->latest()->get();
        
        // Payment statistics
        $stats = [
            'total_revenue' => Payment::where('status', PaymentStatus::Completed)->sum('amount'),
            'completed' => Payment::where('status', PaymentStatus::Completed)->count(),
            'pending' => Payment::where('status', PaymentStatus::Pending)->count(),
            'processing' => Payment::where('status', PaymentStatus::Processing)->count(),
            'failed' => Payment::where('status', PaymentStatus::Failed)->count(),
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
            'refunds',
            'invoice',
        ]);

        return view('admin.payments.show', compact('payment'));
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
