<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($type) }} Analytics Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .metric-card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .metric-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .summary-table th,
        .summary-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .summary-table th {
            background-color: #667eea;
            color: white;
        }
        .positive {
            color: #28a745;
        }
        .negative {
            color: #dc3545;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ ucfirst($type) }} Analytics Report</h1>
        <p>{{ $generatedAt->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="content">
        <h2>Executive Summary</h2>
        <p>Here's your {{ $type }} performance overview for the SexEd Platform:</p>

        <!-- Key Metrics Grid -->
        <div class="grid">
            <div class="metric-card">
                <div class="metric-value">{{ number_format($subscriptionMetrics['total_subscribers'] ?? 0) }}</div>
                <div class="metric-label">Total Subscribers</div>
            </div>

            <div class="metric-card">
                <div class="metric-value">{{ number_format($subscriptionMetrics['new_subscribers'] ?? 0) }}</div>
                <div class="metric-label">New Subscribers</div>
            </div>

            <div class="metric-card">
                <div class="metric-value">₱{{ number_format($subscriptionMetrics['monthly_recurring_revenue'] ?? 0, 2) }}</div>
                <div class="metric-label">Monthly Recurring Revenue</div>
            </div>

            <div class="metric-card">
                <div class="metric-value">₱{{ number_format($revenueMetrics['total_revenue'] ?? 0, 2) }}</div>
                <div class="metric-label">Total Revenue</div>
            </div>
        </div>

        <!-- Detailed Metrics Table -->
        <h3>Detailed Metrics</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Period</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Active Subscribers</td>
                    <td>{{ number_format($subscriptionMetrics['total_subscribers'] ?? 0) }}</td>
                    <td>Current</td>
                </tr>
                <tr>
                    <td>New Subscribers</td>
                    <td class="positive">+{{ number_format($subscriptionMetrics['new_subscribers'] ?? 0) }}</td>
                    <td>{{ ucfirst($period) }}</td>
                </tr>
                <tr>
                    <td>Churn Rate</td>
                    <td class="{{ ($subscriptionMetrics['churn_rate'] ?? 0) > 5 ? 'negative' : 'positive' }}">
                        {{ number_format($subscriptionMetrics['churn_rate'] ?? 0, 2) }}%
                    </td>
                    <td>{{ ucfirst($period) }}</td>
                </tr>
                <tr>
                    <td>Payment Success Rate</td>
                    <td class="{{ ($paymentAnalytics['success_rate'] ?? 0) > 90 ? 'positive' : 'negative' }}">
                        {{ number_format($paymentAnalytics['success_rate'] ?? 0, 2) }}%
                    </td>
                    <td>{{ ucfirst($period) }}</td>
                </tr>
                <tr>
                    <td>New Users</td>
                    <td class="positive">+{{ number_format($userGrowth['new_users'] ?? 0) }}</td>
                    <td>{{ ucfirst($period) }}</td>
                </tr>
                <tr>
                    <td>Total Revenue</td>
                    <td class="positive">₱{{ number_format($revenueMetrics['total_revenue'] ?? 0, 2) }}</td>
                    <td>{{ ucfirst($period) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Revenue Breakdown -->
        @if(isset($revenueMetrics) && !empty($revenueMetrics))
        <h3>Revenue Breakdown</h3>
        <div class="grid">
            @if(isset($revenueMetrics['subscription_revenue']))
            <div class="metric-card">
                <div class="metric-value">₱{{ number_format($revenueMetrics['subscription_revenue'], 2) }}</div>
                <div class="metric-label">Subscription Revenue</div>
            </div>
            @endif

            @if(isset($revenueMetrics['payment_volume']))
            <div class="metric-card">
                <div class="metric-value">{{ number_format($revenueMetrics['payment_volume']) }}</div>
                <div class="metric-label">Payment Transactions</div>
            </div>
            @endif

            @if(isset($revenueMetrics['average_revenue_per_user']))
            <div class="metric-card">
                <div class="metric-value">₱{{ number_format($revenueMetrics['average_revenue_per_user'], 2) }}</div>
                <div class="metric-label">Average Revenue Per User</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Payment Analytics -->
        @if(isset($paymentAnalytics) && !empty($paymentAnalytics))
        <h3>Payment Analytics</h3>
        <div class="grid">
            @if(isset($paymentAnalytics['successful_payments']))
            <div class="metric-card">
                <div class="metric-value positive">{{ number_format($paymentAnalytics['successful_payments']) }}</div>
                <div class="metric-label">Successful Payments</div>
            </div>
            @endif

            @if(isset($paymentAnalytics['failed_payments']))
            <div class="metric-card">
                <div class="metric-value {{ ($paymentAnalytics['failed_payments'] ?? 0) > 0 ? 'negative' : 'positive' }}">
                    {{ number_format($paymentAnalytics['failed_payments'] ?? 0) }}
                </div>
                <div class="metric-label">Failed Payments</div>
            </div>
            @endif

            @if(isset($paymentAnalytics['refund_amount']))
            <div class="metric-card">
                <div class="metric-value">₱{{ number_format($paymentAnalytics['refund_amount'] ?? 0, 2) }}</div>
                <div class="metric-label">Refunds Processed</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Key Insights -->
        <h3>Key Insights</h3>
        <div style="background: white; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <ul style="margin: 0; padding-left: 20px;">
                @if(($subscriptionMetrics['new_subscribers'] ?? 0) > 0)
                    <li class="positive"><strong>Growth:</strong> {{ number_format($subscriptionMetrics['new_subscribers']) }} new subscribers added this {{ $type === 'weekly' ? 'week' : 'month' }}</li>
                @endif
                
                @if(($paymentAnalytics['success_rate'] ?? 0) >= 95)
                    <li class="positive"><strong>Payment Health:</strong> Excellent payment success rate at {{ number_format($paymentAnalytics['success_rate'], 1) }}%</li>
                @elseif(($paymentAnalytics['success_rate'] ?? 0) < 90)
                    <li class="negative"><strong>Payment Concern:</strong> Payment success rate below 90% ({{ number_format($paymentAnalytics['success_rate'], 1) }}%)</li>
                @endif

                @if(($subscriptionMetrics['churn_rate'] ?? 0) <= 3)
                    <li class="positive"><strong>Retention:</strong> Low churn rate at {{ number_format($subscriptionMetrics['churn_rate'], 1) }}%</li>
                @elseif(($subscriptionMetrics['churn_rate'] ?? 0) > 5)
                    <li class="negative"><strong>Retention:</strong> High churn rate detected ({{ number_format($subscriptionMetrics['churn_rate'], 1) }}%)</li>
                @endif

                @if(($revenueMetrics['total_revenue'] ?? 0) > 0)
                    <li class="positive"><strong>Revenue:</strong> Generated ₱{{ number_format($revenueMetrics['total_revenue'], 2) }} this period</li>
                @endif
            </ul>
        </div>

        <!-- Action Items 
        <h3>Recommended Actions</h3>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <ul style="margin: 0; padding-left: 20px;">
                @if(($subscriptionMetrics['churn_rate'] ?? 0) > 5)
                    <li><strong>Investigate churn:</strong> Review canceled subscriptions and conduct exit surveys</li>
                @endif
                @if(($paymentAnalytics['success_rate'] ?? 0) < 90)
                    <li><strong>Payment optimization:</strong> Review payment flow and consider alternative payment methods</li>
                @endif
                @if(($subscriptionMetrics['new_subscribers'] ?? 0) < 10)
                    <li><strong>Acquisition boost:</strong> Focus on marketing campaigns and referral programs</li>
                @endif
                <li><strong>Continue monitoring:</strong> Track these metrics closely next {{ $type === 'weekly' ? 'week' : 'month' }}</li>
            </ul>
        </div>-->
    </div>

    <div class="footer">
        <p>Generated automatically by SexEd Platform Analytics</p>
        <p>For questions about this report, contact your development team.</p>
    </div>
</body>
</html>