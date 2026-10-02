<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeminarDeliveryService
{
    private const FIELDS = ['external_url', 'external_link_visible_at', 'external_link_expiry_mode', 'external_link_expires_at', 'delivery_instructions'];

    public function __construct(private readonly SeminarNoticeService $notices) {}

    public function update(Seminar $seminar, array $data, User $actor): Seminar
    {
        [$updated, $changed] = DB::transaction(function () use ($seminar, $data): array {
            $locked = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            abort_unless($locked->isExternalDelivery() && in_array($locked->status, ['published', 'completed'], true), 422);
            $data = array_intersect_key($data, array_flip(self::FIELDS));
            if (array_key_exists('external_link_expiry_mode', $data) && $data['external_link_expiry_mode'] !== 'custom') {
                $data['external_link_expires_at'] = null;
            }
            $changed = false;
            foreach ($data as $field => $value) {
                $original = $locked->getRawOriginal($field);
                if ($original !== $value) {
                    $changed = true;
                    if ($field === 'external_link_visible_at') {
                        $locked->link_available_sent_for_visible_at = null;
                    }
                }
            }
            if ($changed) {
                $locked->fill($data)->save();
            }

            return [$locked->fresh(), $changed];
        });

        if ($changed) {
            try {
                $this->notices->notifyDeliveryChanged($updated, $actor);
            } catch (\Throwable $exception) {
                Log::warning('Seminar delivery notice dispatch failed', ['seminar_id' => $updated->id, 'exception' => $exception]);
            }
        }

        return $updated;
    }
}
