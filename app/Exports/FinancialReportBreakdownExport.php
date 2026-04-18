<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinancialReportBreakdownExport implements FromArray, WithTitle
{
    /**
     * @param array<int, array<int, string|int|float>> $rows
     */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $rows,
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
