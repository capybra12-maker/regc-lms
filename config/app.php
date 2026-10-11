<?php

define('APP_NAME',     env('APP_NAME',     'REGC Fleet Management & Shipment Tracking'));
define('APP_ENV',      env('APP_ENV',      'production'));
define('APP_URL',      rtrim(env('APP_URL', ''), '/'));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'Asia/Manila'));
define('UPLOAD_PATH',  __DIR__ . '/../public/uploads');
define('FUEL_BASELINE', 15); // litres per trip baseline
define('GOOGLE_MAPS_API_KEY', env('GOOGLE_MAPS_API_KEY', ''));

// Mail / SMTP settings (filled in .env)
define('MAIL_HOST',       env('MAIL_HOST',       ''));
define('MAIL_PORT',       (int) env('MAIL_PORT',       587));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_USERNAME',   env('MAIL_USERNAME',   ''));
define('MAIL_PASSWORD',   env('MAIL_PASSWORD',   ''));
define('MAIL_FROM',       env('MAIL_FROM',       ''));
define('MAIL_FROM_NAME',  env('MAIL_FROM_NAME',  APP_NAME));

// SMS settings (filled in .env)
define('SMS_PROVIDER',    env('SMS_PROVIDER',    'semaphore'));
define('SMS_API_KEY',     env('SMS_API_KEY',     ''));
define('SMS_SENDER_NAME', env('SMS_SENDER_NAME', 'REGCLMS'));

date_default_timezone_set(APP_TIMEZONE);
