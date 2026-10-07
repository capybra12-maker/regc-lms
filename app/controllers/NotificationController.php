<?php

require_once __DIR__ . '/../models/Notification.php';

class NotificationController
{
    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public function index(): void
    {
        requireAuth();
        $user = authUser();

        $notifications = Notification::forUser((int) $user['id'], $user['role'], 50);
        $unreadCount = Notification::unreadCount((int) $user['id'], $user['role']);
        $module = 'notifications';

        require __DIR__ . '/../views/notifications/index.php';
    }

    public function markRead(): void
    {
        requireAuth();
        verifyCsrf();
        $user = authUser();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            Notification::markAsRead($id, (int) $user['id']);
        }

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'REGC-Modal' || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
            $this->json(['success' => true]);
        }

        header('Location: index.php?route=notifications');
        exit;
    }

    public function markAllRead(): void
    {
        requireAuth();
        verifyCsrf();
        $user = authUser();

        Notification::markAllAsRead((int) $user['id'], $user['role']);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'REGC-Modal' || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
            $this->json(['success' => true]);
        }

        $_SESSION['notice'] = 'All notifications marked as read.';
        header('Location: index.php?route=notifications');
        exit;
    }

    public function poll(): void
    {
        requireAuth();
        $user = authUser();

        $unreadCount = Notification::unreadCount((int) $user['id'], $user['role']);
        $recent = Notification::forUser((int) $user['id'], $user['role'], 5);

        $this->json([
            'unread_count' => $unreadCount,
            'recent' => $recent,
        ]);
    }
}

