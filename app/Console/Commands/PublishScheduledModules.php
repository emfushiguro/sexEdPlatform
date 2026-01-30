<?php

namespace App\Console\Commands;

use App\Models\Module;
use Illuminate\Console\Command;

class PublishScheduledModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically publish modules that have reached their scheduled publish time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = Module::where('publish_status', 'scheduled')
            ->where('is_published', false)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->get();

        if ($modules->isEmpty()) {
            $this->info('No modules to publish.');
            return 0;
        }

        $count = 0;
        foreach ($modules as $module) {
            $module->update([
                'is_published' => true,
                'publish_status' => 'published',
            ]);
            $count++;
            $this->info("✓ Published: {$module->title}");
        }

        $this->info("Successfully published {$count} module(s).");
        return 0;
    }
}
