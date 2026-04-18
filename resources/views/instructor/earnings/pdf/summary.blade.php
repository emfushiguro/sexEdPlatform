<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instructor Earnings Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        .meta { margin-bottom: 16px; color: #4b5563; }
        .summary-grid { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .summary-grid td { border: 1px solid #e5e7eb; padding: 8px; }
        .summary-grid td:first-child { font-weight: 700; width: 35%; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 7px; text-align: left; }
        th { background: #f3f4f6; font-weight: 700; }
        .footer { margin-top: 18px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
    @php
        $summary = (array) ($summaryPayload['summary'] ?? []);
    @endphp

    <h1>{{ ucfirst($filter->reportType) }} Instructor Earnings Report</h1>
    <div class="meta">
        Date Range: {{ $filter->localStart->format('M d, Y') }} - {{ $filter->localEnd->format('M d, Y') }}<br>
        Timezone: {{ $filter->timezone }}
    </div>

    <table class="summary-grid">
        <tr><td>Total Sales</td><td>{{ number_format((int) ($summary['total_transactions'] ?? 0)) }}</td></tr>
        <tr><td>Gross Revenue</td><td>₱{{ number_format((float) ($summary['gross_revenue'] ?? 0), 2) }}</td></tr>
        <tr><td>Platform Commission</td><td>₱{{ number_format((float) ($summary['platform_commission'] ?? 0), 2) }}</td></tr>
        <tr><td>Your Earnings</td><td>₱{{ number_format((float) ($summary['instructor_earnings'] ?? 0), 2) }}</td></tr>
    </table>

    <h3>Revenue Source Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Source</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($breakdownPayload['source_breakdown'] ?? []) as $row)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', (string) data_get($row, 'source', 'other'))) }}</td>
                    <td>₱{{ number_format((float) data_get($row, 'amount', 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No data available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated at {{ now('Asia/Manila')->format('M d, Y h:i A') }} (Asia/Manila)
    </div>
</body>
</html>
