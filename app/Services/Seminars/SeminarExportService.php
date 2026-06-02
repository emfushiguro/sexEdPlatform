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
                'role',
                'joined at',
                'left at',
                'total minutes',
                'status',
            ]);

            $seminar->attendances()
                ->with('user')
                ->orderBy('joined_at')
                ->chunk(200, function ($attendances) use ($handle): void {
                    foreach ($attendances as $attendance) {
                        $user = $attendance->user;
                        fputcsv($handle, [
                            $user?->name,
                            $user?->email,
                            $user?->role,
                            optional($attendance->joined_at)?->toDateTimeString(),
                            optional($attendance->left_at)?->toDateTimeString(),
                            number_format((int) $attendance->total_seconds / 60, 2, '.', ''),
                            $attendance->status,
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
