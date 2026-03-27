<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case ScheduledCancel = 'scheduled_cancel';
    case GracePeriod = 'grace_period';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
