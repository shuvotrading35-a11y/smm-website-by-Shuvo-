<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;
use SMMPanel\Services\SmmApiService;

/**
 * ApiController — user API key management and public v2 API endpoint.
 */
final class ApiController extends BaseController
{
    private Database $db;
    private SmmApiService $smmApi;

    public function __construct()
    {
        parent::__construct();
        $this->db     = Database::getInstance();
        $this->smmApi = new SmmApiService();
    }

    // ── User API Page ─────────────────────────────────────────

    public function userApiPage(array $params): void
    {
        $this->requireAuth();
        $userId = $this->currentUserId();
        $user   = $this->currentUser();

        $apiKey = $this->db->fetchOne(
            'SELECT * FROM smmPanel_api_keys WHERE user_id = ?',
            [$userId]
        );

        // Auto-generate key if none exists
        if (!$apiKey) {
            $apiKey = $this->generateApiKey($userId);
        }

        // Usage stats
        $usageToday = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_api_logs
             WHERE user_id = ? AND DATE(created_at) = CURDATE()',
            [$userId]
        );

        $usageMonth = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_api_logs
             WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())',
            [$userId]
        );

        $this->view('dashboard/api', [
            'title'      => 'API Access',
            'user'       => $user,
            'apiKey'     => $apiKey,
            'usageToday' => (int)$usageToday,
            'usageMonth' => (int)$usageMonth,
            'apiUrl'     => $this->getSetting('site_url') . '/api/v2',
            'csrf'       => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    public function regenerateKey(array $params): void
    {
        $this->requireAuth();
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'CSRF mismatch.'], 403);
        }

        $userId = $this->currentUserId();

        // Delete old key
        $this->db->query('DELETE FROM smmPanel_api_keys WHERE user_id = ?', [$userId]);

        // Generate new
        $apiKey = $this->generateApiKey($userId);

        $this->json([
            'success' => true,
            'message' => 'API key regenerated.',
            'prefix'  => $apiKey['key_prefix'],
        ]);
    }

    // ── AJAX service list (used by dashboard new-order) ───────

    public function getServices(array $params): void
    {
        $this->requireAuth();

        $catId = (int)($_GET['category_id'] ?? 0);

        if (!$catId) {
            $this->json(['success' => false, 'message' => 'category_id required.']);
        }

        $services = $this->db->fetchAll(
            'SELECT s.id, s.api_service_id,
                    COALESCE(s.custom_name, s.name) AS name,
                    COALESCE(s.custom_desc, s.description) AS description,
                    s.rate, s.markup_type, s.markup_value,
                    s.min_quantity, s.max_quantity, s.refill, s.cancel, s.type
             FROM smmPanel_services s
             WHERE s.category_id = ? AND s.is_active = 1
             ORDER BY s.sort_order, s.name',
            [$catId]
        );

        foreach ($services as &$s) {
            $s['user_rate'] = $this->applyMarkup(
                (float)$s['rate'],
                $s['markup_type'],
                (float)$s['markup_value']
            );
        }
        unset($s);

        $this->json(['success' => true, 'data' => $services]);
    }

    // ── Public v2 API (key-auth) ──────────────────────────────

    public function handleV2(array $params): void
    {
        header('Content-Type: application/json');

        $rawKey = $_POST['key'] ?? '';
        $action = strtolower(trim($_POST['action'] ?? ''));

        if (empty($rawKey)) {
            $this->json(['error' => 'API key required.'], 400);
        }

        // Validate API key
        $keyHash = hash('sha256', $rawKey);
        $keyRow  = $this->db->fetchOne(
            'SELECT ak.*, u.id AS user_id, u.balance, u.status
             FROM smmPanel_api_keys ak
             JOIN smmPanel_users u ON u.id = ak.user_id
             WHERE ak.key_hash = ? AND ak.is_active = 1',
            [$keyHash]
        );

        if (!$keyRow || $keyRow['status'] !== 'active') {
            $this->logApiCall(null, $action, ['error' => 'Invalid key']);
            $this->json(['error' => 'Invalid API key.'], 401);
        }

        $userId = (int)$keyRow['user_id'];

        // Update usage stats
        $this->db->query(
            'UPDATE smmPanel_api_keys
             SET requests_today = requests_today + 1,
                 requests_month = requests_month + 1,
                 requests_total = requests_total + 1,
                 last_used_at   = NOW()
             WHERE user_id = ?',
            [$userId]
        );

        // Dispatch action
        try {
            $response = match ($action) {
                'services' => $this->v2Services(),
                'add'      => $this->v2AddOrder($userId, $keyRow),
                'status'   => $this->v2Status($userId),
                'balance'  => $this->v2Balance($keyRow),
                'refill'   => $this->v2Refill($userId),
                'refill_status' => $this->v2RefillStatus($userId),
                'cancel'   => $this->v2Cancel($userId),
                default    => ['error' => "Unknown action: {$action}"],
            };
        } catch (\Throwable $e) {
            $response = ['error' => $e->getMessage()];
        }

        $this->logApiCall($userId, $action, null, $response);
        $this->json($response);
    }

    // ── v2 Action Handlers ────────────────────────────────────

    private function v2Services(): array
    {
        $services = $this->db->fetchAll(
            'SELECT s.api_service_id AS service,
                    COALESCE(s.custom_name, s.name) AS name,
                    sc.name AS category,
                    s.type,
                    s.rate,
                    s.min_quantity AS min,
                    s.max_quantity AS max,
                    s.refill,
                    s.cancel
             FROM smmPanel_services s
             JOIN smmPanel_service_categories sc ON sc.id = s.category_id
             WHERE s.is_active = 1
             ORDER BY sc.sort_order, s.sort_order'
        );

        return $services;
    }

    private function v2AddOrder(int $userId, array $keyRow): array
    {
        $serviceApiId = (int)($_POST['service'] ?? 0);
        $link         = trim($_POST['link'] ?? '');
        $quantity     = (int)($_POST['quantity'] ?? 0);

        if (!$serviceApiId || !$link || !$quantity) {
            return ['error' => 'service, link, and quantity are required.'];
        }

        $service = $this->db->fetchOne(
            'SELECT * FROM smmPanel_services WHERE api_service_id = ? AND is_active = 1',
            [$serviceApiId]
        );

        if (!$service) {
            return ['error' => 'Service not found or inactive.'];
        }

        if ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
            return ['error' => "Quantity must be between {$service['min_quantity']} and {$service['max_quantity']}."];
        }

        $rate   = $this->applyMarkup((float)$service['rate'], $service['markup_type'], (float)$service['markup_value']);
        $charge = round($quantity * $rate / 1000, 4);

        if ((float)$keyRow['balance'] < $charge) {
            return ['error' => 'Insufficient balance.'];
        }

        // Place via provider
        $apiResponse = $this->smmApi->placeOrder((int)$service['api_service_id'], $link, $quantity);
        $apiOrderId  = $apiResponse['order'] ?? null;

        // Save to DB
        $orderId = $this->db->transaction(function (Database $db) use ($userId, $service, $apiOrderId, $link, $quantity, $charge, $keyRow) {
            $db->query(
                'UPDATE smmPanel_users SET balance = balance - ?, total_spent = total_spent + ?, total_orders = total_orders + 1 WHERE id = ?',
                [$charge, $charge, $userId]
            );

            $orderId = (int) $db->insert('smmPanel_orders', [
                'user_id'      => $userId,
                'service_id'   => $service['id'],
                'api_order_id' => $apiOrderId,
                'link'         => $link,
                'quantity'     => $quantity,
                'charge'       => $charge,
                'status'       => 'pending',
            ]);

            $db->insert('smmPanel_transactions', [
                'user_id'        => $userId,
                'type'           => 'order',
                'amount'         => -$charge,
                'balance_before' => (float)$keyRow['balance'],
                'balance_after'  => (float)$keyRow['balance'] - $charge,
                'reference_id'   => $orderId,
                'reference_type' => 'order',
                'description'    => "API Order #{$orderId}",
            ]);

            return $orderId;
        });

        return ['order' => $orderId];
    }

    private function v2Status(int $userId): array
    {
        if (!empty($_POST['orders'])) {
            $orderIds = array_map('intval', explode(',', $_POST['orders']));
        } else {
            $orderIds = [(int)($_POST['order'] ?? 0)];
        }

        $orderIds = array_filter($orderIds);
        if (empty($orderIds)) {
            return ['error' => 'order or orders parameter required.'];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id AS order_id, status, start_count, remains, quantity, charge
             FROM smmPanel_orders
             WHERE id IN ({$placeholders}) AND user_id = ?",
            array_merge($orderIds, [$userId])
        );

        if (count($orderIds) === 1) {
            return $rows[0] ?? ['error' => 'Order not found.'];
        }

        return array_column($rows, null, 'order_id');
    }

    private function v2Balance(array $keyRow): array
    {
        return ['balance' => number_format((float)$keyRow['balance'], 4, '.', '')];
    }

    private function v2Refill(int $userId): array
    {
        $orderId = (int)($_POST['order'] ?? 0);

        $order = $this->db->fetchOne(
            'SELECT o.*, s.refill AS can_refill
             FROM smmPanel_orders o JOIN smmPanel_services s ON s.id = o.service_id
             WHERE o.id = ? AND o.user_id = ?',
            [$orderId, $userId]
        );

        if (!$order || !$order['can_refill']) {
            return ['error' => 'Refill not available.'];
        }

        $response = $this->smmApi->requestRefill($order['api_order_id']);

        if (isset($response['refill'])) {
            $this->db->query(
                'UPDATE smmPanel_orders SET refill_id = ?, refill_status = "pending" WHERE id = ?',
                [$response['refill'], $orderId]
            );
        }

        return ['refill' => $response['refill'] ?? null];
    }

    private function v2RefillStatus(int $userId): array
    {
        $refillId = trim($_POST['refill'] ?? '');
        if (!$refillId) return ['error' => 'refill parameter required.'];

        $response = $this->smmApi->getRefillStatus($refillId);
        return $response;
    }

    private function v2Cancel(int $userId): array
    {
        $orderIds = array_map('intval', explode(',', $_POST['orders'] ?? ''));
        $orderIds = array_filter($orderIds);

        if (empty($orderIds)) {
            return ['error' => 'orders parameter required.'];
        }

        // Verify ownership
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $owned = $this->db->fetchAll(
            "SELECT id, api_order_id FROM smmPanel_orders WHERE id IN ({$placeholders}) AND user_id = ?",
            array_merge($orderIds, [$userId])
        );

        if (count($owned) !== count($orderIds)) {
            return ['error' => 'One or more orders not found.'];
        }

        $apiIds = array_column($owned, 'api_order_id');
        $response = $this->smmApi->cancelOrders($apiIds);

        $this->db->query(
            "UPDATE smmPanel_orders SET status = 'cancelled' WHERE id IN ({$placeholders}) AND user_id = ?",
            array_merge($orderIds, [$userId])
        );

        return $response;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function generateApiKey(int $userId): array
    {
        $rawKey  = bin2hex(random_bytes(32));                // 64-char hex
        $keyHash = hash('sha256', $rawKey);
        $prefix  = substr($rawKey, 0, 8);

        $this->db->insert('smmPanel_api_keys', [
            'user_id'    => $userId,
            'key_prefix' => $prefix,
            'key_hash'   => $keyHash,
            'is_active'  => 1,
        ]);

        // Store full key in session so we can show it once
        $_SESSION['new_api_key'] = $rawKey;

        return [
            'key_prefix' => $prefix,
            'key_hash'   => $keyHash,
            'full_key'   => $rawKey,
        ];
    }

    private function applyMarkup(float $rate, string $type, float $value): float
    {
        if ($value <= 0) return $rate;

        return match ($type) {
            'percent' => $rate * (1 + $value / 100),
            'fixed'   => $rate + $value,
            default   => $rate,
        };
    }

    private function logApiCall(?int $userId, string $action, ?array $request = null, ?array $response = null): void
    {
        $this->db->insert('smmPanel_api_logs', [
            'user_id'    => $userId,
            'action'     => $action,
            'request'    => $request  ? json_encode($request)  : null,
            'response'   => $response ? json_encode($response) : null,
            'ip_address' => $this->getClientIp(),
        ]);
    }
}
