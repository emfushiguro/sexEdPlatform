<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$database = (string) env('PLAYWRIGHT_DB_DATABASE', env('DB_DATABASE', ''));
if (app()->environment() !== 'testing' || ! preg_match('/_playwright_test$/i', $database)) {
    fwrite(STDERR, "Refusing to prepare an unguarded Playwright database.\n");
    exit(1);
}

$resolved = (string) DB::selectOne('select database() as name')->name;
if ($resolved !== $database) {
    fwrite(STDERR, "Resolved database does not match PLAYWRIGHT_DB_DATABASE.\n");
    exit(1);
}

if (in_array('--check-only', $argv, true)) {
    if (! DB::getSchemaBuilder()->hasTable('migrations')) {
        fwrite(STDERR, "Playwright database exists but has no migrations table.\n");
        exit(1);
    }
    fwrite(STDOUT, "Playwright database guard passed: {$database}\n");
    exit(0);
}

Artisan::call('migrate', ['--force' => true]);
fwrite(STDOUT, Artisan::output());
