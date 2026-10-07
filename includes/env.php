<?php

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        if (
            str_starts_with($value, '"') &&
            str_ends_with($value, '"')
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

loadEnv(dirname(__DIR__) . '/.env');
