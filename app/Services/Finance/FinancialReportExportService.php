<?php

namespace App\Services\Finance;

use App\Support\Finance\FinancialReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class FinancialReportExportService
{
    public function exportAdminReport(string $format, FinancialReportFilter $filter, array $summaryPayload, array $breakdownPayload): BinaryFileResponse|Response|IlluminateResponse
    {
        $filenameBase = $this->filenameBase('admin-financial-report', $filter);

        if ($format === 'pdf') {
            return Pdf::loadView('admin.financial-reports.pdf.summary', [
                'filter' => $filter,
                'summaryPayload' => $summaryPayload,
                'breakdownPayload' => $breakdownPayload,
            ])->download($filenameBase . '.pdf');
        }

        return $this->downloadSpreadsheet(
            format: $format,
            filenameBase: $filenameBase,
            summaryPayload: $summaryPayload,
            breakdownPayload: $breakdownPayload,
        );
    }

    public function exportInstructorReport(string $format, FinancialReportFilter $filter, array $summaryPayload, array $breakdownPayload): BinaryFileResponse|Response|IlluminateResponse
    {
        $filenameBase = $this->filenameBase('instructor-earnings-report', $filter);

        if ($format === 'pdf') {
            return Pdf::loadView('instructor.earnings.pdf.summary', [
                'filter' => $filter,
                'summaryPayload' => $summaryPayload,
                'breakdownPayload' => $breakdownPayload,
            ])->download($filenameBase . '.pdf');
        }

        return $this->downloadSpreadsheet(
            format: $format,
            filenameBase: $filenameBase,
            summaryPayload: $summaryPayload,
            breakdownPayload: $breakdownPayload,
        );
    }

    private function filenameBase(string $prefix, FinancialReportFilter $filter): string
    {
        return Str::of($prefix)
            ->append('-')
            ->append($filter->reportType)
            ->append('-')
            ->append($filter->localStart->format('Ymd'))
            ->append('-')
            ->append($filter->localEnd->format('Ymd'))
            ->toString();
    }

    private function downloadSpreadsheet(string $format, string $filenameBase, array $summaryPayload, array $breakdownPayload): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');

        $row = 1;
        $sheet->setCellValue('A' . $row, 'Metric');
        $sheet->setCellValue('B' . $row, 'Value');
        $row++;

        $summary = (array) ($summaryPayload['summary'] ?? []);
        $metricRows = [
            'Total Revenue' => (float) ($summary['total_revenue'] ?? 0),
            'Gross Revenue' => (float) ($summary['gross_revenue'] ?? 0),
            'Net Revenue' => (float) ($summary['net_revenue'] ?? 0),
            'Refund Amount' => (float) ($summary['refund_amount'] ?? 0),
            'Subscription Revenue' => (float) ($summary['subscription_revenue'] ?? 0),
            'Module Revenue' => (float) ($summary['module_revenue'] ?? 0),
            'Platform Earnings' => (float) ($summary['platform_earnings'] ?? 0),
            'Instructor Earnings' => (float) ($summary['instructor_earnings'] ?? 0),
        ];

        foreach ($metricRows as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        $row += 1;
        $sheet->setCellValue('A' . $row, 'Top Modules');
        $row++;
        $sheet->setCellValue('A' . $row, 'Module');
        $sheet->setCellValue('B' . $row, 'Sales Count');
        $sheet->setCellValue('C' . $row, 'Gross Amount');
        $sheet->setCellValue('D' . $row, 'Platform Commission');
        $sheet->setCellValue('E' . $row, 'Instructor Earnings');
        $row++;

        foreach ((array) ($breakdownPayload['top_modules'] ?? []) as $moduleItem) {
            $sheet->setCellValue('A' . $row, (string) data_get($moduleItem, 'module.title', 'N/A'));
            $sheet->setCellValue('B' . $row, (int) data_get($moduleItem, 'sales_count', 0));
            $sheet->setCellValue('C' . $row, (float) data_get($moduleItem, 'gross_amount', 0));
            $sheet->setCellValue('D' . $row, (float) data_get($moduleItem, 'commission_amount', 0));
            $sheet->setCellValue('E' . $row, (float) data_get($moduleItem, 'instructor_earnings_amount', 0));
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(22);

        $tempDir = storage_path('app/tmp/financial-reports');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = $filenameBase . '.' . $format;
        $tempPath = $tempDir . '/' . Str::uuid() . '-' . $filename;

        if ($format === 'csv') {
            $writer = new CsvWriter($spreadsheet);
            $writer->setUseBOM(true);
            $contentType = 'text/csv; charset=UTF-8';
        } else {
            $writer = new XlsxWriter($spreadsheet);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        $writer->save($tempPath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($tempPath, $filename, [
            'Content-Type' => $contentType,
        ])->deleteFileAfterSend(true);
    }
}
