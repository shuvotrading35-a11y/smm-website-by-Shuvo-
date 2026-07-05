<?php

declare(strict_types=1);

namespace SMMPanel\Services;

use SMMPanel\Core\Config;
use SMMPanel\Core\Database;

/**
 * SmmApiService — all external SMM provider API calls happen here.
 *
 * API key is NEVER sent to the browser. All calls are server-side.
 */
final class SmmApiService
{
    private string $apiUrl;
    private string $apiKey;
    private Database $db;

    public function __construct()
    {
        $this->apiUrl = Config::required('SMM_API_URL');
        $this->apiKey = Config::required('SMM_API_KEY');
        $this->db     = Database::getInstance();
    }

    // ── Public API Methods ────────────────────────────────────

    /**
     * Fetch all services from provider.
     */
    public function fetchServices(): array
    {
        return $this->call(['action' => 'services']);
    }

    /**
     * Place a new order.
     */
    public function placeOrder(int $serviceId, string $link, int $quantity): array
    {
        return $this->call([
            'action'   => 'add',
            'service'  => $serviceId,
            'link'     => $link,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Get status of a single order.
     */
    public function getOrderStatus(string|int $orderId): array
    {
        return $this->call([
            'action' => 'status',
            'order'  => $orderId,
        ]);
    }

    /**
     * Get status of multiple orders (bulk).
     *
     * @param array $orderIds Array of provider order IDs
     */
    public function getBulkStatus(array $orderIds): array
    {
        return $this->call([
            'action' => 'status',
            'orders' => implode(',', $orderIds),
        ]);
    }

    /**
     * Get provider account balance.
     */
    public function getBalance(): array
    {
        return $this->call(['action' => 'balance']);
    }

    /**
     * Request a refill for an order.
     */
    public function requestRefill(string|int $orderId): array
    {
        return $this->call([
            'action' => 'refill',
            'order'  => $orderId,
        ]);
    }

    /**
     * Check status of a refill request.
     */
    public function getRefillStatus(string $refillId): array
    {
        return $this->call([
            'action' => 'refill_status',
            'refill' => $refillId,
        ]);
    }

    /**
     * Cancel one or more orders.
     *
     * @param array|string $orderIds Single ID or array of IDs
     */
    public function cancelOrders(array|string $orderIds): array
    {
        $ids = is_array($orderIds) ? implode(',', $orderIds) : $orderIds;

        return $this->call([
            'action' => 'cancel',
            'orders' => $ids,
        ]);
    }

    // ── Core HTTP Caller ──────────────────────────────────────

    /**
     * POST to the provider API, log request/response, return decoded array.
     *
     * @throws \RuntimeException on curl failure or non-200 HTTP status
     */
    private function call(array $params, int $timeoutSeconds = 30): array
    {
        $payload = array_merge(['key' => $this->apiKey], $params);
        $start   = microtime(true);
        $error   = null;
        $rawResponse = '';
        $httpCode    = 0;

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => $this->apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeoutSeconds,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'ShuvoSMMPanel/1.0',
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);

            $rawResponse = curl_exec($ch);
            $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError   = curl_error($ch);

            curl_close($ch);

            if ($rawResponse === false) {
                throw new \RuntimeException("cURL error: {$curlError}");
            }

            $decoded = json_decode($rawResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from provider.');
            }

            if (isset($decoded['error'])) {
                throw new \RuntimeException('Provider error: ' . $decoded['error']);
            }

            return $decoded;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            throw $e;
        } finally {
            // Log every call (strip API key from logged params)
            $safeParams = $params; // Key not included in $params
            $duration   = (int) ((microtime(true) - $start) * 1000);

            $this->db->insert('smmPanel_api_logs', [
                'user_id'     => null,
                'action'      => $params['action'] ?? 'unknown',
                'endpoint'    => $this->apiUrl,
                'request'     => json_encode($safeParams),
                'response'    => $rawResponse ? (strlen($rawResponse) > 5000 ? substr($rawResponse, 0, 5000) . '...' : $rawResponse) : null,
                'status_code' => $httpCode,
                'duration_ms' => $duration,
                'error'       => $error,
            ]);
        }
    }

    // ── Service Cache ─────────────────────────────────────────

    /**
     * Sync services from provider into local DB.
     * Called by cron every 6 hours.
     */
    public function syncServicesToDb(): array
    {
        $services = $this->fetchServices();

        if (empty($services)) {
            return ['added' => 0, 'updated' => 0, 'errors' => 'Empty response'];
        }

        $added   = 0;
        $updated = 0;
        $errors  = [];

        foreach ($services as $s) {
            try {
                $existing = $this->db->fetchOne(
                    'SELECT id FROM smmPanel_services WHERE api_service_id = ?',
                    [(int) $s['service']]
                );

                // Find or create category
                $categoryId = $this->ensureCategory($s['category'] ?? 'Other');

                $data = [
                    'api_service_id' => (int) ($s['service'] ?? 0),
                    'category_id'    => $categoryId,
                    'name'           => substr($s['name'] ?? 'Unnamed Service', 0, 255),
                    'description'    => $s['description'] ?? null,
                    'type'           => $s['type'] ?? null,
                    'rate'           => (float) ($s['rate'] ?? 0),
                    'min_quantity'   => (int) ($s['min'] ?? 10),
                    'max_quantity'   => (int) ($s['max'] ?? 10000),
                    'refill'         => isset($s['refill']) && $s['refill'] ? 1 : 0,
                    'cancel'         => isset($s['cancel']) && $s['cancel'] ? 1 : 0,
                ];

                if ($existing) {
                    $this->db->update('smmPanel_services', $data, ['id' => $existing['id']]);
                    $updated++;
                } else {
                    $this->db->insert('smmPanel_services', $data);
                    $added++;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Service ' . ($s['service'] ?? '?') . ': ' . $e->getMessage();
            }
        }

        // Log sync
        $this->db->insert('smmPanel_service_cache_log', [
            'total_count' => count($services),
            'added'       => $added,
            'updated'     => $updated,
            'errors'      => $errors ? implode("\n", $errors) : null,
        ]);

        return compact('added', 'updated', 'errors');
    }

    /**
     * Ensure a category exists by name, return its ID.
     */
    private function ensureCategory(string $name): int
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

        $existing = $this->db->fetchOne(
            'SELECT id FROM smmPanel_service_categories WHERE slug = ?',
            [$slug]
        );

        if ($existing) {
            return (int) $existing['id'];
        }

        return (int) $this->db->insert('smmPanel_service_categories', [
            'name'       => $name,
            'slug'       => $slug,
            'sort_order' => 99,
        ]);
    }
}
