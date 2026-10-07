<?php
// Run with: C:\xampp\php\php.exe tests/operations.php
// Uses a uniquely named disposable database; never changes the application's records.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../includes/env.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/security.php';
require __DIR__ . '/../app/models/Operations.php';

$databaseName = 'regc_test_' . bin2hex(random_bytes(6));
$testPdo = new PDO(
    'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
);
function db(): PDO { global $testPdo; return $testPdo; }
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $message); }
    $checks++;
    echo 'PASS: ', $message, PHP_EOL;
}
function rejects(callable $action, string $message): void {
    try { $action(); } catch (DomainException $error) { check(true, $message); return; }
    throw new RuntimeException('FAIL: ' . $message);
}
function scalar(string $sql, array $params = []): mixed {
    $stmt = db()->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn();
}

try {
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    $schema = str_replace(['CREATE DATABASE IF NOT EXISTS regc_lms', 'USE regc_lms;'], ['CREATE DATABASE ' . $databaseName, 'USE ' . $databaseName . ';'], $schema);
    $testPdo->exec($schema);
    db()->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)')
        ->execute(['Test administrator', 'test@example.invalid', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), 'administrator']);
    $_SESSION = ['user' => ['id' => (int) db()->lastInsertId(), 'name' => 'Test administrator', 'role' => 'administrator']];
    $customerData = ['contact_person' => 'Integration Customer', 'company_name' => 'Test Company', 'email' => 'customer@example.invalid', 'status' => 'active'];
    $customer = Operations::saveCatalog('customers', $customerData, 0);
    check(Operations::find('customers', $customer)['contact_person'] === 'Integration Customer', 'Create customer');
    $customerData['contact_person'] = 'Updated Customer';
    Operations::saveCatalog('customers', $customerData, $customer);
    check(Operations::find('customers', $customer)['contact_person'] === 'Updated Customer', 'Edit customer');
    rejects(fn() => Operations::saveCatalog('customers', array_merge($customerData, ['email' => 'invalid']), 0), 'Reject invalid email');
    rejects(fn() => Operations::saveCatalog('customers', array_merge($customerData, ['contact_person' => []]), 0), 'Reject malformed fields');
    $truckData = ['plate_number' => 'TEST-001', 'capacity_kg' => '1000', 'status' => 'available'];
    $truck = Operations::saveCatalog('trucks', $truckData, 0);
    $driverData = ['full_name' => 'Test Driver', 'license_number' => 'TEST-LICENSE', 'status' => 'available'];
    $driver = Operations::saveCatalog('drivers', $driverData, 0);
    $truck2 = Operations::saveCatalog('trucks', array_merge($truckData, ['plate_number' => 'TEST-002']), 0);
    $driver2 = Operations::saveCatalog('drivers', array_merge($driverData, ['license_number' => 'TEST-LICENSE-2']), 0);
    try { Operations::saveCatalog('trucks', $truckData, 0); throw new RuntimeException('Duplicate accepted'); }
    catch (PDOException $error) { check((int) $error->errorInfo[1] === 1062, 'Reject duplicate plate number'); }
    $shipmentData = ['customer_id' => (string) $customer, 'cargo_type' => 'Boxes', 'cargo_weight_kg' => '500', 'pickup_location' => 'Depot', 'destination' => 'Customer warehouse'];
    $shipment = Operations::saveShipment($shipmentData, 0);
    $record = Operations::find('shipments', $shipment);
    check($record['current_status'] === 'pending' && preg_match('/^REGC-(\d{4}-\d{6}|\d{8}-[A-F0-9]{10})$/', $record['tracking_number']) === 1, 'Create pending shipment with tracking number');
    rejects(fn() => Operations::advanceShipment($shipment, 'assigned', ''), 'Require assignment before pickup');
    rejects(fn() => Operations::saveShipment(array_merge($shipmentData, ['truck_id' => $truck]), $shipment), 'Reject incomplete assignment');
    rejects(fn() => Operations::saveShipment(array_merge($shipmentData, ['cargo_weight_kg' => '0']), 0), 'Reject zero weight');
    rejects(fn() => Operations::saveShipment(array_merge($shipmentData, ['shipment_date' => '2026-02-30T12:00']), 0), 'Reject invalid calendar date');
    rejects(fn() => Operations::saveShipment(array_merge($shipmentData, ['shipment_date' => '2027-01-02T12:00', 'expected_delivery_date' => '2027-01-01T12:00']), 0), 'Reject delivery before pickup');
    $assigned = array_merge($shipmentData, ['truck_id' => (string) $truck, 'driver_id' => (string) $driver]);
    // Capacity restriction removed per requirements
    check(Operations::find('trucks', $truck)['status'] === 'available', 'Truck remains available before assignment');
    db()->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)')
        ->execute(['Test driver', 'driver@example.invalid', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), 'driver']);
    $driverUserId = (int) db()->lastInsertId();
    db()->prepare('UPDATE drivers SET user_id = ? WHERE id = ?')->execute([$driverUserId, $driver]);
    set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try {
        Operations::saveShipment($assigned, $shipment);
    } finally {
        restore_error_handler();
    }
    check(scalar('SELECT message FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$driverUserId]) === 'You have been assigned to delivery ' . $record['tracking_number'] . '.', 'Existing shipment assignment notifies linked driver with its tracking number without warnings');
    check(Operations::find('shipments', $shipment)['tracking_number'] === $record['tracking_number'], 'Assignment preserves the existing tracking number');
    check(Operations::find('trucks', $truck)['status'] === 'assigned' && Operations::find('drivers', $driver)['status'] === 'assigned', 'Assignment reserves truck and driver');
    rejects(fn() => Operations::saveShipment($assigned, 0), 'Reject double booking');
    rejects(fn() => Operations::delete('customers', $customer), 'Protect customer history');
    rejects(fn() => Operations::delete('trucks', $truck), 'Protect truck history');
    rejects(fn() => Operations::delete('drivers', $driver), 'Protect driver history');
    rejects(fn() => Operations::delete('shipments', $shipment), 'Prevent deletion of assigned shipment');
    Operations::saveCatalog('trucks', array_merge($truckData, ['status' => 'available']), $truck);
    check(Operations::find('trucks', $truck)['status'] === 'assigned', 'Manual edit cannot free reserved truck');
    // Capacity restriction removed per requirements
    $reassigned = array_merge($assigned, ['truck_id' => $truck2, 'driver_id' => $driver2]);
    Operations::saveShipment($reassigned, $shipment);
    check(Operations::find('trucks', $truck)['status'] === 'available' && Operations::find('drivers', $driver)['status'] === 'available', 'Reassignment releases old resources');
    Operations::saveShipment($shipmentData, $shipment);
    check(Operations::find('shipments', $shipment)['current_status'] === 'pending' && Operations::find('trucks', $truck2)['status'] === 'available', 'Unassign returns shipment to pending');
    Operations::saveCatalog('drivers', array_merge($driverData, ['license_expiration' => '2000-01-01']), $driver);
    rejects(fn() => Operations::saveShipment($assigned, $shipment), 'Reject expired driver license');
    check(Operations::find('trucks', $truck)['status'] === 'available', 'Driver rejection rolls back truck reservation');
    Operations::saveCatalog('drivers', $driverData, $driver);
    Operations::saveShipment($assigned, $shipment);
    rejects(fn() => Operations::advanceShipment($shipment, 'delivered', ''), 'Reject skipped delivery stages');
    Operations::advanceShipment($shipment, 'pickup', 'Collected from depot');
    check(Operations::find('drivers', $driver)['status'] === 'on_delivery', 'Pickup marks resources in use');
    rejects(fn() => Operations::saveShipment($assigned, $shipment), 'Lock shipment details after pickup');
    rejects(fn() => Operations::advanceShipment($shipment, 'pickup', ''), 'Reject duplicate status submission');
    Operations::advanceShipment($shipment, 'in_transit', 'On the road');
    Operations::advanceShipment($shipment, 'arrived', 'At destination');
    Operations::advanceShipment($shipment, 'delivered', 'Received by customer');
    check(Operations::find('shipments', $shipment)['current_status'] === 'delivered', 'Complete delivery lifecycle');
    check(Operations::find('trucks', $truck)['status'] === 'available' && Operations::find('drivers', $driver)['status'] === 'available', 'Delivery releases truck and driver');
    rejects(fn() => Operations::advanceShipment($shipment, 'pending', ''), 'Delivered shipment cannot go backwards');
    check(Operations::timeline($shipment)[0]['remarks'] === 'Received by customer', 'Tracking history contains latest delivery remarks');
    check(Operations::shipmentDetail($shipment)['driver_name'] === 'Test Driver', 'Shipment details resolve customer and fleet records');
    check((int) scalar('SELECT COUNT(*) FROM shipments') === 1, 'Invalid submissions leave no orphan shipments');
    check(Operations::listing('shipments', $record['tracking_number'], 'delivered', 1)['total'] === 1, 'Search and status filters find delivery');
    check(Operations::listing('shipments', "' OR 1=1 --", '', 1)['total'] === 0, 'Search input remains a literal parameter');
    check(Operations::listing('shipments', '', '', 999)['page'] === 1, 'Pagination clamps invalid pages');
    $pending = Operations::saveShipment($shipmentData, 0);
    Operations::delete('shipments', $pending);
    check(Operations::find('shipments', $pending) === null && (int) scalar('SELECT COUNT(*) FROM shipment_status_history WHERE shipment_id = ?', [$pending]) === 0, 'Delete pending shipment and its history');
    Operations::delete('trucks', $truck2);
    Operations::delete('drivers', $driver2);
    check(Operations::find('trucks', $truck2) === null && Operations::find('drivers', $driver2) === null, 'Delete unused fleet records');
    $unused = Operations::saveCatalog('customers', $customerData, 0);
    Operations::delete('customers', $unused);
    check(Operations::find('customers', $unused) === null, 'Delete unused customer');
    check((int) scalar('SELECT COUNT(*) FROM system_logs') > 0, 'Changes are audited');
    $module = 'shipments';
    $shipment = Operations::shipmentDetail($shipment);
    $shipment['notes'] = '<script>alert("test")</script>';
    $history = Operations::timeline((int) $shipment['id']);
    ob_start();
    require __DIR__ . '/../app/views/operations/shipment.php';
    $html = ob_get_clean();
    check(str_contains($html, '&lt;script&gt;') && !str_contains($html, '<script>alert('), 'Shipment view escapes stored notes');
    check(str_contains($html, 'Received by customer') && str_contains($html, 'Delivery completed.'), 'Shipment page renders delivery and tracking history');
    $module = 'tracking';
    $number = $shipment['tracking_number'];
    ob_start();
    require __DIR__ . '/../app/views/operations/tracking.php';
    $html = ob_get_clean();
    check(str_contains($html, $number) && str_contains($html, 'Manage this shipment'), 'Tracking lookup view renders matching shipment');
    echo "All {$checks} checks passed.", PHP_EOL;
} finally {
    // This identifier is generated above, never accepted from a request or environment.
    if (!preg_match('/^regc_test_[a-f0-9]{12}$/D', $databaseName)) { throw new RuntimeException('Invalid test database name.'); }
    $testPdo->exec('DROP DATABASE IF EXISTS `' . $databaseName . '`');
}
