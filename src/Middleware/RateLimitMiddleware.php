<?php
declare(strict_types=1);
namespace SMMPanel\Middleware;

use SMMPanel\Core\Database;

final class RateLimitMiddleware
{
    public function __construct(
        private string $action,
        private int $maxHits,
        private int $windowSeconds
    ) {}

    public function handle(callable $next): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = $ip . ':' . $this->action;
        $db  = Database::getInstance();

        // Clean expired
        $db->query('DELETE FROM smmPanel_rate_limits WHERE window_end < NOW()');

        $row = $db->fetchOne(
            'SELECT id, hits, window_end FROM smmPanel_rate_limits WHERE `key` = ?',
            [$key]
        );

        if (!$row) {
            $db->insert('smmPanel_rate_limits', [
                'key'        => $key,
                'hits'       => 1,
                'window_end' => date('Y-m-d H:i:s', time() + $this->windowSeconds),
            ]);
        } elseif ((int)$row['hits'] >= $this->maxHits) {
            $retryAfter = max(0, strtotime($row['window_end']) - time());
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait ' . $retryAfter . ' seconds.']);
            exit;
        } else {
            $db->query(
                'UPDATE smmPanel_rate_limits SET hits = hits + 1 WHERE `key` = ?',
                [$key]
            );
        }

        $next();
    }
}
