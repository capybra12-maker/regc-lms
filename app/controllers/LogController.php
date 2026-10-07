<?php

class LogController
{
    public function index(): void
    {
        requireRole(['administrator']);

        $search = trim((string) ($_GET['q'] ?? ''));
        $moduleFilter = trim((string) ($_GET['module'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(l.action LIKE ? OR l.description LIKE ? OR l.ip_address LIKE ? OR u.full_name LIKE ?)';
            $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
        }

        if ($moduleFilter !== '') {
            $where[] = 'l.module = ?';
            $params[] = $moduleFilter;
        }

        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $pdo = db();
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs l LEFT JOIN users u ON u.id = l.user_id {$sql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("SELECT l.*, u.full_name AS user_name, u.email AS user_email
            FROM system_logs l
            LEFT JOIN users u ON u.id = l.user_id
            {$sql}
            ORDER BY l.id DESC
            LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Get modules list for filter
        $modules = $pdo->query("SELECT DISTINCT module FROM system_logs WHERE module IS NOT NULL AND module <> '' ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);

        $module = 'logs';
        require __DIR__ . '/../views/logs/index.php';
    }
}

