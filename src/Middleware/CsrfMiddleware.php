<?php
declare(strict_types=1);
namespace SMMPanel\Middleware;

final class CsrfMiddleware
{
    public function handle(callable $next): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
            $token   = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $session = $_SESSION['csrf_token'] ?? '';

            if (!$session || !hash_equals($session, $token)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Security token mismatch. Refresh and try again.']);
                exit;
            }
        }
        $next();
    }
}
