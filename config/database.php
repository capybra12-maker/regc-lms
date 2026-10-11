<?php

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host     = env('DB_HOST',     '127.0.0.1');
    $port     = env('DB_PORT',     '3306');
    $database = env('DB_DATABASE', 'regc_lms');
    $username = env('DB_USERNAME', 'root');
    $password = env('DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    try {
        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        return $pdo;

    } catch (PDOException $e) {

        if (defined('APP_ENV') && APP_ENV === 'development') {
            exit('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }

        http_response_code(500);
        exit(
            '<h1>Database Connection Error</h1>
             <p>Please contact the system administrator.</p>'
        );
    }
}
