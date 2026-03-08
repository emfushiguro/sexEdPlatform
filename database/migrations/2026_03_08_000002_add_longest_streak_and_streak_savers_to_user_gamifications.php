<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_gamifications', function (Blueprint $table) {
            if (!Schema::hasColumn('user_gamifications', 'longest_streak')) {
                $table->unsignedInteger('longest_streak')->default(0)->after('streak_count');
            }
            if (!Schema::hasColumn('user_gamifications', 'streak_savers')) {
                $table->unsignedTinyInteger('streak_savers')->default(0)->after('longest_streak');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_gamifications', function (Blueprint $table) {
            $table->dropColumnIfExists('longest_streak');
            $table->dropColumnIfExists('streak_savers');
        });
    }
};
