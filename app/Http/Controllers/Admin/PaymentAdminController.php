<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'subscription']);

        // Filter by status
        if ($request->filled('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Filter by payment method
        if ($request->filled('method') && $request->input('method') != 'all') {
            $query->where('method', $request->input('method'));
        }
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Search by user
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $payments = $query->latest()->paginate(15);
        
        // Payment statistics
        $stats = [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'completed' => Payment::where('status', 'completed')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'refunded' => Payment::where('status', 'refunded')->count(),
        ];
        
        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'subscription', 'refunds']);
        return view('admin.payments.show', compact('payment'));
    }

    public function processRefund(Request $request, Payment $payment)
    {
        if ($payment->status != 'completed') {
            return back()->with('error', 'Only completed payments can be refunded');
        }

        // Check 3-day refund policy
        if ($payment->paid_at && $payment->paid_at->diffInDays(now()) > 3) {
            return back()->with('error', 'Refund period (3 days) has expired');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // 1. Mark payment as refunded
            $payment->update([
                'status' => 'refunded',
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'refund_reason' => $request->reason,
                    'refunded_at' => now()->toDateTimeString(),
                    'refunded_by_admin' => \Illuminate\Support\Facades\Auth::id(),
                ]),
            ]);

            // 2. Immediately deactivate subscription
            if ($payment->subscription) {
                $payment->subscription->update([
                    'status' => 'cancelled',
                    'end_date' => now(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Payment refunded: ' . $request->reason,
                    'auto_renew' => false,
                ]);

                    Log::info('Subscription deactivated due to refund', [
                        'payment_id' => $payment->id,
                            'subscription_id' => optional($payment->subscription)->id,
                    'user_id' => $payment->user_id,
                    'reason' => $request->reason,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.payments.index')
                ->with('success', 'Payment refunded and subscription deactivated immediately');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund processing error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark payment as completed (admin override).
     * Uses SubscriptionService::activate() so the SubscriptionCreated event fires
     * (invoice + welcome email dispatched automatically).
     */
    public function markAsCompleted(Payment $payment)
    {
        if ($payment->status === 'completed') {
            return back()->with('info', 'Payment is already completed');
        }

        try {
            // Marking payment as completed triggers PaymentObserver which calls
            // SubscriptionService::activate() automatically.
            $payment->update([
                'status'  => 'completed',
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
