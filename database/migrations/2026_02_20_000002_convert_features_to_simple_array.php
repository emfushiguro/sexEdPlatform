<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Convert existing features from key-value pairs to simple array
        $plans = DB::table('subscription_plans')->get();
        
        foreach ($plans as $plan) {
            $features = json_decode($plan->features, true);
            
            if (is_array($features) && !empty($features)) {
                // Extract only the keys (feature names) where value is true or non-empty
                $newFeatures = [];
                foreach ($features as $key => $value) {
                    // Include feature if value is true, or if it's a truthy value
                    if ($value === true || $value === 'true' || (!empty($value) && $value !== false && $value !== 'false')) {
                        $newFeatures[] = $key;
                    }
                }
                
                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update(['features' => json_encode($newFeatures)]);
            } else {
                // If features is empty or not an array, set to empty array
                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update(['features' => json_encode([])]);
            }
        }
    }

    public function down()
    {
        // Can't reliably reverse this, so we'll just log a warning
        // Manual intervention required if rollback is needed
    }
};
