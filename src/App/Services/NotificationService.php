<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Manages in-app notifications, including creation,
 * retrieval, unread counts, and read status.
 */
class NotificationService
{
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Creates a notification without allowing notification failures
     * to interrupt the main application action that triggered it.
     */
    public function notify(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?string $link = null
    ): void {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO notifications (user_id, type, title, body, link)
                 VALUES (:uid, :type, :title, :body, :link)"
            );
            $stmt->execute([
                ':uid' => $userId,
                ':type' => $type,
                ':title' => $title,
                ':body' => $body,
                ':link' => $link,
            ]);
        } catch (\Throwable $e) {
            error_log('NotificationService error: ' . $e->getMessage());
        }
    }

    public function getForUser(int $userId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT notification_id, type, title, body, link, is_read, created_at
             FROM notifications
             WHERE user_id = :uid
             ORDER BY created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0"
        );
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0"
        );
        $stmt->execute([':uid' => $userId]);
    }
}
