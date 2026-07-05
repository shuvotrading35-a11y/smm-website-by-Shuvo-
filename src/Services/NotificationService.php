<?php
declare(strict_types=1);
namespace SMMPanel\Services;

use SMMPanel\Core\Database;

final class NotificationService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function send(int $userId, string $type, string $title, string $body, array $data = [], ?string $url = null): void
    {
        $this->db->insert('smmPanel_notifications', [
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data ? json_encode($data) : null,
            'url'     => $url,
            'is_read' => 0,
        ]);
    }

    public function sendToAll(string $type, string $title, string $body): void
    {
        $this->db->query(
            "INSERT INTO smmPanel_notifications (user_id, type, title, body)
             SELECT id, ?, ?, ? FROM smmPanel_users WHERE status = 'active' AND deleted_at IS NULL",
            [$type, $title, $body]
        );
    }
}
