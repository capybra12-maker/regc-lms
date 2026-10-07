<?php

class Notification
{
    public static function forUser(int $userId, string $role, int $limit = 20): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM notifications
             WHERE (user_id = ? OR (user_id IS NULL AND (role = ? OR role IS NULL)))
             ORDER BY created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $role, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId, string $role): int
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM notifications
             WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND (role = ? OR role IS NULL)))'
        );
        $stmt->execute([$userId, $role]);
        return (int) $stmt->fetchColumn();
    }

    public static function markAsRead(int $id, int $userId): void
    {
        $stmt = db()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public static function markAllAsRead(int $userId, string $role): void
    {
        $stmt = db()->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE user_id = ? OR (user_id IS NULL AND (role = ? OR role IS NULL))'
        );
        $stmt->execute([$userId, $role]);
    }

    public static function create(?int $userId, ?string $role, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        createNotification($userId, $role, $title, $message, $type, $link);
    }
}

