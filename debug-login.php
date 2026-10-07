<?php

// Local command-line diagnostic. It is intentionally unavailable through Apache.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

try {
    $userCount = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $activeCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

    echo "REGC LMS authentication diagnostic\n";
    echo "Database connection: OK\n";
    echo "Users: {$userCount}\n";
    echo "Active users: {$activeCount}\n";
    echo "Session save path: " . session_save_path() . "\n";
    echo "Password hashes and account details are not displayed.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Diagnostic failed: {$error->getMessage()}\n");
    exit(1);
}
