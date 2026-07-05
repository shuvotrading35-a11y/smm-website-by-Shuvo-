<?php
// src/Middleware/AuthMiddleware.php
declare(strict_types=1);
namespace SMMPanel\Middleware;

final class AuthMiddleware
{
    public function handle(callable $next): void
    {
        if (empty($_SESSION['user_id'])) {
            $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthenticated.', 'redirect' => '/login']);
                exit;
            }
            header('Location: /login');
            exit;
        }
        $next();
    }
}
