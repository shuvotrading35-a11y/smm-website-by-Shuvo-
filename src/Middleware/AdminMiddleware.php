<?php
declare(strict_types=1);
namespace SMMPanel\Middleware;

use SMMPanel\Core\Database;

final class AdminMiddleware
{
    public function handle(callable $next): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) { header('Location: /admin/login'); exit; }

        $user = Database::getInstance()->fetchOne(
            'SELECT role, status FROM smmPanel_users WHERE id = ? AND deleted_at IS NULL',
            [(int)$userId]
        );

        if (!$user || !in_array($user['role'], ['admin', 'superadmin'], true) || $user['status'] !== 'active') {
            header('Location: /admin/login'); exit;
        }

        // Optional IP whitelist
        $whitelist = trim((string)($_ENV['ADMIN_IP_WHITELIST'] ?? ''));
        if ($whitelist) {
            $allowed = array_map('trim', explode(',', $whitelist));
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!in_array($clientIp, $allowed, true)) {
                http_response_code(403);
                echo 'Access denied.'; exit;
            }
        }

        $next();
    }
}
