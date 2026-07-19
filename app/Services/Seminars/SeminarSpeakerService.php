<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\SeminarSpeaker;
use App\Models\User;
use App\Notifications\Seminars\SeminarSpeakerAssignedNotification;
use App\Notifications\Seminars\SeminarSpeakerInvitationRespondedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeminarSpeakerService
{
    public function addPlatformSpeaker(Seminar $seminar, User $user, array $attributes): SeminarSpeaker
    {
        $speaker = DB::transaction(function () use ($seminar, $user, $attributes): SeminarSpeaker {
            Seminar::query()->lockForUpdate()->findOrFail($seminar->id);

            if ($seminar->speakers()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'This instructor already has an invitation or speaker record.']);
            }

            return $seminar->speakers()->create([
                'user_id' => $user->id,
                'display_name' => $attributes['display_name'] ?? $user->name,
                'title' => $attributes['title'] ?? null,
                'bio' => $attributes['bio'] ?? null,
                'role' => $attributes['role'] ?? 'speaker',
                'status' => 'pending',
                'invitation_message' => $attributes['invitation_message'] ?? null,
                'invited_at' => now(),
                'expires_at' => $seminar->starts_at ?? $seminar->schedule,
            ]);
        });

        $user->notify(new SeminarSpeakerAssignedNotification($speaker->load('seminar.connector')));

        return $speaker;
    }

    public function addExternalSpeaker(Seminar $seminar, array $attributes): SeminarSpeaker
    {
        return $seminar->speakers()->create([
            'user_id' => null,
            'display_name' => $attributes['display_name'],
            'title' => $attributes['title'] ?? null,
            'bio' => $attributes['bio'] ?? null,
            'role' => $attributes['role'] ?? 'speaker',
            'status' => 'accepted',
        ]);
    }

    public function removeSpeaker(Seminar $seminar, SeminarSpeaker $speaker): void
    {
        abort_unless((int) $speaker->seminar_id === (int) $seminar->id, 404);

        $speaker->delete();
    }

    public function cancelInvitation(Seminar $seminar, SeminarSpeaker $speaker): void
    {
        abort_unless((int) $speaker->seminar_id === (int) $seminar->id, 404);
        abort_unless(in_array($speaker->status, ['pending', 'accepted'], true), 422, 'This invitation is already closed.');

        $speaker->update(['status' => 'cancelled', 'responded_at' => now()]);
    }

    public function isSpeaker(User $user, Seminar $seminar): bool
    {
        return $seminar->speakers()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    public function acceptInvitation(SeminarSpeaker $speaker): SeminarSpeaker
    {
        $this->abortUnlessPending($speaker);
        $speaker->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        $this->notifyConnectorManagers($speaker->fresh(['seminar.connector']));

        return $speaker;
    }

    public function declineInvitation(SeminarSpeaker $speaker, ?string $reason = null): SeminarSpeaker
    {
        $this->abortUnlessPending($speaker);
        $speaker->update([
            'status' => 'declined',
            'responded_at' => now(),
            'review_note' => $reason,
        ]);

        $this->notifyConnectorManagers($speaker->fresh(['seminar.connector']));

        return $speaker;
    }

    public function apply(Seminar $seminar, User $user, array $attributes): SeminarSpeaker
    {
        if ($seminar->speakers()->where('user_id', $user->id)->whereIn('status', ['pending', 'accepted', 'applied'])->exists()) {
            throw ValidationException::withMessages(['speaker' => 'You already have a speaker invitation or application for this seminar.']);
        }

        $speaker = $seminar->speakers()->create([
            'user_id' => $user->id,
            'display_name' => $user->name,
            'title' => $user->instructorProfile?->primary_expertise,
            'bio' => $user->instructorProfile?->bio,
            'role' => 'speaker',
            'status' => 'applied',
            'application_motivation' => $attributes['motivation'],
            'application_expertise' => $attributes['expertise'],
            'application_experience' => $attributes['experience'],
            'application_supporting_info' => $attributes['supporting_info'] ?? null,
            'invited_at' => now(),
        ]);

        $this->notifyConnectorManagers($speaker->fresh(['seminar.connector']));

        return $speaker;
    }

    public function approveApplication(SeminarSpeaker $speaker, User $reviewer): SeminarSpeaker
    {
        $speaker->update([
            'status' => 'accepted',
            'reviewed_by' => $reviewer->id,
            'responded_at' => now(),
        ]);

        $speaker->user?->notify(new SeminarSpeakerInvitationRespondedNotification($speaker->fresh(['seminar.connector'])));

        return $speaker;
    }

    public function rejectApplication(SeminarSpeaker $speaker, User $reviewer, ?string $note = null): SeminarSpeaker
    {
        $speaker->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'review_note' => $note,
            'responded_at' => now(),
        ]);

        $speaker->user?->notify(new SeminarSpeakerInvitationRespondedNotification($speaker->fresh(['seminar.connector'])));

        return $speaker;
    }

    private function notifyConnectorManagers(SeminarSpeaker $speaker): void
    {
        $connector = $speaker->seminar?->connector;

        if (! $connector) {
            return;
        }

        User::query()
            ->whereHas('connectorMemberships', fn ($query) => $query
                ->where('connector_id', $connector->id)
                ->where('status', 'active')
                ->whereHas('role.permissions', fn ($permission) => $permission->where('permission_key', 'connector.manage_seminars')))
            ->get()
            ->each(fn (User $manager) => $manager->notify(new SeminarSpeakerInvitationRespondedNotification($speaker)));
    }

    private function abortUnlessPending(SeminarSpeaker $speaker): void
    {
        if ($speaker->status === 'pending' && $speaker->expires_at?->isPast()) {
            $speaker->update(['status' => 'expired', 'responded_at' => now()]);
        }

        abort_unless($speaker->status === 'pending', 422, 'This invitation is no longer pending.');
    }
}
