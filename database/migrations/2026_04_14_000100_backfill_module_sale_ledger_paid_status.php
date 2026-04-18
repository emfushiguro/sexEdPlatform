<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('module_sale_ledgers')
            ->where('payout_status', '!=', 'paid')
            ->update([
                'payout_status' => 'paid',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally no-op: previous payout states are not recoverable after backfill.
    }
};
