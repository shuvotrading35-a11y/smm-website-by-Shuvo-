#!/usr/bin/env php
<?php

/**
 * Cron: process-notifications.php
 * Processes scheduled broadcasts and cleans up old notifications.
 * Schedule: every 15 minutes
 *   * /15 * * * * php /var/www/shuvosmm/cron/process-notifications.php >> /var/log/smm-notify.log 2>&1
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

use SMMPanel\Core\Config;
use SMMPanel\Core\Database;
use SMMPanel\Services\EmailService;
use SMMPanel\Services\NotificationService;

Config::boot(BASE_PATH);

$db     = Database::getInstance();
$mailer = new EmailService();
$notify = new NotificationService();

echo '[' . date('Y-m-d H:i:s') . '] Processing notifications...' . PHP_EOL;

// 1. Process scheduled broadcasts that are due
$due = $db->fetchAll(
    'SELECT * FROM smmPanel_broadcast_queue
     WHERE status = "scheduled" AND scheduled_at <= NOW()
     LIMIT 10'
);

foreach ($due as $broadcast) {
    echo "Processing broadcast #{$broadcast['id']}: {$broadcast['title']}" . PHP_EOL;

    try {
        $db->query(
            'UPDATE smmPanel_broadcast_queue SET status = "sending" WHERE id = ?',
            [$broadcast['id']]
        );

        $channels = explode(',', $broadcast['channels']);
        $sent = 0;
        $failed = 0;

        $users = $db->fetchAll(
            'SELECT id, email, full_name FROM smmPanel_users
             WHERE status = "active" AND deleted_at IS NULL'
        );

        foreach ($users as $user) {
            try {
                // In-app notification
                if (in_array('inapp', $channels, true)) {
                    $notify->send(
                        $user['id'],
                        'broadcast',
                        $broadcast['title'],
                        $broadcast['body']
                    );
                }

                // Email
                if (in_array('email', $channels, true)) {
                    $mailer->send(
                        $user['email'],
                        $user['full_name'],
                        $broadcast['title'],
                        '<p>' . htmlspecialchars($broadcast['body']) . '</p>'
                    );
                }

                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $db->query(
            'UPDATE smmPanel_broadcast_queue
             SET status = "sent", sent_at = NOW(), total_sent = ?, total_failed = ?
             WHERE id = ?',
            [$sent, $failed, $broadcast['id']]
        );

        echo "  Sent: {$sent}, Failed: {$failed}" . PHP_EOL;
    } catch (\Throwable $e) {
        $db->query(
            'UPDATE smmPanel_broadcast_queue SET status = "failed" WHERE id = ?',
            [$broadcast['id']]
        );
        echo "[ERROR] Broadcast #{$broadcast['id']} failed: " . $e->getMessage() . PHP_EOL;
    }
}

// 2. Clean up read notifications older than 30 days
$deleted = $db->query(
    'DELETE FROM smmPanel_notifications
     WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
);

// 3. Clean up expired rate limit entries
$db->query('DELETE FROM smmPanel_rate_limits WHERE window_end < NOW()');

// 4. Clean up expired sessions
$db->query('DELETE FROM smmPanel_sessions WHERE expires_at < NOW()');

// 5. Clean up expired OTPs
$db->query(
    'DELETE FROM smmPanel_email_otps WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)'
);

// 6. Reset daily API request counters
$db->query(
    "UPDATE smmPanel_api_keys SET requests_today = 0
     WHERE DATE(updated_at) < CURDATE()"
);

echo '[' . date('Y-m-d H:i:s') . '] Done.' . PHP_EOL;
