<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Show payment form
     */
    public function create(Subscription $subscription)
    {
        // Verify subscription belongs to authenticated user
        if ($subscription->user_id !== auth()->id()) {
            abort(403);
        }

        // Calculate amount based on plan
        $amount = $subscription->plan === 'monthly' ? 199.00 : 1999.00;

        return view('payments.create', compact('subscription', 'amount'));
    }

    /**
     * Process payment via Paymongo
     * 
     * NOTE: This is a basic implementation. 
     * For production, integrate with actual Paymongo API
     */
    public function process(Request $request, Subscription $subscription)
    {
        $request->validate([
            'payment_method' => 'required|in:gcash,paymaya,card',
        ]);

        // Verify subscription belongs to authenticated user
        if ($subscription->user_id !== auth()->id()) {
            abort(403);
        }

        DB::beginTransaction();

        try {
            // Calculate amount
            $amount = $subscription->plan === 'monthly' ? 199.00 : 1999.00;

            // Create payment record
            $payment = Payment::create([
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'transaction_id' => 'TXN-' . Str::upper(Str::random(12)),
                'payment_details' => [
                    'plan' => $subscription->plan,
                    'payment_method' => $request->payment_method,
                ],
            ]);

            // TODO: Integrate with Paymongo API
            // For now, we'll simulate successful payment in development
            // In production, redirect to Paymongo payment page and handle webhook

            DB::commit();

            return redirect()->route('payment.pending', ['payment' => $payment->id])
                ->with('info', 'Please complete the payment process.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to process payment. Please try again.');
        }
    }

    /**
     * Show pending payment status
     */
    public function pending(Payment $payment)
    {
        // Verify payment belongs to authenticated user
        if ($payment->subscription->user_id !== auth()->id()) {
            abort(403);
        }

        return view('payments.pending', compact('payment'));
    }

    /**
     * Simulate payment success (for development only)
     * In production, this would be handled by Paymongo webhook
     */
    public function simulateSuccess(Payment $payment)
    {
        // Only allow in development
        if (!app()->environment('local')) {
            abort(404);
        }

        DB::beginTransaction();

        try {
            // Update payment status
            $payment->update([
                'payment_status' => 'completed',
                'paid_at' => now(),
            ]);

            // Activate subscription
            $subscription = $payment->subscription;
            $subscription->update([
                'status' => 'active',
            ]);

            DB::commit();

            return redirect()->route('subscription.index')
                ->with('success', 'Payment successful! Your premium subscription is now active.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to process payment. Please try again.');
        }
    }

    /**
     * Payment webhook handler for Paymongo
     * 
     * This will handle payment notifications from Paymongo
     */
    public function webhook(Request $request)
    {
        // TODO: Implement Paymongo webhook verification and handling
        // 1. Verify webhook signature
        // 2. Extract payment information
        // 3. Update payment and subscription status
        // 4. Send confirmation email

        return response()->json(['success' => true]);
    }

    /**
     * Show payment history
     */
    public function history()
    {
        $payments = auth()->user()->payments()
            ->with('subscription')
            ->latest()
            ->paginate(10);

        return view('payments.history', compact('payments'));
    }

    /**
     * Show payment receipt
     */
    public function receipt(Payment $payment)
    {
        // Verify payment belongs to authenticated user
        if ($payment->subscription->user_id !== auth()->id()) {
            abort(403);
        }

        return view('payments.receipt', compact('payment'));
    }
}
