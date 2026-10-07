<?php
/**
 * REGC LMS — Database Seed Script
 * Creates sample data for demonstration.
 * Run once on a fresh installation: php database/seed.php
 */

require_once __DIR__ . '/../includes/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();

try {
    $adminPwd = password_hash('Admin@123456', PASSWORD_DEFAULT);
    $driverPwd = password_hash('Driver@123456', PASSWORD_DEFAULT);
    $warehousePwd = password_hash('Warehouse@123', PASSWORD_DEFAULT);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE system_logs");
    $pdo->exec("TRUNCATE TABLE notifications");
    $pdo->exec("TRUNCATE TABLE diesel_transactions");
    $pdo->exec("TRUNCATE TABLE proof_of_delivery");
    $pdo->exec("TRUNCATE TABLE delivery_schedules");
    $pdo->exec("TRUNCATE TABLE shipment_status_history");
    $pdo->exec("TRUNCATE TABLE shipments");
    $pdo->exec("TRUNCATE TABLE trucks");
    $pdo->exec("TRUNCATE TABLE drivers");
    $pdo->exec("TRUNCATE TABLE customers");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->beginTransaction();

    $insertUser = $pdo->prepare("INSERT INTO users (full_name, email, password, role, status, last_login_at) VALUES (?, ?, ?, ?, 'active', NOW())");
    $insertUser->execute(['Administrator', 'admin@regc.com', $adminPwd, 'administrator']);
    $adminId = (int) $pdo->lastInsertId();
    $insertUser->execute(['Juan Dela Cruz', 'driver1@regc.com', $driverPwd, 'driver']);
    $driverUserId1 = (int) $pdo->lastInsertId();
    $insertUser->execute(['Pedro Santos', 'driver2@regc.com', $driverPwd, 'driver']);
    $driverUserId2 = (int) $pdo->lastInsertId();
    $insertUser->execute(['Maria Reyes', 'warehouse@regc.com', $warehousePwd, 'warehouse']);
    $warehouseUserId = (int) $pdo->lastInsertId();

    // --------------------------------------------------------
    // CUSTOMERS
    // --------------------------------------------------------
    $insertCustomer = $pdo->prepare("INSERT INTO customers (company_name, contact_person, contact_number, email, address, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $insertCustomer->execute(['GenSan Trading Corp.', 'Renato Bautista', '09171234567', 'rbautista@gensan-trading.com', 'Brgy. Dadiangas East, General Santos City']);
    $cust1 = (int) $pdo->lastInsertId();
    $insertCustomer->execute(['Mindanao Agri Exports', 'Lourdes Villanueva', '09281234567', 'lvillanueva@mindagri.com', 'Koronadal City, South Cotabato']);
    $cust2 = (int) $pdo->lastInsertId();
    $insertCustomer->execute(['Pacific Cement Inc.', 'Roberto Gomez', '09391234567', 'rgomez@paccement.com', 'Davao City, Davao del Sur']);
    $cust3 = (int) $pdo->lastInsertId();
    $insertCustomer->execute(['SoCot Rice Millers', 'Elena Castillo', '09501234567', 'ecastillo@socotrice.com', 'Tupi, South Cotabato']);
    $cust4 = (int) $pdo->lastInsertId();

    // --------------------------------------------------------
    // DRIVERS
    // --------------------------------------------------------
    $insertDriver = $pdo->prepare("INSERT INTO drivers (user_id, full_name, contact_number, license_number, license_expiration, emergency_contact, emergency_contact_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertDriver->execute([$driverUserId1, 'Juan Dela Cruz', '09171234567', 'N01-23-456789', '2027-06-30', 'Ana Dela Cruz', '09172345678', 'available']);
    $driver1 = (int) $pdo->lastInsertId();
    $insertDriver->execute([$driverUserId2, 'Pedro Santos', '09281234567', 'N01-23-987654', '2026-12-15', 'Rosa Santos', '09282345678', 'available']);
    $driver2 = (int) $pdo->lastInsertId();
    $insertDriver->execute([null, 'Alvin Reyes', '09391234567', 'N01-24-112233', '2028-03-20', 'Carol Reyes', '09392345678', 'off_duty']);
    $driver3 = (int) $pdo->lastInsertId();

    // --------------------------------------------------------
    // TRUCKS
    // --------------------------------------------------------
    $insertTruck = $pdo->prepare("INSERT INTO trucks (plate_number, truck_model, truck_type, capacity_kg, status, registration_number, registration_expiration, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertTruck->execute(['SKH-1234', 'Isuzu NQR', '10-Wheeler', 10000, 'available', 'REG-2024-001', '2026-12-31', 'Main long-haul truck']);
    $truck1 = (int) $pdo->lastInsertId();
    $insertTruck->execute(['SKH-5678', 'Mitsubishi Fuso', '6-Wheeler', 5000, 'available', 'REG-2024-002', '2026-11-30', 'Medium cargo truck']);
    $truck2 = (int) $pdo->lastInsertId();
    $insertTruck->execute(['GEN-9012', 'Hino 500', '12-Wheeler', 15000, 'maintenance', 'REG-2023-003', '2025-10-15', 'Undergoing PMS']);
    $truck3 = (int) $pdo->lastInsertId();

    // --------------------------------------------------------
    // SHIPMENTS
    // --------------------------------------------------------
    $insertShipment = $pdo->prepare(
        "INSERT INTO shipments (tracking_number, customer_id, cargo_type, cargo_description, cargo_weight_kg, pickup_location, destination, shipment_date, expected_delivery_date, truck_id, driver_id, current_status, freight_amount, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    // Shipment 1 — Delivered
    $insertShipment->execute([
        'REGC-2026-000001', $cust1, 'Cement', 'Portland cement bags', 8000,
        'Pier 1, General Santos City', 'Davao City Warehouse, Davao del Sur',
        date('Y-m-d H:i:s', strtotime('-10 days')),
        date('Y-m-d H:i:s', strtotime('-8 days')),
        $truck1, $driver1, 'delivered', 25000, 'Priority delivery', $adminId
    ]);
    $ship1 = (int) $pdo->lastInsertId();

    // Shipment 2 — In Transit
    $insertShipment->execute([
        'REGC-2026-000002', $cust2, 'Soya', 'Soybean meal in sacks', 4500,
        'Pier 2, General Santos City', 'Koronadal City, South Cotabato',
        date('Y-m-d H:i:s', strtotime('-2 days')),
        date('Y-m-d H:i:s', strtotime('+1 day')),
        $truck2, $driver2, 'in_transit', 14000, null, $adminId
    ]);
    $ship2 = (int) $pdo->lastInsertId();

    // Shipment 3 — Pending
    $insertShipment->execute([
        'REGC-2026-000003', $cust3, 'Rice', 'Milled white rice', 6000,
        'SoCot Rice Mill, Tupi', 'Pier 3, General Santos City',
        date('Y-m-d H:i:s', strtotime('+1 day')),
        date('Y-m-d H:i:s', strtotime('+3 days')),
        null, null, 'pending', null, null, $adminId
    ]);
    $ship3 = (int) $pdo->lastInsertId();

    // Shipment 4 — Assigned
    $insertShipment->execute([
        'REGC-2026-000004', $cust4, 'Corn', 'Yellow corn grains', 3200,
        'Pier 1, General Santos City', 'Davao Agri Hub, Digos City',
        date('Y-m-d H:i:s', strtotime('+2 days')),
        date('Y-m-d H:i:s', strtotime('+4 days')),
        null, null, 'pending', 9600, null, $adminId
    ]);
    $ship4 = (int) $pdo->lastInsertId();

    // --------------------------------------------------------
    // STATUS HISTORY
    // --------------------------------------------------------
    $insertHistory = $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, updated_by, remarks) VALUES (?, ?, ?, ?)");
    // Ship 1 full history
    foreach ([
        ['pending',    strtotime('-10 days'), 'Shipment created and logged.'],
        ['assigned',   strtotime('-10 days +1 hour'), 'Truck SKH-1234 and driver Juan Dela Cruz assigned.'],
        ['pickup',     strtotime('-9 days'),  'Cargo picked up at Pier 1 GenSan.'],
        ['in_transit', strtotime('-9 days +3 hours'), 'En route to Davao City.'],
        ['arrived',    strtotime('-8 days'),  'Arrived at Davao City Warehouse.'],
        ['delivered',  strtotime('-8 days +2 hours'), 'Delivery confirmed. All items accounted for.'],
    ] as [$status, $time, $remark]) {
        $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, updated_by, remarks, created_at) VALUES (?, ?, ?, ?, ?)")
            ->execute([$ship1, $status, $adminId, $remark, date('Y-m-d H:i:s', $time)]);
    }
    // Ship 2 history
    foreach ([
        ['pending',    strtotime('-3 days'), 'Shipment created.'],
        ['assigned',   strtotime('-2 days +2 hours'), 'Resources assigned.'],
        ['pickup',     strtotime('-2 days +4 hours'), 'Cargo loaded at Pier 2.'],
        ['in_transit', strtotime('-1 day'), 'Departed GenSan, en route to Koronadal.'],
    ] as [$status, $time, $remark]) {
        $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, updated_by, remarks, created_at) VALUES (?, ?, ?, ?, ?)")
            ->execute([$ship2, $status, $adminId, $remark, date('Y-m-d H:i:s', $time)]);
    }
    // Ship 3 & 4 pending history
    $insertHistory->execute([$ship3, 'pending', $adminId, 'Shipment created. Awaiting truck and driver assignment.']);
    $insertHistory->execute([$ship4, 'pending', $adminId, 'Shipment created. Awaiting assignment.']);

    // --------------------------------------------------------
    // DIESEL TRANSACTIONS
    // --------------------------------------------------------
    $insertDiesel = $pdo->prepare(
        "INSERT INTO diesel_transactions (shipment_id, truck_id, driver_id, transaction_date, liters, price_per_liter, total_cost, trip_distance_km, notes, recorded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $insertDiesel->execute([$ship1, $truck1, $driver1, date('Y-m-d', strtotime('-9 days')), 80, 65.00, 5200, 180, 'GenSan to Davao trip', $adminId]);
    $insertDiesel->execute([$ship2, $truck2, $driver2, date('Y-m-d', strtotime('-2 days')), 45, 65.00, 2925, 80, 'GenSan to Koronadal', $adminId]);
    $insertDiesel->execute([null,   $truck3, null,     date('Y-m-d', strtotime('-5 days')), 20, 65.00, 1300, null, 'Test run after PMS', $adminId]);

    // --------------------------------------------------------
    // NOTIFICATIONS
    // --------------------------------------------------------
    $insertNotif = $pdo->prepare(
        "INSERT INTO notifications (user_id, role, title, message, type, link, is_read) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $insertNotif->execute([null, 'administrator', 'New Shipment Created', 'Shipment REGC-2026-000004 has been created and is pending assignment.', 'info', 'index.php?route=shipments&action=show&id=' . $ship4, 0]);
    $insertNotif->execute([null, 'administrator', 'Shipment In Transit', 'REGC-2026-000002 is now in transit to Koronadal City.', 'success', 'index.php?route=shipments&action=show&id=' . $ship2, 0]);
    $insertNotif->execute([$driverUserId1, null, 'Delivery Completed', 'You have successfully completed delivery of REGC-2026-000001. Well done!', 'success', 'index.php?route=driver', 1]);

    // --------------------------------------------------------
    // PROOF OF DELIVERY (ship1 delivered)
    // --------------------------------------------------------
    $pdo->prepare("INSERT INTO proof_of_delivery (shipment_id, received_by, notes, uploaded_by) VALUES (?, ?, ?, ?)")
        ->execute([$ship1, 'Jose Mendoza - Davao Warehouse Manager', 'All 8000kg received in good condition.', $adminId]);

    $pdo->commit();

    echo "✅ Seed data created successfully.\n\n";
    echo "Login credentials:\n";
    echo "  Admin:     admin@regc.com / Admin@123456\n";
    echo "  Driver 1:  driver1@regc.com / Driver@123456\n";
    echo "  Driver 2:  driver2@regc.com / Driver@123456\n";
    echo "  Warehouse: warehouse@regc.com / Warehouse@123\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Seed failed: " . $e->getMessage() . "\n";
    exit(1);
}
