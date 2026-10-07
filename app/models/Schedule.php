<?php

class Schedule
{
    public static function listing(?string $startDate = null, ?string $endDate = null): array
    {
        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = 'ds.pickup_datetime >= ?';
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $where[] = 'ds.pickup_datetime <= ?';
            $params[] = $endDate . ' 23:59:59';
        }

        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmt = db()->prepare("SELECT ds.*, 
            s.tracking_number, s.cargo_type, s.cargo_weight_kg, s.pickup_location, s.destination, s.current_status,
            t.plate_number, t.truck_model,
            d.full_name AS driver_name, d.contact_number AS driver_phone
            FROM delivery_schedules ds
            JOIN shipments s ON s.id = ds.shipment_id
            JOIN trucks t ON t.id = ds.truck_id
            JOIN drivers d ON d.id = ds.driver_id
            {$sql}
            ORDER BY ds.pickup_datetime ASC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function checkConflicts(int $truckId, int $driverId, string $pickup, ?string $delivery, int $ignoreScheduleId = 0): array
    {
        $conflicts = [];
        $end = $delivery ?: date('Y-m-d H:i:s', strtotime($pickup . ' +24 hours'));

        // Check truck conflicts
        $stmt = db()->prepare("SELECT ds.*, s.tracking_number, t.plate_number
            FROM delivery_schedules ds
            JOIN shipments s ON s.id = ds.shipment_id
            JOIN trucks t ON t.id = ds.truck_id
            WHERE ds.truck_id = ? AND ds.id <> ?
              AND ((ds.pickup_datetime BETWEEN ? AND ?)
                   OR (COALESCE(ds.expected_delivery_datetime, DATE_ADD(ds.pickup_datetime, INTERVAL 24 HOUR)) BETWEEN ? AND ?)
                   OR (ds.pickup_datetime <= ? AND COALESCE(ds.expected_delivery_datetime, DATE_ADD(ds.pickup_datetime, INTERVAL 24 HOUR)) >= ?))
            LIMIT 1");
        $stmt->execute([$truckId, $ignoreScheduleId, $pickup, $end, $pickup, $end, $pickup, $end]);
        $truckConflict = $stmt->fetch();
        if ($truckConflict) {
            $conflicts[] = "Truck {$truckConflict['plate_number']} is already scheduled for shipment {$truckConflict['tracking_number']} during this window.";
        }

        // Check driver conflicts
        $stmt = db()->prepare("SELECT ds.*, s.tracking_number, d.full_name
            FROM delivery_schedules ds
            JOIN shipments s ON s.id = ds.shipment_id
            JOIN drivers d ON d.id = ds.driver_id
            WHERE ds.driver_id = ? AND ds.id <> ?
              AND ((ds.pickup_datetime BETWEEN ? AND ?)
                   OR (COALESCE(ds.expected_delivery_datetime, DATE_ADD(ds.pickup_datetime, INTERVAL 24 HOUR)) BETWEEN ? AND ?)
                   OR (ds.pickup_datetime <= ? AND COALESCE(ds.expected_delivery_datetime, DATE_ADD(ds.pickup_datetime, INTERVAL 24 HOUR)) >= ?))
            LIMIT 1");
        $stmt->execute([$driverId, $ignoreScheduleId, $pickup, $end, $pickup, $end, $pickup, $end]);
        $driverConflict = $stmt->fetch();
        if ($driverConflict) {
            $conflicts[] = "Driver {$driverConflict['full_name']} is already scheduled for shipment {$driverConflict['tracking_number']} during this window.";
        }

        return $conflicts;
    }

    public static function save(array $input, int $id = 0): int
    {
        $shipmentId = (int) ($input['shipment_id'] ?? 0);
        $truckId = (int) ($input['truck_id'] ?? 0);
        $driverId = (int) ($input['driver_id'] ?? 0);
        $pickup = trim((string) ($input['pickup_datetime'] ?? ''));
        $delivery = !empty($input['expected_delivery_datetime']) ? trim((string) $input['expected_delivery_datetime']) : null;
        $notes = trim((string) ($input['notes'] ?? ''));

        if (!$shipmentId || !$truckId || !$driverId || !$pickup) {
            throw new DomainException('Shipment, truck, driver, and pickup datetime are required.');
        }

        // Check conflicts
        $conflicts = self::checkConflicts($truckId, $driverId, $pickup, $delivery, $id);
        if ($conflicts) {
            throw new DomainException(implode(' ', $conflicts));
        }

        $userId = authUser()['id'] ?? 1;

        if ($id === 0) {
            $stmt = db()->prepare("INSERT INTO delivery_schedules (shipment_id, truck_id, driver_id, pickup_datetime, expected_delivery_datetime, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$shipmentId, $truckId, $driverId, $pickup, $delivery, $notes, $userId]);
            $savedId = (int) db()->lastInsertId();
            logActivity('Create Schedule', 'Schedule', "Created delivery schedule for Shipment #{$shipmentId}");
            return $savedId;
        }

        $stmt = db()->prepare("UPDATE delivery_schedules SET
            shipment_id = ?, truck_id = ?, driver_id = ?, pickup_datetime = ?, expected_delivery_datetime = ?, notes = ?
            WHERE id = ?");
        $stmt->execute([$shipmentId, $truckId, $driverId, $pickup, $delivery, $notes, $id]);
        logActivity('Update Schedule', 'Schedule', "Updated delivery schedule #{$id}");
        return $id;
    }
}

