<?php

require __DIR__.'/../vendor/autoload.php';

if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'mysql' || getenv('DB_DATABASE') !== 'cc_db_test') {
    throw new RuntimeException('Transaction-only tests require APP_ENV=testing and the existing MySQL cc_db_test database.');
}
if (is_file(getenv('APP_CONFIG_CACHE') ?: __DIR__.'/../bootstrap/cache/config.php')) {
    throw new RuntimeException('Remove the dedicated testing config cache before running transaction-only tests.');
}

// Keep the existing test schema; RefreshDatabase will only open rollback transactions.
Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = true;
