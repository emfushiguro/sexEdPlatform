<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\SeminarSpeaker;
use App\Models\User;
use App\Notifications\Seminars\SeminarSpeakerAssignedNotification;
use Illuminate\Validation\ValidationException;

class SeminarSpeakerService
{
    public function addPlatformSpeaker(Seminar $seminar, User $user, array $attributes): SeminarSpeaker
    {
        if ($this->isSpeaker($user, $seminar)) {
            throw ValidationException::withMessages(['user_id' => 'This platform user is already assigned as a speaker.']);
        }

        $speaker = $seminar->speakers()->create([
            'user_id' => $user->id,
            'display_name' => $attributes['display_name'] ?? $user->name,
            'title' => $attributes['title'] ?? null,
            'bio' => $attributes['bio'] ?? null,
            'role' => $attributes['role'] ?? 'speaker',
        ]);

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
        ]);
    }

    public function removeSpeaker(Seminar $seminar, SeminarSpeaker $speaker): void
    {
        abort_unless((int) $speaker->seminar_id === (int) $seminar->id, 404);

        $speaker->delete();
    }

    public function isSpeaker(User $user, Seminar $seminar): bool
    {
        return $seminar->speakers()
            ->where('user_id', $user->id)
            ->exists();
    }
}
