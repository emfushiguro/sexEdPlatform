<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarExportService
{
    public function registrantsCsv(Seminar $seminar): StreamedResponse
    {
        $filename = $this->filename($seminar, 'registrants');

        return response()->streamDownload(function () use ($seminar): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'name',
                'email',
                'participant type',
                'learner age category',
                'status',
                'registered at',
                'cancelled at',
            ]);

            $seminar->registrants()
                ->with('user')
                ->orderBy('registered_at')
                ->chunk(200, function ($registrants) use ($handle): void {
                    foreach ($registrants as $registrant) {
                        $user = $registrant->user;
                        fputcsv($handle, [
                            $user?->name,
                            $user?->email,
                            $registrant->participant_type,
                            $user?->age_bracket_cached,
                            $registrant->status,
                            optional($registrant->registered_at)?->toDateTimeString(),
                            optional($registrant->cancelled_at)?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function attendanceCsv(Seminar $seminar): StreamedResponse
    {
        $filename = $this->filename($seminar, 'attendance');

        return response()->streamDownload(function () use ($seminar): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'name',
                'email',
                'participant type',
                'registration status',
                'attendance status',
                'method',
                'attended at',
                'joined at',
                'left at',
                'total minutes',
            ]);

            $seminar->registrants()
                ->with('user')
                ->orderBy('registered_at')
                ->orderBy('id')
                ->chunk(200, function ($registrants) use ($seminar, $handle): void {
                    $attendances = $seminar->attendances()
                        ->whereIn('user_id', $registrants->pluck('user_id'))
                        ->get()
                        ->keyBy('user_id');

                    foreach ($registrants as $registrant) {
                        $user = $registrant->user;
                        $attendance = $attendances->get($registrant->user_id);
                        fputcsv($handle, [
                            $user?->name,
                            $user?->email,
                            $registrant->participant_type,
                            $registrant->status,
                            $attendance?->status ?? 'Not submitted',
                            $attendance?->attendance_method,
                            optional($attendance?->attended_at)->toDateTimeString(),
                            optional($attendance?->joined_at)->toDateTimeString(),
                            optional($attendance?->left_at)->toDateTimeString(),
                            $attendance ? number_format((int) $attendance->total_seconds / 60, 2, '.', '') : '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filename(Seminar $seminar, string $type): string
    {
        return 'seminar-'.$seminar->id.'-'.$type.'.csv';
    }
}
