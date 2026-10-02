<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Seminars\SeminarDeliveryNotification;
use Illuminate\Support\Collection;

class SeminarNoticeService
{
    public function __construct(private readonly SeminarRegistrationService $registrations) {}

    public function recipients(Seminar $seminar, ?User $actor = null, bool $adminAction = false): Collection
    {
        $registrants = $seminar->registrants()->active()->with('user.learnerProfile')->get()
            ->pluck('user')
            ->filter(fn ($user) => $user && $this->registrations->matchesParticipantEligibility($user, $seminar));
        $speakers = $seminar->speakers()->where('status', 'accepted')->whereNotNull('user_id')->with('user')->get()->pluck('user')->filter();
        $organizer = $adminAction
            ? $seminar->connector?->primaryRepresentative ?? $seminar->connector?->creator
            : null;

        return $registrants->concat($speakers)->when($organizer, fn ($users) => $users->push($organizer))
            ->unique('id')->values();
    }

    public function notifyDeliveryChanged(Seminar $seminar, User $actor, bool $adminAction = false): void
    {
        foreach ($this->recipients($seminar, $actor, $adminAction) as $recipient) {
            $recipient->notify(new SeminarDeliveryNotification((int) $seminar->id, $seminar->title, 'changed'));
        }
    }
}
