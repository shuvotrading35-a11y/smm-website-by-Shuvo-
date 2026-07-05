#!/usr/bin/env php
<?php

/**
 * Cron: sync-orders.php
 * Syncs pending/in-progress order statuses from the SMM provider.
 * Schedule: every 5 minutes
 *   * /5 * * * * php /var/www/shuvosmm/cron/sync-orders.php >> /var/log/smm-orders.log 2>&1
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

use SMMPanel\Core\Config;
use SMMPanel\Core\Database;
use SMMPanel\Services\SmmApiService;
use SMMPanel\Services\NotificationService;

Config::boot(BASE_PATH);

$db     = Database::getInstance();
$api    = new SmmApiService();
$notify = new NotificationService();

$startTime = microtime(true);
$synced    = 0;
$errors    = 0;

echo '[' . date('Y-m-d H:i:s') . '] Starting order sync...' . PHP_EOL;

// Fetch all pending/in-progress orders that have an API order ID
$orders = $db->fetchAll(
    'SELECT id, user_id, api_order_id, status, quantity, charge
     FROM smmPanel_orders
     WHERE status IN ("pending","processing","in_progress")
       AND api_order_id IS NOT NULL
     ORDER BY created_at ASC
     LIMIT 500'
);

if (empty($orders)) {
    echo 'No orders to sync.' . PHP_EOL;
    exit(0);
}

// Batch into groups of 100 (provider bulk limit)
$chunks = array_chunk($orders, 100);

foreach ($chunks as $chunk) {
    $apiIds    = array_column($chunk, 'api_order_id');
    $orderMap  = [];

    foreach ($chunk as $order) {
        $orderMap[$order['api_order_id']] = $order;
    }

    try {
        $statuses = $api->getBulkStatus($apiIds);

        if (!is_array($statuses)) {
            throw new RuntimeException('Invalid response from provider.');
        }

        foreach ($statuses as $apiOrderId => $s) {
            if (!isset($orderMap[$apiOrderId])) continue;

            $order      = $orderMap[$apiOrderId];
            $newStatus  = mapProviderStatus($s['status'] ?? '');
            $startCount = isset($s['start_count']) ? (int)$s['start_count'] : null;
            $remains    = isset($s['remains'])     ? (int)$s['remains']     : null;

            if ($newStatus === $order['status'] && $startCount === null) {
                continue; // No change
            }

            $updateData = [
                'api_status' => $s['status'] ?? null,
                'status'     => $newStatus,
            ];

            if ($startCount !== null) $updateData['start_count'] = $startCount;
            if ($remains    !== null) $updateData['remains']     = $remains;

            if ($newStatus === 'completed') {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }

            $db->update('smmPanel_orders', $updateData, ['id' => $order['id']]);

            // Log the status change
            if ($newStatus !== $order['status']) {
                $db->insert('smmPanel_order_logs', [
                    'order_id'   => $order['id'],
                    'old_status' => $order['status'],
                    'new_status' => $newStatus,
                    'message'    => "Auto-synced from provider. Remains: {$remains}",
                    'source'     => 'cron',
                ]);

                // Notify user on completion or partial
                if (in_array($newStatus, ['completed', 'partial', 'cancelled'], true)) {
                    $messages = [
                        'completed' => "Your order #{$order['id']} has been completed!",
                        'partial'   => "Your order #{$order['id']} was partially completed.",
                        'cancelled' => "Your order #{$order['id']} was cancelled by the provider.",
                    ];

                    $notify->send(
                        $order['user_id'],
                        'order_' . $newStatus,
                        'Order ' . ucfirst($newStatus),
                        $messages[$newStatus],
                        ['order_id' => $order['id']],
                        "/dashboard/orders/{$order['id']}"
                    );
                }
            }

            $synced++;
        }
    } catch (\Throwable $e) {
        $errors++;
        echo '[ERROR] Batch failed: ' . $e->getMessage() . PHP_EOL;

        $db->insert('smmPanel_system_logs', [
            'level'   => 'error',
            'channel' => 'cron',
            'message' => 'Order sync batch failed: ' . $e->getMessage(),
            'context' => json_encode(['api_ids' => $apiIds]),
        ]);
    }

    // Brief pause between batches to avoid rate limiting
    usleep(200000); // 200ms
}

$duration = round(microtime(true) - $startTime, 2);
echo "[" . date('Y-m-d H:i:s') . "] Done. Synced: {$synced}, Errors: {$errors}, Duration: {$duration}s" . PHP_EOL;

// ── Helper: map provider status string to our enum ────────────

function mapProviderStatus(string $providerStatus): string
{
    $map = [
        'pending'     => 'pending',
        'in progress' => 'in_progress',
        'inprogress'  => 'in_progress',
        'processing'  => 'processing',
        'active'      => 'in_progress',
        'completed'   => 'completed',
        'partial'     => 'partial',
        'cancelled'   => 'cancelled',
        'canceled'    => 'cancelled',
        'refunded'    => 'refunded',
        'error'       => 'error',
        'fail'        => 'error',
        'failed'      => 'error',
    ];

    return $map[strtolower(trim($providerStatus))] ?? 'pending';
}
