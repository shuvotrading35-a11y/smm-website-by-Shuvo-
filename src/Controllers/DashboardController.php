<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;

/**
 * DashboardController — user dashboard home and misc pages.
 */
final class DashboardController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->requireAuth();
    }

    // ── Dashboard Home ────────────────────────────────────────

    public function index(array $params): void
    {
        $userId = $this->currentUserId();
        $user   = $this->currentUser();

        // Stats cards
        $stats = $this->db->fetchOne(
            'SELECT
               COUNT(*) AS total_orders,
               SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_orders,
               SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending,
               SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) AS in_progress,
               SUM(CASE WHEN DATE(created_at) = CURDATE() THEN charge ELSE 0 END) AS today_spent
             FROM smmPanel_orders
             WHERE user_id = ?',
            [$userId]
        );

        // Recent orders
        $recentOrders = $this->db->fetchAll(
            'SELECT o.*, s.name as service_name, sc.name as category_name, sc.icon as category_icon
             FROM smmPanel_orders o
             JOIN smmPanel_services s ON s.id = o.service_id
             JOIN smmPanel_service_categories sc ON sc.id = s.category_id
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC
             LIMIT 5',
            [$userId]
        );

        // Spending chart (last 30 days)
        $chartData = $this->db->fetchAll(
            'SELECT DATE(created_at) AS day, SUM(charge) AS total
             FROM smmPanel_orders
             WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            [$userId]
        );

        // Notifications (unread count)
        $unreadCount = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );

        $this->view('dashboard/index', [
            'title'       => 'Dashboard',
            'user'        => $user,
            'stats'       => $stats,
            'recentOrders'=> $recentOrders,
            'chartData'   => $chartData,
            'unreadCount' => $unreadCount,
            'layout'      => 'dashboard',
        ], 'dashboard');
    }

    // ── Notifications ─────────────────────────────────────────

    public function notifications(array $params): void
    {
        $userId = $this->currentUserId();

        $notifications = $this->db->fetchAll(
            'SELECT * FROM smmPanel_notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 30',
            [$userId]
        );

        $this->json([
            'success' => true,
            'data'    => $notifications,
            'unread'  => array_sum(array_column($notifications, 'is_read') === [0]),
        ]);
    }

    public function markRead(array $params): void
    {
        $userId = $this->currentUserId();
        $id     = (int)($_POST['id'] ?? 0);

        if ($id) {
            $this->db->query(
                'UPDATE smmPanel_notifications SET is_read = 1, read_at = NOW()
                 WHERE id = ? AND user_id = ?',
                [$id, $userId]
            );
        } else {
            // Mark all
            $this->db->query(
                'UPDATE smmPanel_notifications SET is_read = 1, read_at = NOW()
                 WHERE user_id = ? AND is_read = 0',
                [$userId]
            );
        }

        $this->json(['success' => true]);
    }

    // ── Coupons ───────────────────────────────────────────────

    public function coupons(array $params): void
    {
        $userId = $this->currentUserId();

        $usedCoupons = $this->db->fetchAll(
            'SELECT cu.*, c.code, c.type, c.value
             FROM smmPanel_coupon_usage cu
             JOIN smmPanel_coupons c ON c.id = cu.coupon_id
             WHERE cu.user_id = ?
             ORDER BY cu.used_at DESC',
            [$userId]
        );

        $this->view('dashboard/coupons', [
            'title'      => 'Coupons',
            'usedCoupons'=> $usedCoupons,
            'csrf'       => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    public function applyCoupon(array $params): void
    {
        $code   = strtoupper(trim($_POST['code'] ?? ''));
        $amount = (float)($_POST['amount'] ?? 0);

        if (empty($code) || $amount <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid request.']);
        }

        $coupon = $this->db->fetchOne(
            'SELECT * FROM smmPanel_coupons
             WHERE code = ? AND is_active = 1
               AND (expires_at IS NULL OR expires_at > NOW())
               AND (max_uses IS NULL OR total_used < max_uses)',
            [$code]
        );

        if (!$coupon) {
            $this->json(['success' => false, 'message' => 'Invalid or expired coupon code.']);
        }

        if ((float)$coupon['min_order'] > $amount) {
            $this->json(['success' => false, 'message' => 'Minimum order amount: $' . number_format((float)$coupon['min_order'], 2)]);
        }

        // Check per-user usage
        $userId    = $this->currentUserId();
        $userUsage = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_coupon_usage WHERE coupon_id = ? AND user_id = ?',
            [$coupon['id'], $userId]
        );

        if ($userUsage >= (int)$coupon['uses_per_user']) {
            $this->json(['success' => false, 'message' => 'You have already used this coupon.']);
        }

        // Calculate discount
        $discount = match ($coupon['type']) {
            'percent' => min(
                $amount * ((float)$coupon['value'] / 100),
                $coupon['max_discount'] ? (float)$coupon['max_discount'] : PHP_FLOAT_MAX
            ),
            'fixed', 'bonus_balance' => min((float)$coupon['value'], $amount),
            default => 0,
        };

        $this->json([
            'success'   => true,
            'discount'  => round($discount, 4),
            'final'     => round($amount - $discount, 4),
            'coupon_id' => $coupon['id'],
            'message'   => 'Coupon applied! Saving $' . number_format($discount, 2),
        ]);
    }

    // ── Referrals ─────────────────────────────────────────────

    public function referrals(array $params): void
    {
        $userId = $this->currentUserId();
        $user   = $this->currentUser();

        $referralLink = $this->getSetting('site_url') . '/ref/' . $user['referral_code'];

        $referrals = $this->db->fetchAll(
            'SELECT u.username, u.full_name, u.created_at, r.status,
                    COALESCE(SUM(re.earned), 0) AS total_earned,
                    COUNT(re.id) AS order_count
             FROM smmPanel_referrals r
             JOIN smmPanel_users u ON u.id = r.referred_id
             LEFT JOIN smmPanel_referral_earnings re ON re.referred_id = r.referred_id
             WHERE r.referrer_id = ?
             GROUP BY r.id
             ORDER BY r.created_at DESC',
            [$userId]
        );

        $totalEarned = $this->db->fetchColumn(
            'SELECT COALESCE(SUM(earned), 0) FROM smmPanel_referral_earnings WHERE referrer_id = ?',
            [$userId]
        );

        $this->view('dashboard/referrals', [
            'title'        => 'Referral Program',
            'referralLink' => $referralLink,
            'referrals'    => $referrals,
            'totalEarned'  => $totalEarned,
            'percent'      => $this->getSetting('referral_percent', 2),
        ], 'dashboard');
    }

    // ── Settings ──────────────────────────────────────────────

    public function settings(array $params): void
    {
        $user = $this->currentUser();

        if ($this->isPost()) {
            if (!$this->verifyCsrfToken()) {
                $this->json(['success' => false, 'message' => 'Security token mismatch.']);
            }

            $action = $_POST['action'] ?? 'profile';

            match ($action) {
                'profile'  => $this->updateProfile($user),
                'password' => $this->updatePassword($user),
                'avatar'   => $this->updateAvatar($user),
                default    => $this->json(['success' => false, 'message' => 'Unknown action.']),
            };
            return;
        }

        $this->view('dashboard/settings', [
            'title' => 'Account Settings',
            'user'  => $user,
            'csrf'  => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    private function updateProfile(array $user): never
    {
        $fullName = trim($_POST['full_name'] ?? '');

        if (strlen($fullName) < 2) {
            $this->json(['success' => false, 'message' => 'Full name must be at least 2 characters.']);
        }

        $this->db->query(
            'UPDATE smmPanel_users SET full_name = ? WHERE id = ?',
            [$fullName, $user['id']]
        );

        $this->json(['success' => true, 'message' => 'Profile updated successfully.']);
    }

    private function updatePassword(array $user): never
    {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $fullUser = $this->db->fetchOne(
            'SELECT password FROM smmPanel_users WHERE id = ?',
            [$user['id']]
        );

        if (!password_verify($current, $fullUser['password'])) {
            $this->json(['success' => false, 'message' => 'Current password is incorrect.']);
        }

        if (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $this->json(['success' => false, 'message' => 'Password must be 8+ chars with uppercase and number.']);
        }

        if ($new !== $confirm) {
            $this->json(['success' => false, 'message' => 'Passwords do not match.']);
        }

        $this->db->query(
            'UPDATE smmPanel_users SET password = ? WHERE id = ?',
            [password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]), $user['id']]
        );

        $this->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    private function updateAvatar(array $user): never
    {
        if (empty($_FILES['avatar'])) {
            $this->json(['success' => false, 'message' => 'No file uploaded.']);
        }

        $file    = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Only JPEG, PNG or WebP allowed.']);
        }

        if ($file['size'] > $maxSize) {
            $this->json(['success' => false, 'message' => 'File must be under 2 MB.']);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
        $dest     = dirname(__DIR__, 2) . '/public/assets/uploads/avatars/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->json(['success' => false, 'message' => 'Upload failed. Please try again.']);
        }

        $this->db->query(
            'UPDATE smmPanel_users SET avatar = ? WHERE id = ?',
            ['/assets/uploads/avatars/' . $filename, $user['id']]
        );

        $this->json(['success' => true, 'avatar' => '/assets/uploads/avatars/' . $filename]);
    }
}
