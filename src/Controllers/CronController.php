<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Config;
use SMMPanel\Core\Database;
use SMMPanel\Services\SmmApiService;
use SMMPanel\Services\NotificationService;

/**
 * CronController — HTTP-triggered replacement for system crontab.
 *
 * Railway containers don't run system cron, so instead this endpoint
 * is pinged periodically by an external uptime/pinger bot. Each ping
 * checks whether enough time has passed since the last order sync
 * (5 min) or service sync (6 hours) and runs them if due. Safe to
 * call as often as you like — it's a no-op outside the schedule.
 */
final class CronController
{
    private const ORDER_SYNC_INTERVAL_SECONDS   = 300;      // 5 minutes
    private const SERVICE_SYNC_INTERVAL_SECONDS = 21600;    // 6 hours
    private const ORDER_BATCH_LIMIT             = 500;
    private const ORDER_CHUNK_SIZE               = 100;

    private Database $db;
    private SmmApiService $api;
    private NotificationService $notify;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->api    = new SmmApiService();
        $this->notify = new NotificationService();
    }

    public function tick(array $params): void
    {
        header('Content-Type: application/json');

        $secret = $_GET['secret'] ?? '';
        if (!hash_equals(Config::required('CRON_SECRET'), (string) $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $result = [
            'time'     => date('Y-m-d H:i:s'),
            'orders'   => null,
            'services' => null,
        ];

        if ($this->dueFor('last_order_sync_at', self::ORDER_SYNC_INTERVAL_SECONDS)) {
            try {
                $result['orders'] = $this->syncOrders();
                $this->touch('last_order_sync_at');
            } catch (\Throwable $e) {
                error_log('[Cron] Order sync failed: ' . $e->getMessage());
                $result['orders'] = ['error' => $e->getMessage()];
            }
        }

        if ($this->dueFor('last_service_sync_at', self::SERVICE_SYNC_INTERVAL_SECONDS)) {
            try {
                $result['services'] = $this->api->syncServicesToDb();
                $this->touch('last_service_sync_at');

                try {
                    $balance = $this->api->getBalance();
                    if (isset($balance['balance'])) {
                        $this->setSetting('smm_api_balance', (string) $balance['balance']);
                        $this->setSetting('smm_api_balance_at', date('Y-m-d H:i:s'));
                    }
                } catch (\Throwable $e) {
                    error_log('[Cron] Balance fetch failed: ' . $e->getMessage());
                }
            } catch (\Throwable $e) {
                error_log('[Cron] Service sync failed: ' . $e->getMessage());
                $result['services'] = ['error' => $e->getMessage()];
            }
        }

        echo json_encode($result);
    }

    // ── Order sync (ported from cron/sync-orders.php) ──────────

    private function syncOrders(): array
    {
        $synced = 0;
        $errors = 0;

        $orders = $this->db->fetchAll(
            'SELECT id, user_id, api_order_id, status, quantity, charge
             FROM smmPanel_orders
             WHERE status IN ("pending","processing","in_progress")
               AND api_order_id IS NOT NULL
             ORDER BY created_at ASC
             LIMIT ' . self::ORDER_BATCH_LIMIT
        );

        if (empty($orders)) {
            return ['synced' => 0, 'errors' => 0, 'note' => 'No orders to sync'];
        }

        $chunks = array_chunk($orders, self::ORDER_CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $apiIds   = array_column($chunk, 'api_order_id');
            $orderMap = [];
            foreach ($chunk as $order) {
                $orderMap[$order['api_order_id']] = $order;
            }

            try {
                $statuses = $this->api->getBulkStatus($apiIds);

                if (!is_array($statuses)) {
                    throw new \RuntimeException('Invalid response from provider.');
                }

                foreach ($statuses as $apiOrderId => $s) {
                    if (!isset($orderMap[$apiOrderId])) {
                        continue;
                    }

                    $order      = $orderMap[$apiOrderId];
                    $newStatus  = $this->mapProviderStatus($s['status'] ?? '');
                    $startCount = isset($s['start_count']) ? (int) $s['start_count'] : null;
                    $remains    = isset($s['remains']) ? (int) $s['remains'] : null;

                    if ($newStatus === $order['status'] && $startCount === null) {
                        continue;
                    }

                    $updateData = [
                        'api_status' => $s['status'] ?? null,
                        'status'     => $newStatus,
                    ];
                    if ($startCount !== null) $updateData['start_count'] = $startCount;
                    if ($remains !== null)    $updateData['remains']     = $remains;
                    if ($newStatus === 'completed') $updateData['completed_at'] = date('Y-m-d H:i:s');

                    $this->db->update('smmPanel_orders', $updateData, ['id' => $order['id']]);

                    if ($newStatus !== $order['status']) {
                        $this->db->insert('smmPanel_order_logs', [
                            'order_id'   => $order['id'],
                            'old_status' => $order['status'],
                            'new_status' => $newStatus,
                            'message'    => "Auto-synced from provider. Remains: {$remains}",
                            'source'     => 'cron',
                        ]);

                        if (in_array($newStatus, ['completed', 'partial', 'cancelled'], true)) {
                            $messages = [
                                'completed' => "Your order #{$order['id']} has been completed!",
                                'partial'   => "Your order #{$order['id']} was partially completed.",
                                'cancelled' => "Your order #{$order['id']} was cancelled by the provider.",
                            ];

                            $this->notify->send(
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
                $this->db->insert('smmPanel_system_logs', [
                    'level'   => 'error',
                    'channel' => 'cron',
                    'message' => 'Order sync batch failed: ' . $e->getMessage(),
                    'context' => json_encode(['api_ids' => $apiIds]),
                ]);
            }

            usleep(200000); // 200ms pause between batches
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    private function mapProviderStatus(string $providerStatus): string
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

    // ── Scheduling helpers (stored in smmPanel_settings) ────────

    private function dueFor(string $key, int $intervalSeconds): bool
    {
        $row = $this->db->fetchOne(
            'SELECT `value` FROM smmPanel_settings WHERE `key` = ?',
            [$key]
        );

        if (!$row || empty($row['value'])) {
            return true; // never run before
        }

        $last = strtotime((string) $row['value']);
        if ($last === false) {
            return true;
        }

        return (time() - $last) >= $intervalSeconds;
    }

    private function touch(string $key): void
    {
        $this->setSetting($key, date('Y-m-d H:i:s'));
    }

    private function setSetting(string $key, string $value): void
    {
        $existing = $this->db->fetchOne(
            'SELECT `key` FROM smmPanel_settings WHERE `key` = ?',
            [$key]
        );

        if ($existing) {
            $this->db->query(
                'UPDATE smmPanel_settings SET `value` = ? WHERE `key` = ?',
                [$value, $key]
            );
        } else {
            $this->db->insert('smmPanel_settings', ['key' => $key, 'value' => $value]);
        }
    }
}
