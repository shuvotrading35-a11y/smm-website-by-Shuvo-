<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;
use SMMPanel\Services\SmmApiService;
use SMMPanel\Services\NotificationService;
use SMMPanel\Services\EmailService;

/**
 * AdminController — all admin panel functionality.
 */
final class AdminController extends BaseController
{
    private Database $db;
    private SmmApiService $api;
    private NotificationService $notify;
    private EmailService $mailer;

    public function __construct()
    {
        parent::__construct();
        $this->db     = Database::getInstance();
        $this->api    = new SmmApiService();
        $this->notify = new NotificationService();
        $this->mailer = new EmailService();
    }

    // ── Admin Login ───────────────────────────────────────────

    public function login(array $params): void
    {
        $user = $this->currentUser();
        if ($user && in_array($user['role'], ['admin', 'superadmin'], true)) {
            $this->redirect('/admin');
        }

        if ($this->isPost()) {
            $identifier = strtolower(trim($_POST['identifier'] ?? ''));
            $password   = $_POST['password'] ?? '';

            $user = $this->db->fetchOne(
                'SELECT * FROM smmPanel_users
                 WHERE (email = ? OR username = ?)
                   AND role IN ("admin","superadmin")
                   AND status = "active"
                   AND deleted_at IS NULL
                 LIMIT 1',
                [$identifier, $identifier]
            );

            if (!$user || !password_verify($password, $user['password'])) {
                $this->json(['success' => false, 'message' => 'Invalid credentials.']);
            }

            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['login_at'] = time();

            $this->db->query(
                'UPDATE smmPanel_users SET last_login_at = NOW(), last_login_ip = ?, login_attempts = 0 WHERE id = ?',
                [$this->getClientIp(), $user['id']]
            );

            $this->logAdminAction('login', 'user', $user['id']);
            $this->json(['success' => true, 'redirect' => '/admin']);
        }

        $this->view('admin/login', ['title' => 'Admin Login'], 'main');
    }

    public function logout(array $params): void
    {
        $this->logAdminAction('logout', null, null);
        $_SESSION = [];
        session_destroy();
        $this->redirect('/admin/login');
    }

    // ── Admin Dashboard ───────────────────────────────────────

    public function dashboard(array $params): void
    {
        $this->requireAdmin();

        $kpis = $this->db->fetchOne(
            'SELECT
               (SELECT COUNT(*) FROM smmPanel_users WHERE deleted_at IS NULL AND status != "deleted") AS total_users,
               (SELECT COUNT(*) FROM smmPanel_users WHERE DATE(created_at) = CURDATE()) AS new_users_today,
               (SELECT COUNT(*) FROM smmPanel_orders) AS total_orders,
               (SELECT COUNT(*) FROM smmPanel_orders WHERE DATE(created_at) = CURDATE()) AS orders_today,
               (SELECT COALESCE(SUM(charge),0) FROM smmPanel_orders WHERE status != "refunded") AS total_revenue,
               (SELECT COALESCE(SUM(charge),0) FROM smmPanel_orders WHERE DATE(created_at) = CURDATE()) AS revenue_today,
               (SELECT COUNT(*) FROM smmPanel_deposits WHERE status = "pending") AS pending_deposits,
               (SELECT COUNT(*) FROM smmPanel_support_tickets WHERE status IN ("open","in_progress")) AS open_tickets'
        );

        // Revenue chart (last 14 days)
        $revenueChart = $this->db->fetchAll(
            'SELECT DATE(created_at) AS day, SUM(charge) AS revenue, COUNT(*) AS orders
             FROM smmPanel_orders
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND status != "refunded"
             GROUP BY DATE(created_at)
             ORDER BY day ASC'
        );

        // Recent signups
        $recentUsers = $this->db->fetchAll(
            'SELECT id, username, email, full_name, balance, status, created_at
             FROM smmPanel_users
             WHERE deleted_at IS NULL
             ORDER BY created_at DESC LIMIT 8'
        );

        // API balance
        try {
            $apiBalance = $this->api->getBalance();
            $providerBalance = $apiBalance['balance'] ?? '—';
        } catch (\Throwable) {
            $providerBalance = 'Error';
        }

        $this->view('admin/dashboard', [
            'title'          => 'Admin Dashboard',
            'kpis'           => $kpis,
            'revenueChart'   => $revenueChart,
            'recentUsers'    => $recentUsers,
            'providerBalance'=> $providerBalance,
        ], 'admin');
    }

    // ── Users ─────────────────────────────────────────────────

    public function users(array $params): void
    {
        $this->requireAdmin();

        $where  = ['deleted_at IS NULL'];
        $binds  = [];

        if (!empty($_GET['status'])) {
            $where[] = 'status = ?';
            $binds[] = $_GET['status'];
        }

        if (!empty($_GET['role'])) {
            $where[] = 'role = ?';
            $binds[] = $_GET['role'];
        }

        if (!empty($_GET['q'])) {
            $search  = '%' . $this->db->escapeLike($_GET['q']) . '%';
            $where[] = '(username LIKE ? OR email LIKE ? OR full_name LIKE ?)';
            array_push($binds, $search, $search, $search);
        }

        $whereStr  = implode(' AND ', $where);
        $pagination = $this->paginate(
            "SELECT id, username, email, full_name, balance, total_orders, total_spent, role, status, created_at, last_login_at
             FROM smmPanel_users WHERE {$whereStr} ORDER BY created_at DESC",
            $binds,
            30
        );

        $this->view('admin/users', [
            'title'      => 'User Management',
            'pagination' => $pagination,
            'filters'    => $_GET,
            'csrf'       => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function viewUser(array $params): void
    {
        $this->requireAdmin();

        $userId = (int)($params['id'] ?? 0);
        $user   = $this->db->fetchOne(
            'SELECT * FROM smmPanel_users WHERE id = ? AND deleted_at IS NULL',
            [$userId]
        );

        if (!$user) { $this->redirect('/admin/users'); }

        $recentOrders = $this->db->fetchAll(
            'SELECT o.*, s.name AS service_name FROM smmPanel_orders o
             JOIN smmPanel_services s ON s.id = o.service_id
             WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 20',
            [$userId]
        );

        $deposits = $this->db->fetchAll(
            'SELECT * FROM smmPanel_deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10',
            [$userId]
        );

        $this->view('admin/view-user', [
            'title'        => "User: {$user['username']}",
            'viewUser'     => $user,
            'recentOrders' => $recentOrders,
            'deposits'     => $deposits,
            'csrf'         => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function updateUser(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $userId = (int)($params['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        $user = $this->db->fetchOne('SELECT id, role FROM smmPanel_users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        if (!$user) { $this->json(['success' => false, 'message' => 'User not found.']); }

        $currentAdmin = $this->currentUser();

        switch ($action) {
            case 'ban':
                $this->db->query('UPDATE smmPanel_users SET status = "banned" WHERE id = ?', [$userId]);
                $this->logAdminAction('ban_user', 'user', $userId);
                $this->json(['success' => true, 'message' => 'User banned.']);

            case 'unban':
                $this->db->query('UPDATE smmPanel_users SET status = "active" WHERE id = ?', [$userId]);
                $this->logAdminAction('unban_user', 'user', $userId);
                $this->json(['success' => true, 'message' => 'User unbanned.']);

            case 'delete':
                if ($user['role'] !== 'user') { $this->json(['success' => false, 'message' => 'Cannot delete admin.']); }
                $this->db->query('UPDATE smmPanel_users SET deleted_at = NOW(), status = "deleted" WHERE id = ?', [$userId]);
                $this->logAdminAction('delete_user', 'user', $userId);
                $this->json(['success' => true, 'message' => 'User deleted.']);

            default:
                // General edit
                $allowedFields = ['full_name', 'email', 'status', 'role'];
                $data = [];
                foreach ($allowedFields as $f) {
                    if (isset($_POST[$f])) { $data[$f] = $_POST[$f]; }
                }
                if (!empty($data)) {
                    $this->db->update('smmPanel_users', $data, ['id' => $userId]);
                    $this->logAdminAction('edit_user', 'user', $userId, null, $data);
                }
                $this->json(['success' => true, 'message' => 'User updated.']);
        }
    }

    public function adjustBalance(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $userId = (int)($params['id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $type   = $_POST['type'] ?? 'add'; // add | deduct
        $reason = trim($_POST['reason'] ?? '');

        if ($amount <= 0 || empty($reason)) {
            $this->json(['success' => false, 'message' => 'Amount and reason are required.']);
        }

        $user = $this->db->fetchOne('SELECT id, balance FROM smmPanel_users WHERE id = ?', [$userId]);
        if (!$user) { $this->json(['success' => false, 'message' => 'User not found.']); }

        if ($type === 'deduct' && (float)$user['balance'] < $amount) {
            $this->json(['success' => false, 'message' => 'Insufficient balance to deduct.']);
        }

        $sign = $type === 'add' ? '+' : '-';
        $this->db->transaction(function (Database $db) use ($userId, $amount, $type, $reason, $user) {
            $db->query(
                $type === 'add'
                    ? 'UPDATE smmPanel_users SET balance = balance + ? WHERE id = ?'
                    : 'UPDATE smmPanel_users SET balance = balance - ? WHERE id = ?',
                [$amount, $userId]
            );

            $db->insert('smmPanel_balance_logs', [
                'user_id'  => $userId,
                'action'   => $type === 'add' ? 'admin_credit' : 'admin_debit',
                'amount'   => $type === 'add' ? $amount : -$amount,
                'admin_id' => $this->currentUserId(),
                'reason'   => $reason,
            ]);

            $newBalance = $type === 'add'
                ? (float)$user['balance'] + $amount
                : (float)$user['balance'] - $amount;

            $db->insert('smmPanel_transactions', [
                'user_id'        => $userId,
                'type'           => $type === 'add' ? 'bonus' : 'deduction',
                'amount'         => $type === 'add' ? $amount : -$amount,
                'balance_before' => (float)$user['balance'],
                'balance_after'  => $newBalance,
                'description'    => "Admin {$type}: {$reason}",
            ]);
        });

        $this->logAdminAction("balance_{$type}", 'user', $userId, null, ['amount' => $amount, 'reason' => $reason]);
        $this->json(['success' => true, 'message' => "Balance {$type}ed \${$amount} successfully."]);
    }

    public function bulkUsers(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $action  = $_POST['action'] ?? '';
        $userIds = array_filter(array_map('intval', (array)($_POST['user_ids'] ?? [])));

        if (empty($userIds)) { $this->json(['success' => false, 'message' => 'No users selected.']); }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        match ($action) {
            'ban'   => $this->db->query("UPDATE smmPanel_users SET status='banned' WHERE id IN ({$placeholders}) AND role='user'", $userIds),
            'unban' => $this->db->query("UPDATE smmPanel_users SET status='active' WHERE id IN ({$placeholders})", $userIds),
            default => null,
        };

        $this->json(['success' => true, 'message' => 'Bulk action applied.']);
    }

    // ── Orders ────────────────────────────────────────────────

    public function orders(array $params): void
    {
        $this->requireAdmin();

        $where = ['1=1'];
        $binds = [];

        foreach (['status', 'service_id'] as $f) {
            if (!empty($_GET[$f])) { $where[] = "o.{$f} = ?"; $binds[] = $_GET[$f]; }
        }

        if (!empty($_GET['user_id'])) {
            $where[] = 'o.user_id = ?'; $binds[] = (int)$_GET['user_id'];
        }

        if (!empty($_GET['q'])) {
            $s = '%' . $this->db->escapeLike($_GET['q']) . '%';
            $where[] = '(o.link LIKE ? OR o.id LIKE ?)';
            array_push($binds, $s, $s);
        }

        $pagination = $this->paginate(
            'SELECT o.id, o.user_id, o.link, o.quantity, o.charge, o.status,
                    o.start_count, o.remains, o.created_at, o.api_order_id,
                    u.username, s.name AS service_name
             FROM smmPanel_orders o
             JOIN smmPanel_users u ON u.id = o.user_id
             JOIN smmPanel_services s ON s.id = o.service_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY o.created_at DESC',
            $binds, 25
        );

        $this->view('admin/orders', [
            'title'      => 'Orders Management',
            'pagination' => $pagination,
            'filters'    => $_GET,
            'csrf'       => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function updateOrder(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $orderId = (int)($params['id'] ?? 0);
        $status  = $_POST['status'] ?? '';
        $validStatuses = ['pending','processing','in_progress','completed','partial','cancelled','refunded','error'];

        if (!in_array($status, $validStatuses, true)) {
            $this->json(['success' => false, 'message' => 'Invalid status.']);
        }

        $old = $this->db->fetchOne('SELECT status FROM smmPanel_orders WHERE id = ?', [$orderId]);
        if (!$old) { $this->json(['success' => false, 'message' => 'Order not found.']); }

        $this->db->query('UPDATE smmPanel_orders SET status = ? WHERE id = ?', [$status, $orderId]);
        $this->db->insert('smmPanel_order_logs', [
            'order_id'   => $orderId,
            'old_status' => $old['status'],
            'new_status' => $status,
            'message'    => 'Manual update by admin',
            'source'     => 'admin',
        ]);

        $this->logAdminAction('update_order', 'order', $orderId, ['status' => $old['status']], ['status' => $status]);
        $this->json(['success' => true, 'message' => 'Order status updated.']);
    }

    public function bulkOrders(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $action   = $_POST['action'] ?? '';
        $orderIds = array_filter(array_map('intval', (array)($_POST['order_ids'] ?? [])));

        if (empty($orderIds)) { $this->json(['success' => false, 'message' => 'No orders selected.']); }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        if ($action === 'resync') {
            $apiOrders = $this->db->fetchAll(
                "SELECT id, api_order_id FROM smmPanel_orders WHERE id IN ({$placeholders}) AND api_order_id IS NOT NULL",
                $orderIds
            );
            $apiIds = array_column($apiOrders, 'api_order_id');
            if ($apiIds) {
                try {
                    $statuses = $this->api->getBulkStatus($apiIds);
                    foreach ($statuses as $apiId => $s) {
                        $order = array_filter($apiOrders, fn($o) => $o['api_order_id'] == $apiId);
                        if ($order) {
                            $orderId = array_values($order)[0]['id'];
                            $this->db->query(
                                'UPDATE smmPanel_orders SET status=?, api_status=?, start_count=?, remains=? WHERE id=?',
                                [strtolower($s['status'] ?? 'pending'), $s['status'] ?? null, $s['start_count'] ?? null, $s['remains'] ?? null, $orderId]
                            );
                        }
                    }
                } catch (\Throwable) {}
            }
        }

        $this->json(['success' => true, 'message' => 'Bulk action completed.']);
    }

    // ── Deposits ──────────────────────────────────────────────

    public function deposits(array $params): void
    {
        $this->requireAdmin();

        $where = ['1=1'];
        $binds = [];

        if (!empty($_GET['status'])) { $where[] = 'd.status = ?'; $binds[] = $_GET['status']; }
        if (!empty($_GET['method'])) { $where[] = 'd.method = ?'; $binds[] = $_GET['method']; }

        $pagination = $this->paginate(
            'SELECT d.*, u.username, u.email
             FROM smmPanel_deposits d
             JOIN smmPanel_users u ON u.id = d.user_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY d.created_at DESC',
            $binds, 25
        );

        $this->view('admin/deposits', [
            'title'      => 'Deposit Management',
            'pagination' => $pagination,
            'filters'    => $_GET,
            'csrf'       => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function approveDeposit(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $depositId = (int)($params['id'] ?? 0);
        $amount    = (float)($_POST['amount'] ?? 0);

        if ($amount <= 0) { $this->json(['success' => false, 'message' => 'Amount required.']); }

        $deposit = $this->db->fetchOne('SELECT * FROM smmPanel_deposits WHERE id = ? AND status = "pending"', [$depositId]);
        if (!$deposit) { $this->json(['success' => false, 'message' => 'Deposit not found or already processed.']); }

        $this->db->transaction(function (Database $db) use ($deposit, $amount) {
            $db->query(
                'UPDATE smmPanel_deposits SET status="approved", approved_amount=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?',
                [$amount, $this->currentUserId(), $deposit['id']]
            );

            $user = $db->fetchOne('SELECT balance FROM smmPanel_users WHERE id=?', [$deposit['user_id']]);
            $newBalance = (float)$user['balance'] + $amount;

            $db->query('UPDATE smmPanel_users SET balance = balance + ? WHERE id=?', [$amount, $deposit['user_id']]);

            $db->insert('smmPanel_transactions', [
                'user_id'        => $deposit['user_id'],
                'type'           => 'deposit',
                'amount'         => $amount,
                'balance_before' => (float)$user['balance'],
                'balance_after'  => $newBalance,
                'reference_id'   => $deposit['id'],
                'reference_type' => 'deposit',
                'description'    => "Deposit via {$deposit['method']} approved",
            ]);
        });

        $this->notify->send(
            $deposit['user_id'], 'deposit_approved', 'Deposit Approved',
            "\${$amount} has been credited to your account.", ['deposit_id' => $depositId]
        );

        $this->logAdminAction('approve_deposit', 'deposit', $depositId, null, ['amount' => $amount]);
        $this->json(['success' => true, 'message' => "Deposit approved. \${$amount} credited."]);
    }

    public function rejectDeposit(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $depositId = (int)($params['id'] ?? 0);
        $reason    = trim($_POST['reason'] ?? '');

        $deposit = $this->db->fetchOne('SELECT * FROM smmPanel_deposits WHERE id = ? AND status="pending"', [$depositId]);
        if (!$deposit) { $this->json(['success' => false, 'message' => 'Not found.']); }

        $this->db->query(
            'UPDATE smmPanel_deposits SET status="rejected", admin_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?',
            [$reason, $this->currentUserId(), $depositId]
        );

        $this->notify->send(
            $deposit['user_id'], 'deposit_rejected', 'Deposit Rejected',
            "Your deposit was rejected. Reason: {$reason}", ['deposit_id' => $depositId]
        );

        $this->logAdminAction('reject_deposit', 'deposit', $depositId);
        $this->json(['success' => true, 'message' => 'Deposit rejected.']);
    }

    // ── Services ──────────────────────────────────────────────

    public function services(array $params): void
    {
        $this->requireAdmin();

        $search = $_GET['q'] ?? '';
        $binds  = [];
        $where  = ['1=1'];

        if ($search) {
            $s = '%' . $this->db->escapeLike($search) . '%';
            $where[] = '(s.name LIKE ? OR s.custom_name LIKE ?)';
            array_push($binds, $s, $s);
        }

        if (isset($_GET['active'])) {
            $where[] = 's.is_active = ?'; $binds[] = (int)$_GET['active'];
        }

        $pagination = $this->paginate(
            'SELECT s.*, sc.name AS category_name,
                    COALESCE(s.custom_name, s.name) AS display_name
             FROM smmPanel_services s
             JOIN smmPanel_service_categories sc ON sc.id = s.category_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY sc.sort_order, s.sort_order, s.name',
            $binds, 50
        );

        $lastSync = $this->db->fetchOne('SELECT synced_at, total_count, added, updated FROM smmPanel_service_cache_log ORDER BY id DESC LIMIT 1');

        $this->view('admin/services', [
            'title'      => 'Services Management',
            'pagination' => $pagination,
            'lastSync'   => $lastSync,
            'csrf'       => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function syncServices(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        try {
            $result = $this->api->syncServicesToDb();
            $this->logAdminAction('sync_services', null, null, null, $result);
            $this->json([
                'success' => true,
                'message' => "Sync complete: {$result['added']} added, {$result['updated']} updated.",
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    public function updateService(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $serviceId = (int)($params['id'] ?? 0);
        $data      = array_filter([
            'custom_name'  => $_POST['custom_name']  ?? null,
            'custom_desc'  => $_POST['custom_desc']  ?? null,
            'markup_type'  => $_POST['markup_type']  ?? null,
            'markup_value' => isset($_POST['markup_value']) ? (float)$_POST['markup_value'] : null,
            'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : null,
            'sort_order'   => isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : null,
        ], fn($v) => $v !== null);

        if ($data) {
            $this->db->update('smmPanel_services', $data, ['id' => $serviceId]);
            $this->logAdminAction('update_service', 'service', $serviceId, null, $data);
        }

        $this->json(['success' => true, 'message' => 'Service updated.']);
    }

    // ── Coupons ───────────────────────────────────────────────

    public function coupons(array $params): void
    {
        $this->requireAdmin();

        $coupons = $this->db->fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM smmPanel_coupon_usage WHERE coupon_id = c.id) AS usage_count
             FROM smmPanel_coupons c ORDER BY c.created_at DESC'
        );

        $this->view('admin/coupons', [
            'title'   => 'Coupons',
            'coupons' => $coupons,
            'csrf'    => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function createCoupon(array $params): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

            $this->db->insert('smmPanel_coupons', [
                'code'          => strtoupper(trim($_POST['code'] ?? '')),
                'type'          => $_POST['type'] ?? 'percent',
                'value'         => (float)($_POST['value'] ?? 0),
                'min_order'     => (float)($_POST['min_order'] ?? 0),
                'max_discount'  => !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null,
                'max_uses'      => !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null,
                'uses_per_user' => (int)($_POST['uses_per_user'] ?? 1),
                'expires_at'    => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
                'is_active'     => 1,
                'created_by'    => $this->currentUserId(),
            ]);

            $this->json(['success' => true, 'message' => 'Coupon created.', 'redirect' => '/admin/coupons']);
        }

        $this->view('admin/create-coupon', ['title' => 'Create Coupon', 'csrf' => $this->generateCsrfToken()], 'admin');
    }

    public function toggleCoupon(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $id = (int)($params['id'] ?? 0);
        $this->db->query('UPDATE smmPanel_coupons SET is_active = 1 - is_active WHERE id = ?', [$id]);
        $this->json(['success' => true]);
    }

    // ── Broadcast ─────────────────────────────────────────────

    public function broadcast(array $params): void
    {
        $this->requireAdmin();

        $broadcasts = $this->db->fetchAll(
            'SELECT b.*, u.username AS admin_name FROM smmPanel_broadcast_queue b
             JOIN smmPanel_users u ON u.id = b.admin_id
             ORDER BY b.created_at DESC LIMIT 20'
        );

        $this->view('admin/broadcast', [
            'title'      => 'Broadcast',
            'broadcasts' => $broadcasts,
            'csrf'       => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function sendBroadcast(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

        $title    = trim($_POST['title'] ?? '');
        $body     = trim($_POST['body'] ?? '');
        $channels = implode(',', (array)($_POST['channels'] ?? ['inapp']));
        $target   = $_POST['target_type'] ?? 'all';

        if (empty($title) || empty($body)) {
            $this->json(['success' => false, 'message' => 'Title and body required.']);
        }

        $broadcastId = $this->db->insert('smmPanel_broadcast_queue', [
            'admin_id'    => $this->currentUserId(),
            'title'       => $title,
            'body'        => $body,
            'target_type' => $target,
            'channels'    => $channels,
            'status'      => 'sending',
        ]);

        // Send in-app notifications
        if (str_contains($channels, 'inapp')) {
            $this->notify->sendToAll('broadcast', $title, $body);
        }

        // Email broadcast
        if (str_contains($channels, 'email')) {
            $users = $this->db->fetchAll(
                'SELECT email, full_name FROM smmPanel_users WHERE status="active" AND deleted_at IS NULL'
            );
            foreach ($users as $u) {
                $this->mailer->send($u['email'], $u['full_name'], $title, "<p>{$body}</p>");
            }
        }

        $this->db->query(
            'UPDATE smmPanel_broadcast_queue SET status="sent", sent_at=NOW(), total_sent=? WHERE id=?',
            [1, $broadcastId]
        );

        $this->logAdminAction('broadcast', null, null, null, ['title' => $title]);
        $this->json(['success' => true, 'message' => 'Broadcast sent successfully.']);
    }

    // ── Settings ──────────────────────────────────────────────

    public function settings(array $params): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

            $allowed = $this->db->fetchAll('SELECT `key` FROM smmPanel_settings');
            $keys    = array_column($allowed, 'key');

            foreach ($_POST as $k => $v) {
                if (in_array($k, $keys, true) && $k !== '_csrf' && $k !== 'action') {
                    $this->db->query(
                        'UPDATE smmPanel_settings SET `value` = ? WHERE `key` = ?',
                        [$v, $k]
                    );
                }
            }

            $this->logAdminAction('update_settings');
            $this->json(['success' => true, 'message' => 'Settings saved.']);
        }

        $settings = [];
        $rows = $this->db->fetchAll('SELECT `key`, `value`, `group`, `label`, `type` FROM smmPanel_settings ORDER BY `group`, `key`');
        foreach ($rows as $row) {
            $settings[$row['group']][$row['key']] = $row;
        }

        $this->view('admin/settings', [
            'title'    => 'Website Settings',
            'settings' => $settings,
            'csrf'     => $this->generateCsrfToken(),
        ], 'admin');
    }

    // ── Blog ──────────────────────────────────────────────────

    public function blog(array $params): void
    {
        $this->requireAdmin();

        $posts = $this->db->fetchAll(
            'SELECT p.*, u.username AS author FROM smmPanel_blog_posts p
             JOIN smmPanel_users u ON u.id = p.author_id
             ORDER BY p.created_at DESC'
        );

        $this->view('admin/blog', ['title' => 'Blog Posts', 'posts' => $posts, 'csrf' => $this->generateCsrfToken()], 'admin');
    }

    public function createPost(array $params): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }

            $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($_POST['title'] ?? '')));
            $slug = trim(preg_replace('/-+/', '-', $slug), '-');

            $this->db->insert('smmPanel_blog_posts', [
                'author_id'    => $this->currentUserId(),
                'title'        => trim($_POST['title'] ?? ''),
                'slug'         => $slug . '-' . time(),
                'excerpt'      => trim($_POST['excerpt'] ?? ''),
                'body'         => $_POST['body'] ?? '',
                'status'       => $_POST['status'] ?? 'draft',
                'seo_title'    => trim($_POST['seo_title'] ?? ''),
                'seo_desc'     => trim($_POST['seo_desc'] ?? ''),
                'published_at' => ($_POST['status'] ?? '') === 'published' ? date('Y-m-d H:i:s') : null,
            ]);

            $this->json(['success' => true, 'message' => 'Post created.', 'redirect' => '/admin/blog']);
        }

        $this->view('admin/create-post', ['title' => 'New Post', 'csrf' => $this->generateCsrfToken()], 'admin');
    }

    public function editPost(array $params): void
    {
        $this->requireAdmin();
        $post = $this->db->fetchOne('SELECT * FROM smmPanel_blog_posts WHERE id = ?', [(int)$params['id']]);
        if (!$post) { $this->redirect('/admin/blog'); }

        if ($this->isPost()) {
            if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }
            $this->db->update('smmPanel_blog_posts', [
                'title'     => trim($_POST['title'] ?? ''),
                'excerpt'   => trim($_POST['excerpt'] ?? ''),
                'body'      => $_POST['body'] ?? '',
                'status'    => $_POST['status'] ?? 'draft',
                'seo_title' => trim($_POST['seo_title'] ?? ''),
                'seo_desc'  => trim($_POST['seo_desc'] ?? ''),
            ], ['id' => $post['id']]);
            $this->json(['success' => true, 'message' => 'Post updated.']);
        }

        $this->view('admin/create-post', ['title' => 'Edit Post', 'post' => $post, 'csrf' => $this->generateCsrfToken()], 'admin');
    }

    public function deletePost(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) { $this->json(['success' => false, 'message' => 'CSRF.'], 403); }
        $this->db->query('DELETE FROM smmPanel_blog_posts WHERE id = ?', [(int)$params['id']]);
        $this->json(['success' => true, 'message' => 'Post deleted.']);
    }

    // ── Logs ──────────────────────────────────────────────────

    public function logs(array $params): void
    {
        $this->requireAdmin();

        $pagination = $this->paginate(
            'SELECT l.*, u.username AS admin_name FROM smmPanel_admin_logs l
             JOIN smmPanel_users u ON u.id = l.admin_id
             ORDER BY l.created_at DESC',
            [], 50
        );

        $this->view('admin/logs', ['title' => 'Admin Logs', 'pagination' => $pagination], 'admin');
    }

    // ── Private Helpers ───────────────────────────────────────

    private function logAdminAction(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): void {
        $adminId = $this->currentUserId();
        if (!$adminId) return;

        $this->db->insert('smmPanel_admin_logs', [
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'old_data'    => $oldData ? json_encode($oldData) : null,
            'new_data'    => $newData ? json_encode($newData) : null,
            'ip_address'  => $this->getClientIp(),
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
