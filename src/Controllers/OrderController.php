<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;
use SMMPanel\Services\SmmApiService;
use SMMPanel\Services\NotificationService;

/**
 * OrderController — handles order placement and management.
 */
final class OrderController extends BaseController
{
    private Database $db;
    private SmmApiService $api;
    private NotificationService $notify;

    public function __construct()
    {
        parent::__construct();
        $this->db     = Database::getInstance();
        $this->api    = new SmmApiService();
        $this->notify = new NotificationService();
        $this->requireAuth();
    }

    // ── New Order Page ────────────────────────────────────────

    public function newOrder(array $params): void
    {
        $userId = $this->currentUserId();
        $user   = $this->currentUser();

        // Categories with at least one active service
        $categories = $this->db->fetchAll(
            'SELECT sc.id, sc.name, sc.icon, sc.color, COUNT(s.id) as service_count
             FROM smmPanel_service_categories sc
             JOIN smmPanel_services s ON s.category_id = sc.id AND s.is_active = 1
             GROUP BY sc.id
             ORDER BY sc.sort_order ASC'
        );

        // Favorites (to mark in service list)
        $favorites = $this->db->fetchAll(
            'SELECT service_id FROM smmPanel_favorites WHERE user_id = ?',
            [$userId]
        );
        $favoriteIds = array_column($favorites, 'service_id');

        // Pre-selected service from query param
        $preServiceId = (int)($_GET['service'] ?? 0);
        $preService   = null;

        if ($preServiceId) {
            $preService = $this->getServiceForUser($preServiceId);
        }

        $this->view('dashboard/new-order', [
            'title'       => 'New Order',
            'user'        => $user,
            'categories'  => $categories,
            'favoriteIds' => $favoriteIds,
            'preService'  => $preService,
            'csrf'        => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    // ── AJAX: Get services by category ────────────────────────

    public function getServicesByCategory(array $params): void
    {
        $categoryId = (int)($_GET['category_id'] ?? 0);

        if (!$categoryId) {
            $this->json(['success' => false, 'message' => 'Category required.']);
        }

        $services = $this->db->fetchAll(
            'SELECT s.id, s.api_service_id,
                    COALESCE(s.custom_name, s.name) AS name,
                    COALESCE(s.custom_desc, s.description) AS description,
                    s.rate, s.markup_type, s.markup_value,
                    s.min_quantity, s.max_quantity, s.refill, s.cancel, s.type
             FROM smmPanel_services s
             WHERE s.category_id = ? AND s.is_active = 1
             ORDER BY s.sort_order ASC, s.name ASC',
            [$categoryId]
        );

        // Apply markup
        foreach ($services as &$service) {
            $service['user_rate'] = $this->applyMarkup(
                (float)$service['rate'],
                $service['markup_type'],
                (float)$service['markup_value']
            );
        }
        unset($service);

        $this->json(['success' => true, 'data' => $services]);
    }

    // ── Place Order ───────────────────────────────────────────

    public function placeOrder(array $params): void
    {
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'Security token mismatch.'], 403);
        }

        $userId    = $this->currentUserId();
        $user      = $this->currentUser();
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $link      = trim($_POST['link'] ?? '');
        $quantity  = (int)($_POST['quantity'] ?? 0);
        $couponCode = strtoupper(trim($_POST['coupon'] ?? ''));

        // Validation
        if (!$serviceId || empty($link) || $quantity <= 0) {
            $this->json(['success' => false, 'message' => 'All fields are required.']);
        }

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $this->json(['success' => false, 'message' => 'Please enter a valid URL.']);
        }

        $service = $this->getServiceForUser($serviceId);

        if (!$service) {
            $this->json(['success' => false, 'message' => 'Service not found or unavailable.']);
        }

        if ($quantity < (int)$service['min_quantity'] || $quantity > (int)$service['max_quantity']) {
            $this->json([
                'success' => false,
                'message' => "Quantity must be between {$service['min_quantity']} and {$service['max_quantity']}.",
            ]);
        }

        $userRate = $this->applyMarkup(
            (float)$service['rate'],
            $service['markup_type'],
            (float)$service['markup_value']
        );
        $charge = round($quantity * $userRate / 1000, 4);

        // Coupon
        $couponId      = null;
        $discountAmount = 0.0;

        if ($couponCode) {
            $couponData = $this->validateCoupon($couponCode, $charge, $userId);

            if ($couponData['valid']) {
                $couponId       = $couponData['id'];
                $discountAmount = $couponData['discount'];
                $charge         = max(0, $charge - $discountAmount);
            }
        }

        // Balance check
        if ((float)$user['balance'] < $charge) {
            $this->json([
                'success' => false,
                'message' => 'Insufficient balance. Please add funds.',
                'redirect' => '/dashboard/add-funds',
            ]);
        }

        // Place order via API
        try {
            $apiResponse = $this->api->placeOrder(
                (int)$service['api_service_id'],
                $link,
                $quantity
            );
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to place order. Please try again.']);
        }

        $apiOrderId = $apiResponse['order'] ?? null;

        // Save order in DB (transaction)
        $orderId = $this->db->transaction(function (Database $db) use (
            $userId, $serviceId, $apiOrderId, $link, $quantity, $charge,
            $discountAmount, $couponId, $user
        ) {
            // Deduct balance
            $db->query(
                'UPDATE smmPanel_users SET balance = balance - ?, total_spent = total_spent + ?, total_orders = total_orders + 1 WHERE id = ?',
                [$charge, $charge, $userId]
            );

            // Insert order
            $orderId = (int) $db->insert('smmPanel_orders', [
                'user_id'        => $userId,
                'service_id'     => $serviceId,
                'api_order_id'   => $apiOrderId,
                'link'           => $link,
                'quantity'       => $quantity,
                'charge'         => $charge,
                'status'         => 'pending',
                'coupon_id'      => $couponId,
                'discount_amount'=> $discountAmount,
                'ip_address'     => $this->getClientIp(),
            ]);

            // Transaction log
            $newBalance = (float)$user['balance'] - $charge;
            $db->insert('smmPanel_transactions', [
                'user_id'        => $userId,
                'type'           => 'order',
                'amount'         => -$charge,
                'balance_before' => (float)$user['balance'],
                'balance_after'  => $newBalance,
                'reference_id'   => $orderId,
                'reference_type' => 'order',
                'description'    => "Order #{$orderId}",
            ]);

            // Coupon usage
            if ($couponId) {
                $db->insert('smmPanel_coupon_usage', [
                    'coupon_id' => $couponId,
                    'user_id'   => $userId,
                    'order_id'  => $orderId,
                    'discount'  => $discountAmount,
                ]);

                $db->query(
                    'UPDATE smmPanel_coupons SET total_used = total_used + 1 WHERE id = ?',
                    [$couponId]
                );
            }

            // Referral commission
            $this->creditReferralEarning($userId, $charge, $orderId, $db);

            return $orderId;
        });

        $this->notify->send($userId, 'order_placed', 'Order Placed', "Your order #{$orderId} has been placed successfully.", ['order_id' => $orderId]);

        $this->json([
            'success'  => true,
            'message'  => "Order #{$orderId} placed successfully!",
            'order_id' => $orderId,
        ]);
    }

    // ── Order List ────────────────────────────────────────────

    public function listOrders(array $params): void
    {
        $userId = $this->currentUserId();
        $user   = $this->currentUser();

        $where  = ['o.user_id = ?'];
        $bindParams = [$userId];

        if (!empty($_GET['status'])) {
            $where[]      = 'o.status = ?';
            $bindParams[] = $_GET['status'];
        }

        if (!empty($_GET['search'])) {
            $search       = '%' . $this->db->escapeLike($_GET['search']) . '%';
            $where[]      = '(o.link LIKE ? OR o.id LIKE ?)';
            $bindParams[] = $search;
            $bindParams[] = $search;
        }

        if (!empty($_GET['date_from'])) {
            $where[]      = 'DATE(o.created_at) >= ?';
            $bindParams[] = $_GET['date_from'];
        }

        if (!empty($_GET['date_to'])) {
            $where[]      = 'DATE(o.created_at) <= ?';
            $bindParams[] = $_GET['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        $pagination = $this->paginate(
            "SELECT o.id, o.link, o.quantity, o.charge, o.status, o.start_count,
                    o.remains, o.created_at, o.api_order_id,
                    s.name AS service_name, sc.name AS category, sc.icon
             FROM smmPanel_orders o
             JOIN smmPanel_services s ON s.id = o.service_id
             JOIN smmPanel_service_categories sc ON sc.id = s.category_id
             WHERE {$whereClause}
             ORDER BY o.created_at DESC",
            $bindParams,
            20
        );

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => $pagination]);
        }

        $this->view('dashboard/orders', [
            'title'      => 'My Orders',
            'user'       => $user,
            'pagination' => $pagination,
            'filters'    => $_GET,
            'csrf'       => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    // ── Order Detail ──────────────────────────────────────────

    public function orderDetail(array $params): void
    {
        $orderId = (int)($params['id'] ?? 0);
        $userId  = $this->currentUserId();

        $order = $this->db->fetchOne(
            'SELECT o.*, s.name AS service_name, s.refill AS can_refill, s.cancel AS can_cancel,
                    sc.name AS category, sc.icon
             FROM smmPanel_orders o
             JOIN smmPanel_services s ON s.id = o.service_id
             JOIN smmPanel_service_categories sc ON sc.id = s.category_id
             WHERE o.id = ? AND o.user_id = ?',
            [$orderId, $userId]
        );

        if (!$order) {
            $this->redirect('/dashboard/orders');
        }

        $logs = $this->db->fetchAll(
            'SELECT * FROM smmPanel_order_logs WHERE order_id = ? ORDER BY created_at DESC',
            [$orderId]
        );

        if ($this->isAjax()) {
            $this->json(['success' => true, 'order' => $order, 'logs' => $logs]);
        }

        $this->view('dashboard/order-detail', [
            'title' => "Order #{$orderId}",
            'order' => $order,
            'logs'  => $logs,
            'csrf'  => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    // ── Request Refill ────────────────────────────────────────

    public function requestRefill(array $params): void
    {
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'CSRF mismatch.'], 403);
        }

        $orderId = (int)($params['id'] ?? 0);
        $userId  = $this->currentUserId();

        $order = $this->db->fetchOne(
            'SELECT o.*, s.refill AS can_refill, s.api_service_id
             FROM smmPanel_orders o
             JOIN smmPanel_services s ON s.id = o.service_id
             WHERE o.id = ? AND o.user_id = ?',
            [$orderId, $userId]
        );

        if (!$order || !$order['can_refill']) {
            $this->json(['success' => false, 'message' => 'Refill not available for this order.']);
        }

        if (!in_array($order['status'], ['completed', 'partial'], true)) {
            $this->json(['success' => false, 'message' => 'Refill only available for completed or partial orders.']);
        }

        try {
            $response = $this->api->requestRefill($order['api_order_id']);
            $refillId = $response['refill'] ?? null;

            if ($refillId) {
                $this->db->query(
                    'UPDATE smmPanel_orders SET refill_id = ?, refill_status = "pending" WHERE id = ?',
                    [$refillId, $orderId]
                );
            }

            $this->json(['success' => true, 'message' => 'Refill requested successfully.']);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Refill request failed. Please try again.']);
        }
    }

    // ── Cancel Order ──────────────────────────────────────────

    public function cancelOrder(array $params): void
    {
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'CSRF mismatch.'], 403);
        }

        $orderId = (int)($params['id'] ?? 0);
        $userId  = $this->currentUserId();
        $user    = $this->currentUser();

        $order = $this->db->fetchOne(
            'SELECT o.*, s.cancel AS can_cancel
             FROM smmPanel_orders o
             JOIN smmPanel_services s ON s.id = o.service_id
             WHERE o.id = ? AND o.user_id = ?',
            [$orderId, $userId]
        );

        if (!$order || !$order['can_cancel']) {
            $this->json(['success' => false, 'message' => 'This order cannot be cancelled.']);
        }

        if (!in_array($order['status'], ['pending', 'processing'], true)) {
            $this->json(['success' => false, 'message' => 'Order cannot be cancelled at this stage.']);
        }

        try {
            $this->api->cancelOrders($order['api_order_id']);

            // Partial refund (refund remaining unfulfilled portion)
            $refundAmount = $order['remains']
                ? round($order['remains'] * ((float)$order['charge'] / $order['quantity']), 4)
                : (float)$order['charge'];

            $this->db->transaction(function (Database $db) use ($order, $refundAmount, $user) {
                $db->query(
                    'UPDATE smmPanel_orders SET status = "cancelled", cancel_note = "Cancelled by user" WHERE id = ?',
                    [$order['id']]
                );

                if ($refundAmount > 0) {
                    $db->query(
                        'UPDATE smmPanel_users SET balance = balance + ? WHERE id = ?',
                        [$refundAmount, $order['user_id']]
                    );

                    $newBalance = (float)$user['balance'] + $refundAmount;

                    $db->insert('smmPanel_transactions', [
                        'user_id'        => $order['user_id'],
                        'type'           => 'refund',
                        'amount'         => $refundAmount,
                        'balance_before' => (float)$user['balance'],
                        'balance_after'  => $newBalance,
                        'reference_id'   => $order['id'],
                        'reference_type' => 'order',
                        'description'    => "Refund for cancelled order #{$order['id']}",
                    ]);
                }
            });

            $this->json([
                'success' => true,
                'message' => 'Order cancelled.' . ($refundAmount > 0 ? " \${$refundAmount} refunded." : ''),
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Cancellation failed. Please try again.']);
        }
    }

    // ── Order Status Checker ──────────────────────────────────

    public function orderStatus(array $params): void
    {
        $this->view('dashboard/order-status', [
            'title' => 'Check Order Status',
            'user'  => $this->currentUser(),
            'csrf'  => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    public function checkStatus(array $params): void
    {
        $orderIds = array_filter(
            array_map('intval', explode(',', $_POST['order_ids'] ?? ''))
        );

        if (empty($orderIds)) {
            $this->json(['success' => false, 'message' => 'Please enter at least one order ID.']);
        }

        $userId = $this->currentUserId();

        // Validate ownership
        $owned = $this->db->fetchAll(
            sprintf(
                'SELECT id FROM smmPanel_orders WHERE id IN (%s) AND user_id = ?',
                implode(',', array_fill(0, count($orderIds), '?'))
            ),
            array_merge($orderIds, [$userId])
        );

        if (count($owned) !== count($orderIds)) {
            $this->json(['success' => false, 'message' => 'One or more orders not found.']);
        }

        $rows = $this->db->fetchAll(
            sprintf(
                'SELECT o.id, o.status, o.start_count, o.remains, o.charge, o.created_at,
                        s.name AS service_name
                 FROM smmPanel_orders o
                 JOIN smmPanel_services s ON s.id = o.service_id
                 WHERE o.id IN (%s) AND o.user_id = ?',
                implode(',', array_fill(0, count($orderIds), '?'))
            ),
            array_merge($orderIds, [$userId])
        );

        $this->json(['success' => true, 'data' => $rows]);
    }

    // ── Favorites ─────────────────────────────────────────────

    public function favorites(array $params): void
    {
        $userId = $this->currentUserId();

        $favorites = $this->db->fetchAll(
            'SELECT f.id, f.created_at,
                    s.id AS service_id, COALESCE(s.custom_name, s.name) AS name,
                    s.rate, s.markup_type, s.markup_value, s.min_quantity, s.max_quantity,
                    sc.name AS category, sc.icon
             FROM smmPanel_favorites f
             JOIN smmPanel_services s ON s.id = f.service_id
             JOIN smmPanel_service_categories sc ON sc.id = s.category_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC',
            [$userId]
        );

        foreach ($favorites as &$fav) {
            $fav['user_rate'] = $this->applyMarkup(
                (float)$fav['rate'],
                $fav['markup_type'],
                (float)$fav['markup_value']
            );
        }
        unset($fav);

        $this->view('dashboard/favorites', [
            'title'     => 'Favorites',
            'user'      => $this->currentUser(),
            'favorites' => $favorites,
            'csrf'      => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    public function toggleFavorite(array $params): void
    {
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'CSRF mismatch.'], 403);
        }

        $serviceId = (int)($_POST['service_id'] ?? 0);
        $userId    = $this->currentUserId();

        $existing = $this->db->fetchOne(
            'SELECT id FROM smmPanel_favorites WHERE user_id = ? AND service_id = ?',
            [$userId, $serviceId]
        );

        if ($existing) {
            $this->db->query(
                'DELETE FROM smmPanel_favorites WHERE id = ?',
                [$existing['id']]
            );
            $this->json(['success' => true, 'action' => 'removed']);
        } else {
            $this->db->insert('smmPanel_favorites', [
                'user_id'    => $userId,
                'service_id' => $serviceId,
            ]);
            $this->json(['success' => true, 'action' => 'added']);
        }
    }

    // ── Private Helpers ───────────────────────────────────────

    private function getServiceForUser(int $serviceId): array|false
    {
        return $this->db->fetchOne(
            'SELECT s.*, COALESCE(s.custom_name, s.name) AS display_name
             FROM smmPanel_services s
             WHERE s.id = ? AND s.is_active = 1',
            [$serviceId]
        );
    }

    private function applyMarkup(float $rate, string $type, float $value): float
    {
        if ($value <= 0) {
            return $rate;
        }

        return match ($type) {
            'percent' => $rate * (1 + $value / 100),
            'fixed'   => $rate + $value,
            default   => $rate,
        };
    }

    private function validateCoupon(string $code, float $amount, int $userId): array
    {
        $coupon = $this->db->fetchOne(
            'SELECT * FROM smmPanel_coupons
             WHERE code = ? AND is_active = 1
               AND (expires_at IS NULL OR expires_at > NOW())
               AND (max_uses IS NULL OR total_used < max_uses)',
            [$code]
        );

        if (!$coupon || (float)$coupon['min_order'] > $amount) {
            return ['valid' => false];
        }

        $usage = (int)$this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_coupon_usage WHERE coupon_id = ? AND user_id = ?',
            [$coupon['id'], $userId]
        );

        if ($usage >= (int)$coupon['uses_per_user']) {
            return ['valid' => false];
        }

        $discount = match ($coupon['type']) {
            'percent' => min(
                $amount * ((float)$coupon['value'] / 100),
                $coupon['max_discount'] ? (float)$coupon['max_discount'] : PHP_FLOAT_MAX
            ),
            default => min((float)$coupon['value'], $amount),
        };

        return ['valid' => true, 'id' => $coupon['id'], 'discount' => round($discount, 4)];
    }

    private function creditReferralEarning(int $userId, float $charge, int $orderId, Database $db): void
    {
        if (!(bool)$this->getSetting('referral_enabled', true)) {
            return;
        }

        $referral = $db->fetchOne(
            'SELECT * FROM smmPanel_referrals WHERE referred_id = ? AND status = "active"',
            [$userId]
        );

        if (!$referral) {
            return;
        }

        $percent = (float)$this->getSetting('referral_percent', 2);
        $earned  = round($charge * $percent / 100, 4);

        if ($earned <= 0) {
            return;
        }

        $db->query(
            'UPDATE smmPanel_users SET balance = balance + ? WHERE id = ?',
            [$earned, $referral['referrer_id']]
        );

        $db->insert('smmPanel_referral_earnings', [
            'referrer_id'  => $referral['referrer_id'],
            'referred_id'  => $userId,
            'order_id'     => $orderId,
            'order_amount' => $charge,
            'percent'      => $percent,
            'earned'       => $earned,
        ]);
    }
}
