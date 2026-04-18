<?php

namespace App\Support\Finance;

use Carbon\CarbonImmutable;

class FinancialReportFilter
{
    public function __construct(
        public readonly string $reportType,
        public readonly string $timezone,
        public readonly CarbonImmutable $localStart,
        public readonly CarbonImmutable $localEnd,
        public readonly CarbonImmutable $utcStart,
        public readonly CarbonImmutable $utcEnd,
        public readonly string $granularity,
        public readonly ?int $instructorId = null,
        public readonly ?int $moduleId = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'report_type' => $this->reportType,
            'timezone' => $this->timezone,
            'date_from' => $this->localStart->toDateString(),
            'date_to' => $this->localEnd->toDateString(),
            'utc_start' => $this->utcStart->toDateTimeString(),
            'utc_end' => $this->utcEnd->toDateTimeString(),
            'granularity' => $this->granularity,
            'instructor_id' => $this->instructorId,
            'module_id' => $this->moduleId,
        ];
    }

    public function checksum(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }
}
