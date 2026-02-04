<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use Illuminate\Console\Command;

class RestoreClinicCoordinates extends Command
{
    protected $signature = 'clinic:restore-coordinates';
    protected $description = 'Restore missing clinic coordinates';

    public function handle()
    {
        $clinics = Clinic::whereNull('latitude')->orWhereNull('longitude')->get();
        
        if ($clinics->isEmpty()) {
            $this->info('All clinics already have coordinates');
            return 0;
        }

        $this->info("Found {$clinics->count()} clinics missing coordinates:");
        
        foreach ($clinics as $clinic) {
            $this->info("- {$clinic->name} (ID: {$clinic->id}): Lat={$clinic->latitude}, Lng={$clinic->longitude}");
            
            // Set default coordinates for clinics missing them (using Cavite center)
            $clinic->update([
                'latitude' => 14.4791,
                'longitude' => 120.8970
            ]);
            $this->info("  ✓ Updated with default Cavite coordinates");
        }

        $this->info("All missing coordinates have been restored!");
        return 0;
    }
}