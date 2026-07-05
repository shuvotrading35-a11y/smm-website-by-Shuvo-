<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;
use SMMPanel\Services\NotificationService;

/**
 * SupportController — support tickets for users and admins.
 */
final class SupportController extends BaseController
{
    private Database $db;
    private NotificationService $notify;

    public function __construct()
    {
        parent::__construct();
        $this->db     = Database::getInstance();
        $this->notify = new NotificationService();
    }

    // ── User: Ticket List / Create ────────────────────────────

    public function index(array $params): void
    {
        $this->requireAuth();
        $userId = $this->currentUserId();

        if ($this->isPost()) {
            if (!$this->verifyCsrfToken()) {
                $this->json(['success' => false, 'message' => 'CSRF mismatch.'], 403);
            }

            $subject  = trim($_POST['subject'] ?? '');
            $category = $_POST['category'] ?? 'general';
            $priority = $_POST['priority'] ?? 'medium';
            $message  = trim($_POST['message'] ?? '');
            $orderId  = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;

            if (empty($subject) || empty($message)) {
                $this->json(['success' => false, 'message' => 'Subject and message are required.']);
            }

            $ticketId = $this->db->transaction(function (Database $db) use (
                $userId, $subject, $category, $priority, $message, $orderId
            ) {
                $ticketId = (int) $db->insert('smmPanel_support_tickets', [
                    'user_id'    => $userId,
                    'subject'    => $subject,
                    'category'   => $category,
                    'priority'   => $priority,
                    'status'     => 'open',
                    'order_id'   => $orderId,
                    'last_reply_at' => date('Y-m-d H:i:s'),
                ]);

                $db->insert('smmPanel_ticket_replies', [
                    'ticket_id' => $ticketId,
                    'user_id'   => $userId,
                    'body'      => $message,
                    'is_admin'  => 0,
                ]);

                return $ticketId;
            });

            $this->json([
                'success'   => true,
                'ticket_id' => $ticketId,
                'message'   => 'Ticket created successfully.',
                'redirect'  => "/dashboard/support/{$ticketId}",
            ]);
            return;
        }

        $tickets = $this->db->fetchAll(
            'SELECT id, subject, category, priority, status, last_reply_at, created_at
             FROM smmPanel_support_tickets
             WHERE user_id = ?
             ORDER BY last_reply_at DESC',
            [$userId]
        );

        $this->view('dashboard/support', [
            'title'   => 'Support',
            'user'    => $this->currentUser(),
            'tickets' => $tickets,
            'csrf'    => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    public function viewTicket(array $params): void
    {
        $this->requireAuth();
        $ticketId = (int)($params['id'] ?? 0);
        $userId   = $this->currentUserId();

        $ticket = $this->db->fetchOne(
            'SELECT * FROM smmPanel_support_tickets WHERE id = ? AND user_id = ?',
            [$ticketId, $userId]
        );

        if (!$ticket) { $this->redirect('/dashboard/support'); }

        $replies = $this->db->fetchAll(
            'SELECT r.*, u.full_name, u.avatar, u.role
             FROM smmPanel_ticket_replies r
             JOIN smmPanel_users u ON u.id = r.user_id
             WHERE r.ticket_id = ?
             ORDER BY r.created_at ASC',
            [$ticketId]
        );

        $this->view('dashboard/support-ticket', [
            'title'   => 'Ticket #' . $ticketId,
            'user'    => $this->currentUser(),
            'ticket'  => $ticket,
            'replies' => $replies,
            'csrf'    => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    public function replyTicket(array $params): void
    {
        $this->requireAuth();
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'CSRF mismatch.'], 403);
        }

        $ticketId = (int)($params['id'] ?? 0);
        $userId   = $this->currentUserId();
        $body     = trim($_POST['body'] ?? '');

        if (empty($body)) {
            $this->json(['success' => false, 'message' => 'Message cannot be empty.']);
        }

        $ticket = $this->db->fetchOne(
            'SELECT id, status FROM smmPanel_support_tickets WHERE id = ? AND user_id = ?',
            [$ticketId, $userId]
        );

        if (!$ticket || $ticket['status'] === 'closed') {
            $this->json(['success' => false, 'message' => 'Ticket not found or closed.']);
        }

        $this->db->insert('smmPanel_ticket_replies', [
            'ticket_id' => $ticketId,
            'user_id'   => $userId,
            'body'      => $body,
            'is_admin'  => 0,
        ]);

        $this->db->query(
            'UPDATE smmPanel_support_tickets SET status = "open", last_reply_at = NOW() WHERE id = ?',
            [$ticketId]
        );

        $this->json(['success' => true, 'message' => 'Reply sent.']);
    }

    // ── Admin: Ticket Management ──────────────────────────────

    public function adminIndex(array $params): void
    {
        $this->requireAdmin();

        $status = $_GET['status'] ?? '';
        $where  = ['1=1'];
        $binds  = [];

        if ($status) { $where[] = 't.status = ?'; $binds[] = $status; }

        $pagination = $this->paginate(
            'SELECT t.*, u.username, u.email,
                    (SELECT COUNT(*) FROM smmPanel_ticket_replies WHERE ticket_id = t.id) AS reply_count
             FROM smmPanel_support_tickets t
             JOIN smmPanel_users u ON u.id = t.user_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY
               FIELD(t.priority,"urgent","high","medium","low"),
               t.last_reply_at DESC',
            $binds, 30
        );

        $this->view('admin/support', [
            'title'      => 'Support Tickets',
            'pagination' => $pagination,
            'filters'    => ['status' => $status],
            'csrf'       => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function adminView(array $params): void
    {
        $this->requireAdmin();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->db->fetchOne(
            'SELECT t.*, u.username, u.email, u.full_name AS user_name
             FROM smmPanel_support_tickets t
             JOIN smmPanel_users u ON u.id = t.user_id
             WHERE t.id = ?',
            [$ticketId]
        );

        if (!$ticket) { $this->redirect('/admin/support'); }

        $replies = $this->db->fetchAll(
            'SELECT r.*, u.full_name, u.avatar, u.role
             FROM smmPanel_ticket_replies r
             JOIN smmPanel_users u ON u.id = r.user_id
             WHERE r.ticket_id = ?
             ORDER BY r.created_at ASC',
            [$ticketId]
        );

        $admins = $this->db->fetchAll(
            'SELECT id, username, full_name FROM smmPanel_users WHERE role IN ("admin","superadmin") AND status="active"'
        );

        $this->view('admin/support-ticket', [
            'title'   => "Ticket #{$ticketId}",
            'ticket'  => $ticket,
            'replies' => $replies,
            'admins'  => $admins,
            'csrf'    => $this->generateCsrfToken(),
        ], 'admin');
    }

    public function adminReply(array $params): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'CSRF mismatch.'], 403);
        }

        $ticketId  = (int)($params['id'] ?? 0);
        $adminId   = $this->currentUserId();
        $body      = trim($_POST['body'] ?? '');
        $newStatus = $_POST['status'] ?? null;
        $assignTo  = !empty($_POST['assign_to']) ? (int)$_POST['assign_to'] : null;

        if (empty($body)) {
            $this->json(['success' => false, 'message' => 'Reply body required.']);
        }

        $this->db->insert('smmPanel_ticket_replies', [
            'ticket_id' => $ticketId,
            'user_id'   => $adminId,
            'body'      => $body,
            'is_admin'  => 1,
        ]);

        $updateData = ['last_reply_at' => date('Y-m-d H:i:s')];
        if ($newStatus) $updateData['status'] = $newStatus;
        if ($assignTo)  $updateData['assigned_to'] = $assignTo;

        $this->db->update('smmPanel_support_tickets', $updateData, ['id' => $ticketId]);

        // Notify user
        $ticket = $this->db->fetchOne('SELECT user_id, subject FROM smmPanel_support_tickets WHERE id=?', [$ticketId]);
        if ($ticket) {
            $this->notify->send(
                $ticket['user_id'],
                'ticket_reply',
                'Support Reply',
                "Admin replied to your ticket: {$ticket['subject']}",
                [],
                "/dashboard/support/{$ticketId}"
            );
        }

        $this->json(['success' => true, 'message' => 'Reply sent.']);
    }
}
