<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Identity\SyncPersonaInquiry;
use App\Models\PersonaInquiry;
use App\Models\PersonaWebhookEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PersonaWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $validation = Validator::make($payload, [
            'data.id' => ['required', 'string', 'max:80'],
            'data.type' => ['required', 'string', 'max:40'],
            'data.attributes.name' => ['required', 'string', 'max:80'],
            'data.attributes.created-at' => ['required', 'date'],
            'data.attributes.payload.data.id' => ['required', 'string', 'max:80'],
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => 'Invalid webhook payload.'], 422);
        }

        $eventId = (string) data_get($payload, 'data.id');
        $eventType = (string) data_get($payload, 'data.attributes.name');
        $inquiryId = (string) data_get($payload, 'data.attributes.payload.data.id');
        $eventCreatedAt = CarbonImmutable::parse((string) data_get($payload, 'data.attributes.created-at'));

        try {
            $event = DB::transaction(function () use ($eventId, $eventType, $inquiryId, $eventCreatedAt): PersonaWebhookEvent {
                $existing = PersonaWebhookEvent::query()->where('event_id', $eventId)->first();
                if ($existing) {
                    return $existing;
                }

                $event = PersonaWebhookEvent::query()->create([
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'persona_inquiry_id' => $inquiryId,
                    'event_created_at' => $eventCreatedAt,
                    'processing_status' => PersonaWebhookEvent::STATUS_RECEIVED,
                ]);

                if (! PersonaInquiry::query()->where('persona_inquiry_id', $inquiryId)->exists()) {
                    $event->forceFill([
                        'processing_status' => PersonaWebhookEvent::STATUS_IGNORED,
                    ])->save();
                }

                return $event;
            });
        } catch (QueryException $exception) {
            if (! str_starts_with((string) $exception->getCode(), '23')) {
                throw $exception;
            }

            return response()->json(['accepted' => true, 'duplicate' => true]);
        }

        if ($event->wasRecentlyCreated && $event->processing_status === PersonaWebhookEvent::STATUS_RECEIVED) {
            SyncPersonaInquiry::dispatch($event->id)->afterCommit();

            return response()->json(['accepted' => true]);
        }

        if ($event->wasRecentlyCreated && $event->processing_status === PersonaWebhookEvent::STATUS_IGNORED) {
            return response()->json(['accepted' => true, 'ignored' => true]);
        }

        return response()->json(['accepted' => true, 'duplicate' => true]);
    }
}
