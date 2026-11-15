<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('age_eligibility_cached', 32)->nullable()->index()->after('age_bracket_cached');
            $table->boolean('minor_account_activation_required')->default(false)->after('age_eligibility_cached');
            $table->timestamp('minor_account_activated_at')->nullable()->after('minor_account_activation_required');
            $table->string('minor_privacy_notice_version', 32)->nullable()->after('minor_account_activated_at');
            $table->timestamp('minor_privacy_accepted_at')->nullable()->after('minor_privacy_notice_version');
        });
    }

    public function down(): void {}
};
