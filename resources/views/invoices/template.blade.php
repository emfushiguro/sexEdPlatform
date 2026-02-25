<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        .company-logo { font-size: 24px; font-weight: bold; color: #007bff; }
        .invoice-details { margin: 20px 0; }
        .two-column { display: flex; justify-content: space-between; }
        .column { width: 48%; }
        .invoice-meta { margin: 30px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background-color: #f8f9fa; }
        .totals { margin-top: 20px; text-align: right; }
        .total-line { margin: 5px 0; }
        .grand-total { font-weight: bold; font-size: 16px; border-top: 2px solid #007bff; padding-top: 10px; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status.paid { background-color: #d4edda; color: #155724; }
        .status.pending { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-logo">{{ $company['name'] }}</div>
        <div>{{ $company['address'] }}</div>
        @if($company['tin'])
        <div>TIN: {{ $company['tin'] }}</div>
        @endif
        <div>{{ $company['email'] }} | {{ $company['phone'] ?? '' }}</div>
    </div>

    <div class="invoice-details">
        <h2>INVOICE</h2>
        
        <div class="two-column">
            <div class="column">
                <h3>Bill To:</h3>
                <strong>{{ $user->first_name }} {{ $user->last_name }}</strong><br>
                {{ $user->email }}<br>
                @if($user->phone)
                    {{ $user->phone }}<br>
                @endif
            </div>
            
            <div class="column">
                <div class="invoice-meta">
                    <div><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</div>
                    <div><strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('M d, Y') }}</div>
                    <div><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</div>
                    <div>
                        <strong>Status:</strong> 
                        <span class="status {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                    </div>
                    @if($payment->transaction_id)
                    <div><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Quantity</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item['description'] }}</td>
                <td style="text-align: center;">{{ $item['quantity'] }}</td>
                <td style="text-align: right;">₱{{ number_format($item['unit_price'], 2) }}</td>
                <td style="text-align: right;">₱{{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-line">
            <strong>Subtotal: ₱{{ number_format($invoice->subtotal, 2) }}</strong>
        </div>
        @if($invoice->tax_amount > 0)
        <div class="total-line">
            Tax: ₱{{ number_format($invoice->tax_amount, 2) }}
        </div>
        @endif
        <div class="total-line grand-total">
            <strong>Total: ₱{{ number_format($invoice->total_amount, 2) }}</strong>
        </div>
    </div>

    @if($payment && $payment->paid_at)
    <div style="margin-top: 30px;">
        <div><strong>Payment Information:</strong></div>
        <div>Payment Method: {{ ucfirst(str_replace('_', ' ', $payment->method)) }}</div>
        <div>Payment Date: {{ $payment->paid_at->format('M d, Y h:i A') }}</div>
        @if($payment->status === 'completed')
        <div style="color: green; font-weight: bold;">✓ Payment Received</div>
        @endif
    </div>
    @endif

    @if($subscription)
    <div style="margin-top: 30px;">
        <div><strong>Subscription Details:</strong></div>
        <div>Plan: {{ $subscription->getPlanLabel() }}</div>
        <div>Period: {{ $subscription->start_date->format('M d, Y') }} - {{ $subscription->end_date->format('M d, Y') }}</div>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a computer-generated invoice. No signature required.</p>
        @if($company['website'])
        <p>{{ $company['website'] }}</p>
        @endif
    </div>
</body>
</html>