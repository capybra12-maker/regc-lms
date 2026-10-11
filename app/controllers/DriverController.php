<?php

require_once __DIR__ . '/../models/Operations.php';
require_once __DIR__ . '/../models/Proof.php';

class DriverController
{
    private function isModalRequest(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'REGC-Modal';
    }

    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public function index(): void
    {
        requireRole(['driver', 'administrator']);
        $user = authUser();

        $driver = null;
        if ($user['role'] === 'driver') {
            $stmt = db()->prepare('SELECT * FROM drivers WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $driver = $stmt->fetch();
        }

        $driverId = $driver ? (int) $driver['id'] : null;

        $pdo = db();
        if ($driverId) {
            // Active shipments
            $activeStmt = $pdo->prepare("SELECT s.*, c.contact_person, c.company_name, c.contact_number, t.plate_number, t.truck_model
                FROM shipments s
                JOIN customers c ON c.id = s.customer_id
                LEFT JOIN trucks t ON t.id = s.truck_id
                WHERE s.driver_id = ? AND s.current_status IN ('assigned','pickup','in_transit','arrived')
                ORDER BY s.id DESC");
            $activeStmt->execute([$driverId]);
            $activeShipments = $activeStmt->fetchAll();

            // Completed shipments
            $completedStmt = $pdo->prepare("SELECT s.*, c.contact_person, c.company_name, t.plate_number
                FROM shipments s
                JOIN customers c ON c.id = s.customer_id
                LEFT JOIN trucks t ON t.id = s.truck_id
                WHERE s.driver_id = ? AND s.current_status = 'delivered'
                ORDER BY s.updated_at DESC
                LIMIT 15");
            $completedStmt->execute([$driverId]);
            $completedShipments = $completedStmt->fetchAll();

            // Assigned Truck
            $truckStmt = $pdo->prepare("SELECT * FROM trucks WHERE assigned_driver_id = ? OR id IN (SELECT truck_id FROM shipments WHERE driver_id = ? AND current_status <> 'delivered') LIMIT 1");
            $truckStmt->execute([$driverId, $driverId]);
            $assignedTruck = $truckStmt->fetch();
        } else {
            // Admin viewing driver workspace or driver without linked record
            $activeShipments = $pdo->query("SELECT s.*, c.contact_person, c.company_name, c.contact_number, t.plate_number, t.truck_model, d.full_name AS driver_name
                FROM shipments s
                JOIN customers c ON c.id = s.customer_id
                LEFT JOIN trucks t ON t.id = s.truck_id
                LEFT JOIN drivers d ON d.id = s.driver_id
                WHERE s.current_status IN ('assigned','pickup','in_transit','arrived')
                ORDER BY s.id DESC")->fetchAll();
            $completedShipments = [];
            $assignedTruck = null;
        }

        $module = 'driver';
        require __DIR__ . '/../views/driver-dashboard.php';
    }

    public function updateStatus(): void
    {
        requireRole(['driver', 'administrator']);
        verifyCsrf();

        $shipmentId = (int) ($_POST['id'] ?? 0);
        $nextStatus = trim((string) ($_POST['status'] ?? ''));
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        try {
            Operations::advanceShipment($shipmentId, $nextStatus, $remarks);
            $_SESSION['notice'] = 'Delivery status successfully updated to ' . ucwords(str_replace('_', ' ', $nextStatus)) . '.';

            if ($this->isModalRequest()) {
                $this->json(['redirect' => 'index.php?route=driver']);
            }
            header('Location: index.php?route=driver');
            exit;
        } catch (DomainException $e) {
            if ($this->isModalRequest()) {
                $this->json(['message' => $e->getMessage()], 422);
            }
            $_SESSION['operation_error'] = $e->getMessage();
            header('Location: index.php?route=driver');
            exit;
        }
    }

    public function updateGps(): void
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            if (!empty($json['waypoints']) && is_array($json['waypoints'])) {
                $latest = end($json['waypoints']);
                $lat = isset($latest['lat']) ? filter_var($latest['lat'], FILTER_VALIDATE_FLOAT) : null;
                $lng = isset($latest['lng']) ? filter_var($latest['lng'], FILTER_VALIDATE_FLOAT) : null;
            } else {
                $lat = isset($json['lat']) ? filter_var($json['lat'], FILTER_VALIDATE_FLOAT) : null;
                $lng = isset($json['lng']) ? filter_var($json['lng'], FILTER_VALIDATE_FLOAT) : null;
            }
            if (isset($json['driver_id'])) {
                $_POST['driver_id'] = $json['driver_id'];
            }
        } else {
            $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
            $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);
        }

        if ($lat === null || $lat === false || $lng === null || $lng === false) {
            $this->json(['error' => 'Invalid coordinates. Require numeric lat (-90 to 90) and lng (-180 to 180).'], 400);
        }

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            $this->json(['error' => 'Coordinates out of geographic range.'], 400);
        }

        $driverId = null;

        if (isLoggedIn()) {
            $user = authUser();
            if ($user['role'] === 'driver') {
                $stmt = db()->prepare('SELECT id FROM drivers WHERE user_id = ? LIMIT 1');
                $stmt->execute([$user['id']]);
                $driverId = $stmt->fetchColumn() ?: null;
            } elseif ($user['role'] === 'administrator' && !empty($_POST['driver_id'])) {
                $driverId = (int) $_POST['driver_id'];
            }
        } else {
            // External GPS API Device / Webhook / Mobile Web App authentication
            $apiKey = $_POST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? ($json['api_key'] ?? '');
            $configuredKey = defined('GPS_API_KEY') ? GPS_API_KEY : 'regc-gps-live';
            if ($apiKey !== '' && ($apiKey === $configuredKey || $apiKey === (defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : ''))) {
                $driverId = (int) ($_POST['driver_id'] ?? $_GET['driver_id'] ?? ($json['driver_id'] ?? 0));
                if (!$driverId && !empty($_POST['license_number'])) {
                    $stmt = db()->prepare('SELECT id FROM drivers WHERE license_number = ? LIMIT 1');
                    $stmt->execute([trim($_POST['license_number'])]);
                    $driverId = $stmt->fetchColumn() ?: null;
                }
            } else {
                $this->json(['error' => 'Unauthorized GPS API call. Provide valid session or api_key.'], 401);
            }
        }

        if (!$driverId) {
            $this->json(['error' => 'Driver record not found.'], 404);
        }

        db()->prepare('UPDATE drivers SET gps_lat = ?, gps_lng = ?, gps_updated_at = NOW() WHERE id = ?')
            ->execute([$lat, $lng, $driverId]);

        $batchCount = (!empty($json['waypoints']) && is_array($json['waypoints'])) ? count($json['waypoints']) : 1;

        $this->json([
            'ok' => true,
            'driver_id' => (int) $driverId,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'synced_count' => $batchCount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function map(): void
    {
        requireRole(['administrator']);

        $pdo = db();

        // Fetch active drivers with GPS data
        $drivers = $pdo->query("
            SELECT d.id, d.full_name, d.gps_lat, d.gps_lng, d.gps_updated_at, d.status,
                   s.tracking_number, s.pickup_location, s.destination, s.current_status AS shipment_status,
                   s.pickup_lat, s.pickup_lng, s.destination_lat, s.destination_lng
            FROM drivers d
            LEFT JOIN shipments s ON s.driver_id = d.id AND s.current_status IN ('pickup','in_transit','arrived')
            WHERE d.status IN ('available','assigned','on_delivery')
            ORDER BY d.full_name
        ")->fetchAll();

        // Use the live APP_URL as the public GPS link (tunnel no longer needed)
        $publicTunnelUrl = defined('APP_URL') && APP_URL ? APP_URL : '';

        $module = 'driver';
        $mapsKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
        require __DIR__ . '/../views/driver-map.php';
    }

    public function mobileGps(): void
    {
        $pdo = db();
        $drivers = $pdo->query("SELECT id, full_name, license_number, status, gps_lat, gps_lng FROM drivers ORDER BY full_name")->fetchAll();

        $selectedDriverId = null;
        if (isLoggedIn()) {
            $user = authUser();
            if ($user['role'] === 'driver') {
                $stmt = $pdo->prepare('SELECT id FROM drivers WHERE user_id = ? LIMIT 1');
                $stmt->execute([$user['id']]);
                $selectedDriverId = $stmt->fetchColumn() ?: null;
            }
        }
        if (!$selectedDriverId && !empty($_GET['driver_id'])) {
            $selectedDriverId = (int) $_GET['driver_id'];
        }

        // Fetch active shipment assigned to this driver
        $activeShipment = null;
        if ($selectedDriverId) {
            $sStmt = $pdo->prepare("SELECT s.*, c.contact_person, c.company_name, c.contact_number, t.plate_number, t.truck_model
                FROM shipments s
                JOIN customers c ON c.id = s.customer_id
                LEFT JOIN trucks t ON t.id = s.truck_id
                WHERE s.driver_id = ? AND s.current_status IN ('assigned','pickup','in_transit','arrived')
                ORDER BY s.id DESC LIMIT 1");
            $sStmt->execute([$selectedDriverId]);
            $activeShipment = $sStmt->fetch() ?: null;
        }

        // Use the live APP_URL as the public GPS link (tunnel no longer needed)
        $publicTunnelUrl = defined('APP_URL') && APP_URL ? APP_URL : '';

        $serverIp = '192.168.1.9';
        require __DIR__ . '/../views/mobile-gps.php';
    }

    public function mobileTripStatus(): void
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true) ?: [];

        $driverId = (int) ($json['driver_id'] ?? $_POST['driver_id'] ?? 0);
        $shipmentId = (int) ($json['shipment_id'] ?? $_POST['shipment_id'] ?? 0);
        $nextStatus = trim((string) ($json['status'] ?? $_POST['status'] ?? ''));
        $apiKey = $json['api_key'] ?? $_POST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';

        $configuredKey = defined('GPS_API_KEY') ? GPS_API_KEY : 'regc-gps-live';
        $isAuthed = isLoggedIn() || ($apiKey !== '' && $apiKey === $configuredKey);

        if (!$isAuthed) {
            $this->json(['error' => 'Unauthorized trip status update.'], 401);
        }

        if (!$shipmentId || !$nextStatus) {
            $this->json(['error' => 'Missing shipment ID or next status.'], 400);
        }

        try {
            Operations::advanceShipment($shipmentId, $nextStatus, "Updated via Mobile Driver GPS Transmitter.");
            $this->json([
                'ok' => true,
                'status' => $nextStatus,
                'label' => ucwords(str_replace('_', ' ', $nextStatus)),
                'message' => "Delivery status advanced to " . ucwords(str_replace('_', ' ', $nextStatus))
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 422);
        }
    }
}


