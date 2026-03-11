
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->foreign('plan_id')
                ->references('id')->on('subscription_plans')
                ->nullOnDelete(); // ON DELETE SET NULL
        });
    }

    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->foreign('plan_id')
                ->references('id')->on('subscription_plans')
                ->restrictOnDelete(); // revert to RESTRICT
        });
    }
};
