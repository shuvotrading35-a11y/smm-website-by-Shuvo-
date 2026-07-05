#!/usr/bin/env php
<?php

/**
 * Cron: sync-services.php
 * Pulls all services from the SMM provider and upserts into local DB.
 * Schedule: every 6 hours
 *   0 * /6 * * * php /var/www/shuvosmm/cron/sync-services.php >> /var/log/smm-services.log 2>&1
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

use SMMPanel\Core\Config;
use SMMPanel\Core\Database;
use SMMPanel\Services\SmmApiService;

Config::boot(BASE_PATH);

$db  = Database::getInstance();
$api = new SmmApiService();

echo '[' . date('Y-m-d H:i:s') . '] Starting service sync...' . PHP_EOL;

try {
    $result = $api->syncServicesToDb();

    echo "Added:   {$result['added']}"   . PHP_EOL;
    echo "Updated: {$result['updated']}" . PHP_EOL;

    if (!empty($result['errors'])) {
        echo 'Errors:' . PHP_EOL;
        foreach ((array)$result['errors'] as $err) {
            echo '  - ' . $err . PHP_EOL;
        }
    }

    // Also refresh provider API balance in settings
    try {
        $balance = $api->getBalance();
        if (isset($balance['balance'])) {
            $db->query(
                "UPDATE smmPanel_settings SET `value` = ? WHERE `key` = 'smm_api_balance'",
                [$balance['balance']]
            );
            $db->query(
                "UPDATE smmPanel_settings SET `value` = ? WHERE `key` = 'smm_api_balance_at'",
                [date('Y-m-d H:i:s')]
            );
            echo 'Provider balance: $' . $balance['balance'] . PHP_EOL;
        }
    } catch (\Throwable $e) {
        echo '[WARN] Balance fetch failed: ' . $e->getMessage() . PHP_EOL;
    }

    echo '[' . date('Y-m-d H:i:s') . '] Service sync complete.' . PHP_EOL;
} catch (\Throwable $e) {
    echo '[ERROR] ' . $e->getMessage() . PHP_EOL;

    $db->insert('smmPanel_system_logs', [
        'level'   => 'error',
        'channel' => 'cron',
        'message' => 'Service sync failed: ' . $e->getMessage(),
    ]);

    exit(1);
}
