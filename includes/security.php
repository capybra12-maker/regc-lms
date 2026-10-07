<?php

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatDate(?string $value, bool $withTime = true): string
{
    if (!$value) {
        return 'Not scheduled';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format($withTime ? 'M j, Y · g:i A' : 'M j, Y');
    } catch (Exception) {
        return $value;
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return sprintf(
        '<input type="hidden" name="csrf_token" value="%s">',
        e(csrfToken())
    );
}

function verifyCsrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        http_response_code(403);
        exit('Invalid or expired request token. <a href="index.php">Reload the page and try again.</a>');
    }
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function formatCurrency(?float $amount): string
{
    if ($amount === null) {
        return '₱0.00';
    }
    return '₱' . number_format($amount, 2);
}

function createNotification(
    ?int $userId,
    ?string $role,
    string $title,
    string $message,
    string $type = 'info',
    ?string $link = null
): void {
    try {
        $stmt = db()->prepare(
            'INSERT INTO notifications (user_id, role, title, message, type, link)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $role, $title, $message, $type, $link]);
    } catch (Throwable $e) {
        error_log('Failed to create notification: ' . $e->getMessage());
    }
}

function logActivity(
    string $action,
    string $module,
    string $description = ''
): void {
    $userId = $_SESSION['user']['id'] ?? null;

    $stmt = db()->prepare(
        'INSERT INTO system_logs
        (user_id, action, module, description, ip_address)
        VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $userId,
        $action,
        $module,
        $description,
        clientIp()
    ]);
}
