<?php

namespace App\Events;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClinicApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Clinic $clinic,
        public User $approver
    ) {}
}