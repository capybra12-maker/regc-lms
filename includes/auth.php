<?php

function authUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        header('Location: index.php?route=login');
        exit;
    }

    header('Cache-Control: no-store, private');
}

function requireRole(array $roles): void
{
    requireAuth();

    $role = $_SESSION['user']['role'] ?? null;

    if (!in_array($role, $roles, true)) {
        http_response_code(403);

        exit(
            '<h1>403</h1>
             <p>You are not authorized to access this page.</p>'
        );
    }
}

function hasRole(string|array $roles): bool
{
    $currentRole = $_SESSION['user']['role'] ?? null;
    if (!$currentRole) {
        return false;
    }
    $allowed = is_array($roles) ? $roles : [$roles];
    return in_array($currentRole, $allowed, true);
}

function dashboardRouteForRole(?string $role): string
{
    return match ($role) {
        'driver' => 'driver',
        'warehouse' => 'warehouse',
        default => 'dashboard',
    };
}
