<?php

define('APP_NAME',     getenv('APP_NAME')     ?: 'REGC Fleet Management & Shipment Tracking');
define('APP_ENV',      getenv('APP_ENV')      ?: 'production');
define('APP_URL',      rtrim(getenv('APP_URL') ?: '', '/'));
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Asia/Manila');
define('UPLOAD_PATH',  __DIR__ . '/../public/uploads');
define('FUEL_BASELINE', 15); // litres per trip baseline
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');

// Mail / SMTP settings (filled in .env)
define('MAIL_HOST',       getenv('MAIL_HOST')       ?: '');
define('MAIL_PORT',       (int)(getenv('MAIL_PORT') ?: 587));
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_USERNAME',   getenv('MAIL_USERNAME')   ?: '');
define('MAIL_PASSWORD',   getenv('MAIL_PASSWORD')   ?: '');
define('MAIL_FROM',       getenv('MAIL_FROM')       ?: '');
define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME')  ?: APP_NAME);

// SMS settings (filled in .env)
define('SMS_PROVIDER',    getenv('SMS_PROVIDER')    ?: 'semaphore');
define('SMS_API_KEY',     getenv('SMS_API_KEY')     ?: '');
define('SMS_SENDER_NAME', getenv('SMS_SENDER_NAME') ?: 'REGCLMS');

date_default_timezone_set(APP_TIMEZONE);
