<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('certificates')
            ->select(['id', 'certificate_number', 'issued_at', 'created_at'])
            ->orderBy('id')
            ->chunkById(100, function ($certificates): void {
                foreach ($certificates as $certificate) {
                    if ($this->isNewFormat($certificate->certificate_number)) {
                        continue;
                    }

                    $year = $this->extractYear($certificate->issued_at, $certificate->created_at);
                    $newNumber = $this->generateUniqueNumber($year);

                    DB::table('certificates')
                        ->where('id', $certificate->id)
                        ->update(['certificate_number' => $newNumber]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank. Backfilled certificate numbers should remain stable.
    }

    private function isNewFormat(?string $certificateNumber): bool
    {
        if (!$certificateNumber) {
            return false;
        }

        return (bool) preg_match('/^CC-\d{4}-[A-Z0-9]{8}$/', $certificateNumber);
    }

    private function extractYear($issuedAt, $createdAt): string
    {
        $candidate = $issuedAt ?? $createdAt;

        if (is_string($candidate) && strlen($candidate) >= 4) {
            return substr($candidate, 0, 4);
        }

        return now()->format('Y');
    }

    private function generateUniqueNumber(string $year): string
    {
        do {
            $candidate = 'CC-' . $year . '-' . strtoupper(Str::random(8));
            $exists = DB::table('certificates')
                ->where('certificate_number', $candidate)
                ->exists();
        } while ($exists);

        return $candidate;
    }
};
