<?php

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name  = trim($name);
        $value = trim($value);

        if (
            strlen($value) >= 2 &&
            $value[0] === '"' &&
            $value[strlen($value) - 1] === '"'
        ) {
            $value = substr($value, 1, -1);
        }

        // Always set $_ENV (reliable on all hosts including InfinityFree)
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
        @putenv("$name=$value"); // may be disabled on some shared hosts
    }
}

/**
 * Get an environment variable value.
 * Checks $_ENV first (always set), then getenv() as fallback.
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function env(string $key, $default = null)
{
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    return $default;
}

loadEnv(dirname(__DIR__) . '/.env');
