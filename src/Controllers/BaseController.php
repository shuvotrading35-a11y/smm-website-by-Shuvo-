<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;
use SMMPanel\Core\Config;

/**
 * BaseController — shared helpers for all controllers.
 */
abstract class BaseController
{
    protected string $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . '/Views';
    }

    // ── View Rendering ────────────────────────────────────────

    /**
     * Render a view file with layout.
     *
     * @param string $view   Relative path under Views/, e.g. 'dashboard/index'
     * @param array  $data   Variables extracted into view scope
     * @param string $layout Layout file name without extension
     */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View [{$view}] not found at {$viewFile}.");
        }

        // Capture view content
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Render with layout
        $layoutFile = $this->viewPath . '/layouts/' . $layout . '.php';

        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    // ── JSON Response ─────────────────────────────────────────

    /**
     * Send a JSON response and exit.
     */
    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ── Redirects ─────────────────────────────────────────────

    protected function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    // ── Auth Helpers ──────────────────────────────────────────

    protected function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    protected function currentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    protected function currentUser(): ?array
    {
        $id = $this->currentUserId();

        if (!$id) {
            return null;
        }

        static $cache = [];

        if (!isset($cache[$id])) {
            $cache[$id] = Database::getInstance()->fetchOne(
                'SELECT id, username, email, full_name, balance, role, status, avatar,
                        api_key, referral_code, created_at
                 FROM smmPanel_users
                 WHERE id = ? AND deleted_at IS NULL',
                [$id]
            ) ?: null;
        }

        return $cache[$id];
    }

    protected function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Unauthenticated.', 'redirect' => '/login'], 401);
            }
            $this->redirect('/login');
        }
    }

    protected function requireAdmin(): void
    {
        $user = $this->currentUser();

        if (!$user || !in_array($user['role'], ['admin', 'superadmin'], true)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }
            $this->redirect('/admin/login');
        }
    }

    // ── CSRF Helpers ──────────────────────────────────────────

    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrfToken(): bool
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    // ── Request Helpers ───────────────────────────────────────

    protected function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    protected function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function sanitizeString(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $ip = $_SERVER[$key] ?? '';

            if ($ip) {
                // Take first IP if comma-separated
                $ip = trim(explode(',', $ip)[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ── Settings Helper ───────────────────────────────────────

    protected function getSetting(string $key, mixed $default = null): mixed
    {
        static $settingsCache = null;

        if ($settingsCache === null) {
            $rows = Database::getInstance()->fetchAll('SELECT `key`, `value`, `type` FROM smmPanel_settings');

            foreach ($rows as $row) {
                $settingsCache[$row['key']] = $this->castSetting($row['value'], $row['type']);
            }
        }

        return $settingsCache[$key] ?? $default;
    }

    private function castSetting(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool)(int)$value,
            'integer' => (int)$value,
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }

    // ── Pagination ────────────────────────────────────────────

    protected function paginate(string $sql, array $params, int $perPage = 20): array
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;
        $db      = Database::getInstance();

        $countSql = preg_replace('/SELECT .+? FROM/si', 'SELECT COUNT(*) FROM', $sql);
        $countSql = preg_replace('/ORDER BY .+$/si', '', $countSql);
        $total    = (int)$db->fetchColumn($countSql, $params);

        $pageSql = $sql . " LIMIT {$perPage} OFFSET {$offset}";
        $rows    = $db->fetchAll($pageSql, $params);

        return [
            'data'        => $rows,
            'total'       => $total,
            'per_page'    => $perPage,
            'current_page'=> $page,
            'last_page'   => max(1, (int)ceil($total / $perPage)),
            'from'        => $offset + 1,
            'to'          => min($offset + $perPage, $total),
        ];
    }
}
