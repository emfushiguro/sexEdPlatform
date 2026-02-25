<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generateInvoice(Payment $payment): Invoice
    {
        $invoice = Invoice::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $payment->amount,
            'tax_amount' => 0, // Add tax calculation if needed
            'total_amount' => $payment->amount,
            'status' => $payment->status === 'completed' ? 'paid' : 'pending',
            'items' => [
                [
                    'description' => $this->getSubscriptionDescription($payment),
                    'quantity' => 1,
                    'unit_price' => $payment->amount,
                    'total' => $payment->amount
                ]
            ]
        ]);

        // Generate PDF
        $this->generateInvoicePDF($invoice);

        return $invoice;
    }

    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Get the last invoice number for this month
        $lastInvoice = Invoice::whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->orderBy('id', 'desc')
            ->first();
            
        $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;
        
        return sprintf('INV-%s%s-%04d', $year, $month, $sequence);
    }

    private function getSubscriptionDescription(Payment $payment): string
    {
        $subscription = $payment->subscription;
        $plan = ucfirst($subscription->plan);
        
        return "{$plan} Subscription - {$subscription->start_date->format('M d, Y')} to {$subscription->end_date->format('M d, Y')}";
    }

    public function generateInvoicePDF(Invoice $invoice): string
    {
        // Check if DomPDF is available
        if (!class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            // Fallback: Generate HTML invoice and save as .html file
            return $this->generateInvoiceHTML($invoice);
        }

        $data = [
            'invoice' => $invoice,
            'payment' => $invoice->payment,
            'user' => $invoice->user,
            'subscription' => $invoice->payment->subscription,
            'company' => [
                'name' => config('billing.invoicing.company_details.name', config('app.name')),
                'address' => config('billing.invoicing.company_details.address', ''),
                'tin' => config('billing.invoicing.company_details.tin', ''),
                'email' => config('billing.invoicing.company_details.email', config('mail.from.address')),
                'phone' => config('billing.invoicing.company_details.phone', ''),
                'website' => config('billing.invoicing.company_details.website', config('app.url', ''))
            ]
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.template', $data);
        
        $filename = "invoice-{$invoice->invoice_number}.pdf";
        $path = "invoices/{$filename}";
        
        Storage::disk('private')->put($path, $pdf->output());
        
        $invoice->update(['pdf_path' => $path]);
        
        return $path;
    }

    private function generateInvoiceHTML(Invoice $invoice): string
    {
        $data = [
            'invoice' => $invoice,
            'payment' => $invoice->payment,
            'user' => $invoice->user,
            'subscription' => $invoice->payment->subscription,
            'company' => [
                'name' => config('billing.invoicing.company_details.name', config('app.name')),
                'address' => config('billing.invoicing.company_details.address', ''),
                'tin' => config('billing.invoicing.company_details.tin', ''),
                'email' => config('billing.invoicing.company_details.email', config('mail.from.address')),
                'phone' => config('billing.invoicing.company_details.phone', ''),
                'website' => config('billing.invoicing.company_details.website', config('app.url', ''))
            ]
        ];

        $html = view('invoices.template', $data)->render();
        
        $filename = "invoice-{$invoice->invoice_number}.html";
        $path = "invoices/{$filename}";
        
        Storage::disk('private')->put($path, $html);
        
        $invoice->update(['pdf_path' => $path]);
        
        return $path;
    }

    public function sendInvoiceEmail(Invoice $invoice): void
    {
        // TODO: Implement email sending with invoice PDF attached
        // Mail::to($invoice->user->email)->send(new InvoiceMail($invoice));
    }
}