<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RefundService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentAdminController extends Controller
{
    public function __construct(
        protected RefundService $refundService,
        protected SubscriptionService $subscriptionService
    ) {}

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
            'total_revenue' => Payment::where('status', PaymentStatus::Completed)->sum('amount'),
            'completed' => Payment::where('status', PaymentStatus::Completed)->count(),
            'failed' => Payment::where('status', PaymentStatus::Failed)->count(),
            'refunded' => Payment::where('status', PaymentStatus::Refunded)->count(),
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
        if ($payment->status !== PaymentStatus::Completed) {
            return back()->with('error', 'Only completed payments can be refunded.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            // bypassTimeLimit: true — admins can refund beyond the 3-day user window
            $refund = $this->refundService->processRefund(
                payment: $payment,
                amount: null,          // full refund
                reason: $request->reason,
                adminNotes: 'Refund initiated by admin: ' . Auth::user()->name,
                bypassTimeLimit: true
            );

            $statusMsg = match ($refund->status) {
                'completed'         => 'Refund processed successfully via PayMongo. Subscription deactivated.',
                'manual_processing' => 'Refund has been logged for manual processing (no PayMongo payment ID found). Please process the refund directly in your PayMongo dashboard.',
                default             => 'Refund recorded with status: ' . $refund->status,
            };

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', $statusMsg);

        } catch (\RuntimeException $e) {
            // Duplicate refund or business rule violation
            return back()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Admin refund failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
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
