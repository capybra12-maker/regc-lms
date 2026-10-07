<?php

class Settings
{
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();

        if ($val === false) {
            return $default;
        }

        self::$cache[$key] = $val;
        return $val;
    }

    public static function all(): array
    {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public static function set(string $key, ?string $value): void
    {
        $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
        $stmt->execute([$key, $value]);
        self::$cache[$key] = $value;
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            self::set($key, is_scalar($value) ? (string) $value : null);
        }
        logActivity('Update Settings', 'Settings', 'Updated system preferences and company configuration.');
    }
}

