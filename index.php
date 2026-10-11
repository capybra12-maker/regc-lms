<?php

require_once __DIR__ . '/includes/bootstrap.php';

require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/OperationsController.php';
require_once __DIR__ . '/app/controllers/UserController.php';
require_once __DIR__ . '/app/controllers/DieselController.php';
require_once __DIR__ . '/app/controllers/ReportsController.php';
require_once __DIR__ . '/app/controllers/NotificationController.php';
require_once __DIR__ . '/app/controllers/DriverController.php';
require_once __DIR__ . '/app/controllers/WarehouseController.php';
require_once __DIR__ . '/app/controllers/ProofController.php';
require_once __DIR__ . '/app/controllers/ScheduleController.php';
require_once __DIR__ . '/app/controllers/SettingsController.php';
require_once __DIR__ . '/app/controllers/LogController.php';
require_once __DIR__ . '/app/controllers/LandingController.php';

$route = $_GET['route'] ?? (
    isLoggedIn()
        ? dashboardRouteForRole(authUser()['role'] ?? 'administrator')
        : 'landing'
);

switch ($route) {
    // ----------------------------------------------------
    // Public / Landing Page
    // ----------------------------------------------------
    case 'landing':
        (new LandingController())->index();
        break;

    // ----------------------------------------------------
    // Authentication
    // ----------------------------------------------------
    case 'login':
        (new AuthController())->loginForm();
        break;

    case 'login-submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new AuthController())->login();
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    case 'account':
        (new AuthController())->account();
        break;

    case 'account-password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AuthController())->changePassword();
        } else {
            (new AuthController())->passwordForm();
        }
        break;

    // ----------------------------------------------------
    // Dashboards
    // ----------------------------------------------------
    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'driver':
        (new DriverController())->index();
        break;

    case 'driver-status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new DriverController())->updateStatus();
        break;

    case 'driver-gps':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new DriverController())->updateGps();
        break;

    case 'driver-map':
        (new DriverController())->map();
        break;

    case 'mobile-gps':
        (new DriverController())->mobileGps();
        break;

    case 'mobile-trip-status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new DriverController())->mobileTripStatus();
        break;

    case 'warehouse':
        (new WarehouseController())->index();
        break;

    case 'warehouse-confirm':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new WarehouseController())->confirmArrival();
        break;

    // ----------------------------------------------------
    // Operations & Core Catalogs
    // ----------------------------------------------------
    case 'customers':
    case 'trucks':
    case 'drivers':
    case 'shipments':
    case 'tracking':
        (new OperationsController())->handle($route);
        break;

    // ----------------------------------------------------
    // Proof of Delivery
    // ----------------------------------------------------
    case 'proof-form':
        (new ProofController())->form();
        break;

    case 'proof-upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new ProofController())->upload();
        break;

    case 'proof-view':
        (new ProofController())->view();
        break;

    // ----------------------------------------------------
    // Delivery Scheduling
    // ----------------------------------------------------
    case 'schedule':
        (new ScheduleController())->index();
        break;

    case 'schedule-form':
        (new ScheduleController())->form();
        break;

    case 'schedule-save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new ScheduleController())->save();
        break;

    // ----------------------------------------------------
    // User Management (Admin)
    // ----------------------------------------------------
    case 'users':
        (new UserController())->index();
        break;

    case 'users-form':
        (new UserController())->form();
        break;

    case 'users-save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new UserController())->save();
        break;

    case 'users-delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new UserController())->delete();
        break;

    // ----------------------------------------------------
    // Diesel & Fuel Management
    // ----------------------------------------------------
    case 'diesel':
        (new DieselController())->index();
        break;

    case 'diesel-form':
        (new DieselController())->form();
        break;

    case 'diesel-save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new DieselController())->save();
        break;

    case 'diesel-delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new DieselController())->delete();
        break;

    // ----------------------------------------------------
    // Reports & Analytics
    // ----------------------------------------------------
    case 'reports':
        (new ReportsController())->index();
        break;

    // ----------------------------------------------------
    // Notifications Center
    // ----------------------------------------------------
    case 'notifications':
        (new NotificationController())->index();
        break;

    case 'notifications-read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new NotificationController())->markRead();
        break;

    case 'notifications-read-all':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new NotificationController())->markAllRead();
        break;

    case 'notifications-poll':
        (new NotificationController())->poll();
        break;

    // ----------------------------------------------------
    // Settings & System Logs
    // ----------------------------------------------------
    case 'settings':
        (new SettingsController())->index();
        break;

    case 'settings-save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        (new SettingsController())->save();
        break;

    case 'logs':
        (new LogController())->index();
        break;

    // ----------------------------------------------------
    // 404 Not Found
    // ----------------------------------------------------
    case 'driver-gps-positions':
        // Lightweight JSON-only GPS positions endpoint for fleet map polling
        requireRole(['administrator']);
        header('Content-Type: application/json; charset=utf-8');
        $rows = db()->query("
            SELECT d.id, d.full_name, d.gps_lat, d.gps_lng, d.gps_updated_at, d.status,
                   s.tracking_number, s.current_status AS shipment_status,
                   s.destination, s.pickup_location,
                   s.pickup_lat, s.pickup_lng, s.destination_lat, s.destination_lng
            FROM drivers d
            LEFT JOIN shipments s ON s.driver_id = d.id
                AND s.current_status IN ('assigned','pickup','in_transit','arrived')
            WHERE d.status != 'inactive'
            ORDER BY d.full_name
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'drivers' => $rows, 'ts' => time()]);
        exit;

    case 'mail-test':
        // Admin utility: sends a test email to verify SMTP configuration
        requireRole(['administrator']);
        require_once __DIR__ . '/app/services/Mailer.php';
        $to = trim($_GET['to'] ?? (authUser()['email'] ?? ''));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(Mailer::testSmtp($to));
        exit;

    case 'sms-test':
        // Admin utility: send a test SMS to verify Semaphore API key
        requireRole(['administrator']);
        require_once __DIR__ . '/app/services/SMS.php';
        header('Content-Type: application/json; charset=utf-8');
        $toNum = trim($_GET['to'] ?? '');
        if (!$toNum) {
            echo json_encode(['ok' => false, 'message' => 'Provide a ?to=09XXXXXXXXX phone number.']);
            exit;
        }
        $fmtPhone = SMS::formatPhone($toNum);
        if (!$fmtPhone) {
            echo json_encode(['ok' => false, 'message' => "Invalid phone: {$toNum}. Use 09XXXXXXXXX or +639XXXXXXXXX."]);
            exit;
        }
        $smsTestMsg = 'REGC LMS Test: SMS is working! Your tracking code will be sent here. ✅ — REGC Fleet Management';
        echo json_encode(SMS::send($fmtPhone, $smsTestMsg));
        exit;

    default:
        http_response_code(404);
        require __DIR__ . '/app/views/404.php';
        break;
}

