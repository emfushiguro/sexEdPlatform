<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyAnalyticsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $analyticsData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $analyticsData)
    {
        $this->analyticsData = $analyticsData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $period = $this->analyticsData['type'] ?? 'weekly';
        return new Envelope(
            subject: ucfirst($period) . ' Analytics Report - ' . now()->format('M d, Y'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-analytics-report',
            with: [
                'data' => $this->analyticsData,
                'subscriptionMetrics' => $this->analyticsData['subscription_metrics'] ?? [],
                'revenueMetrics' => $this->analyticsData['revenue_metrics'] ?? [],
                'paymentAnalytics' => $this->analyticsData['payment_analytics'] ?? [],
                'userGrowth' => $this->analyticsData['user_growth'] ?? [],
                'period' => $this->analyticsData['period'] ?? '7d',
                'type' => $this->analyticsData['type'] ?? 'weekly',
                'generatedAt' => $this->analyticsData['generated_at'] ?? now(),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
