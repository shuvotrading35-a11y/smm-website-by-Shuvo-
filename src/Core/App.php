<?php

declare(strict_types=1);

namespace SMMPanel\Core;

use SMMPanel\Controllers\AuthController;
use SMMPanel\Controllers\DashboardController;
use SMMPanel\Controllers\OrderController;
use SMMPanel\Controllers\FundsController;
use SMMPanel\Controllers\AdminController;
use SMMPanel\Controllers\ApiController;
use SMMPanel\Controllers\PublicController;
use SMMPanel\Controllers\SupportController;

/**
 * App — Application bootstrap and route definitions.
 */
final class App
{
    private Router $router;

    public function __construct(private string $basePath)
    {
        // 1. Load env + config
        Config::boot($basePath);

        // 2. Set PHP error handling
        $this->configureErrorHandling();

        // 3. Start session
        $this->configureSession();

        // 4. Set security headers
        $this->sendSecurityHeaders();

        // 5. Build router
        $this->router = new Router();
        $this->registerRoutes();
    }

    /** Run the application. */
    public function run(): void
    {
        $this->router->dispatch();
    }

    // ── Route Definitions ─────────────────────────────────────

    private function registerRoutes(): void
    {
        $r = $this->router;

        // ── Public routes ──────────────────────────────────────
        $r->get('/',         [PublicController::class, 'home']);
        $r->get('/services', [PublicController::class, 'services']);
        $r->get('/api-docs', [PublicController::class, 'apiDocs']);
        $r->get('/blog',     [PublicController::class, 'blog']);
        $r->get('/blog/:slug', [PublicController::class, 'blogPost']);
        $r->get('/terms',    [PublicController::class, 'terms']);
        $r->get('/privacy',  [PublicController::class, 'privacy']);
        $r->get('/ref/:code', [PublicController::class, 'referral']);

        // ── Auth routes ───────────────────────────────────────
        $r->group(['prefix' => ''], function (Router $r) {
            $r->any('/register',          [AuthController::class, 'register'], ['rate:reg']);
            $r->get('/register/verify',   [AuthController::class, 'verifyOtp']);
            $r->post('/register/verify',  [AuthController::class, 'processOtp']);
            $r->any('/login',             [AuthController::class, 'login'], ['rate:login']);
            $r->get('/logout',            [AuthController::class, 'logout']);
            $r->any('/forgot-password',   [AuthController::class, 'forgotPassword']);
            $r->any('/reset-password/:token', [AuthController::class, 'resetPassword']);
        });

        // ── User Dashboard routes ──────────────────────────────
        $r->group(['prefix' => '/dashboard', 'middleware' => ['auth']], function (Router $r) {
            $r->get('',                   [DashboardController::class, 'index']);
            $r->get('/new-order',         [OrderController::class, 'newOrder']);
            $r->post('/new-order',        [OrderController::class, 'placeOrder'], ['csrf']);
            $r->get('/orders',            [OrderController::class, 'listOrders']);
            $r->get('/orders/:id',        [OrderController::class, 'orderDetail']);
            $r->post('/orders/:id/refill', [OrderController::class, 'requestRefill'], ['csrf']);
            $r->post('/orders/:id/cancel', [OrderController::class, 'cancelOrder'], ['csrf']);
            $r->get('/order-status',      [OrderController::class, 'orderStatus']);
            $r->post('/order-status',     [OrderController::class, 'checkStatus']);
            $r->get('/add-funds',         [FundsController::class, 'addFunds']);
            $r->post('/add-funds',        [FundsController::class, 'submitDeposit'], ['csrf']);
            $r->get('/transactions',      [FundsController::class, 'transactions']);
            $r->get('/favorites',         [OrderController::class, 'favorites']);
            $r->post('/favorites/toggle', [OrderController::class, 'toggleFavorite'], ['csrf']);
            $r->get('/api',               [ApiController::class, 'userApiPage']);
            $r->post('/api/regenerate',   [ApiController::class, 'regenerateKey'], ['csrf']);
            $r->get('/coupons',           [DashboardController::class, 'coupons']);
            $r->post('/coupons/apply',    [DashboardController::class, 'applyCoupon'], ['csrf']);
            $r->get('/referrals',         [DashboardController::class, 'referrals']);
            $r->any('/support',           [SupportController::class, 'index']);
            $r->get('/support/:id',       [SupportController::class, 'viewTicket']);
            $r->post('/support/:id/reply', [SupportController::class, 'replyTicket'], ['csrf']);
            $r->any('/settings',          [DashboardController::class, 'settings'], ['csrf']);
        });

        // ── REST-ish AJAX endpoints (user) ─────────────────────
        $r->group(['prefix' => '/api', 'middleware' => ['auth']], function (Router $r) {
            $r->get('/services',          [ApiController::class, 'getServices']);
            $r->get('/notifications',     [DashboardController::class, 'notifications']);
            $r->post('/notifications/read', [DashboardController::class, 'markRead'], ['csrf']);
        });

        // ── Public API (API-key auth) ──────────────────────────
        $r->group(['prefix' => '/api/v2', 'middleware' => ['rate:api']], function (Router $r) {
            $r->post('', [ApiController::class, 'handleV2']);
        });

        // ── Admin routes ───────────────────────────────────────
        $r->any('/admin/login', [AdminController::class, 'login'], ['rate:login']);

        $r->group(['prefix' => '/admin', 'middleware' => ['admin']], function (Router $r) {
            $r->get('',                       [AdminController::class, 'dashboard']);
            $r->get('/users',                 [AdminController::class, 'users']);
            $r->get('/users/:id',             [AdminController::class, 'viewUser']);
            $r->post('/users/:id',            [AdminController::class, 'updateUser'], ['csrf']);
            $r->post('/users/:id/balance',    [AdminController::class, 'adjustBalance'], ['csrf']);
            $r->post('/users/bulk',           [AdminController::class, 'bulkUsers'], ['csrf']);
            $r->get('/orders',                [AdminController::class, 'orders']);
            $r->get('/orders/:id',            [AdminController::class, 'viewOrder']);
            $r->post('/orders/:id',           [AdminController::class, 'updateOrder'], ['csrf']);
            $r->post('/orders/bulk',          [AdminController::class, 'bulkOrders'], ['csrf']);
            $r->get('/deposits',              [AdminController::class, 'deposits']);
            $r->post('/deposits/:id/approve', [AdminController::class, 'approveDeposit'], ['csrf']);
            $r->post('/deposits/:id/reject',  [AdminController::class, 'rejectDeposit'], ['csrf']);
            $r->get('/services',              [AdminController::class, 'services']);
            $r->post('/services/sync',        [AdminController::class, 'syncServices'], ['csrf']);
            $r->post('/services/:id',         [AdminController::class, 'updateService'], ['csrf']);
            $r->get('/coupons',               [AdminController::class, 'coupons']);
            $r->any('/coupons/create',        [AdminController::class, 'createCoupon'], ['csrf']);
            $r->post('/coupons/:id/toggle',   [AdminController::class, 'toggleCoupon'], ['csrf']);
            $r->get('/broadcast',             [AdminController::class, 'broadcast']);
            $r->post('/broadcast',            [AdminController::class, 'sendBroadcast'], ['csrf']);
            $r->get('/support',               [SupportController::class, 'adminIndex']);
            $r->get('/support/:id',           [SupportController::class, 'adminView']);
            $r->post('/support/:id',          [SupportController::class, 'adminReply'], ['csrf']);
            $r->get('/blog',                  [AdminController::class, 'blog']);
            $r->any('/blog/create',           [AdminController::class, 'createPost'], ['csrf']);
            $r->any('/blog/:id/edit',         [AdminController::class, 'editPost'], ['csrf']);
            $r->post('/blog/:id/delete',      [AdminController::class, 'deletePost'], ['csrf']);
            $r->any('/settings',              [AdminController::class, 'settings'], ['csrf']);
            $r->get('/logs',                  [AdminController::class, 'logs']);
            $r->get('/logout',                [AdminController::class, 'logout']);
        });
    }

    // ── PHP Configuration ─────────────────────────────────────

    private function configureErrorHandling(): void
    {
        if (Config::isDebug()) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        }

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            error_log("[PHP] {$errstr} in {$errfile}:{$errline}");
            return true;
        });

        set_exception_handler(function (\Throwable $e): void {
            error_log('[Exception] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            if (Config::isDebug()) {
                echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
            } else {
                http_response_code(500);
                include __DIR__ . '/../Views/errors/500.php';
            }
            exit(1);
        });
    }

    private function configureSession(): void
    {
        $secure   = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $lifetime = (int) Config::get('SESSION_LIFETIME', 86400);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_name('SMMPSID');
            session_start();
        }
    }

    private function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if (!empty($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        $cspParts = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com cdnjs.cloudflare.com",
            "font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
        ];

        header('Content-Security-Policy: ' . implode('; ', $cspParts));
    }
}
