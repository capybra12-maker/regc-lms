<?php

class Diesel
{
    public const BASELINE_LITERS = 15.0;

    public static function listing(string $search = '', string $truckId = '', string $startDate = '', string $endDate = '', int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if ($truckId !== '') {
            $where[] = 'dt.truck_id = ?';
            $params[] = (int) $truckId;
        }

        if ($startDate !== '') {
            $where[] = 'dt.transaction_date >= ?';
            $params[] = $startDate;
        }

        if ($endDate !== '') {
            $where[] = 'dt.transaction_date <= ?';
            $params[] = $endDate;
        }

        if ($search !== '') {
            $where[] = '(t.plate_number LIKE ? OR d.full_name LIKE ? OR s.tracking_number LIKE ? OR dt.notes LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = db()->prepare("SELECT COUNT(*) FROM diesel_transactions dt 
            LEFT JOIN trucks t ON t.id = dt.truck_id 
            LEFT JOIN drivers d ON d.id = dt.driver_id 
            LEFT JOIN shipments s ON s.id = dt.shipment_id {$sql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = db()->prepare("SELECT dt.*, t.plate_number, t.truck_model, d.full_name AS driver_name, s.tracking_number, u.full_name AS recorded_by_name
            FROM diesel_transactions dt
            LEFT JOIN trucks t ON t.id = dt.truck_id
            LEFT JOIN drivers d ON d.id = dt.driver_id
            LEFT JOIN shipments s ON s.id = dt.shipment_id
            LEFT JOIN users u ON u.id = dt.recorded_by
            {$sql}
            ORDER BY dt.transaction_date DESC, dt.id DESC
            LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare("SELECT dt.*, t.plate_number, d.full_name AS driver_name, s.tracking_number
            FROM diesel_transactions dt
            LEFT JOIN trucks t ON t.id = dt.truck_id
            LEFT JOIN drivers d ON d.id = dt.driver_id
            LEFT JOIN shipments s ON s.id = dt.shipment_id
            WHERE dt.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function save(array $input, int $id = 0): int
    {
        $truckId = filter_var($input['truck_id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$truckId) {
            throw new DomainException('Please select a valid truck.');
        }

        $shipmentId = !empty($input['shipment_id']) ? (int) $input['shipment_id'] : null;
        $driverId = !empty($input['driver_id']) ? (int) $input['driver_id'] : null;

        $date = trim((string) ($input['transaction_date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new DomainException('Please enter a valid date (YYYY-MM-DD).');
        }

        $liters = filter_var($input['liters'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($liters === false || $liters <= 0) {
            throw new DomainException('Liters consumed must be greater than zero.');
        }

        $pricePerLiter = filter_var($input['price_per_liter'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($pricePerLiter === false || $pricePerLiter <= 0) {
            throw new DomainException('Price per liter must be greater than zero.');
        }

        $totalCost = round($liters * $pricePerLiter, 2);
        $tripDistance = !empty($input['trip_distance_km']) ? (float) $input['trip_distance_km'] : null;
        $notes = trim((string) ($input['notes'] ?? ''));

        $recordedBy = authUser()['id'] ?? 1;

        if ($id === 0) {
            $stmt = db()->prepare("INSERT INTO diesel_transactions 
                (shipment_id, truck_id, driver_id, transaction_date, liters, price_per_liter, total_cost, trip_distance_km, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$shipmentId, $truckId, $driverId, $date, $liters, $pricePerLiter, $totalCost, $tripDistance, $notes, $recordedBy]);
            $savedId = (int) db()->lastInsertId();
            logActivity('Create Fuel Log', 'Diesel', "Logged {$liters}L for Truck #{$truckId} on {$date}");
            return $savedId;
        }

        $stmt = db()->prepare("UPDATE diesel_transactions SET
            shipment_id = ?, truck_id = ?, driver_id = ?, transaction_date = ?, liters = ?, price_per_liter = ?, total_cost = ?, trip_distance_km = ?, notes = ?
            WHERE id = ?");
        $stmt->execute([$shipmentId, $truckId, $driverId, $date, $liters, $pricePerLiter, $totalCost, $tripDistance, $notes, $id]);
        logActivity('Update Fuel Log', 'Diesel', "Updated fuel log #{$id}");
        return $id;
    }

    public static function delete(int $id): void
    {
        db()->prepare("DELETE FROM diesel_transactions WHERE id = ?")->execute([$id]);
        logActivity('Delete Fuel Log', 'Diesel', "Deleted fuel log #{$id}");
    }

    public static function analytics(): array
    {
        $pdo = db();

        // Total consumption & cost
        $overall = $pdo->query("SELECT 
            COALESCE(SUM(liters), 0) AS total_liters,
            COALESCE(SUM(total_cost), 0) AS total_cost,
            COALESCE(AVG(liters), 0) AS avg_liters_per_trip,
            COUNT(*) AS total_transactions
            FROM diesel_transactions")->fetch();

        // Weekly consumption (past 8 weeks)
        $weekly = $pdo->query("SELECT 
            YEARWEEK(transaction_date, 1) AS yw,
            DATE_FORMAT(MIN(transaction_date), '%b %d') AS week_label,
            SUM(liters) AS total_liters,
            SUM(total_cost) AS total_cost
            FROM diesel_transactions
            GROUP BY yw
            ORDER BY yw DESC
            LIMIT 8")->fetchAll();

        // By Truck
        $byTruck = $pdo->query("SELECT 
            t.plate_number, t.truck_model,
            SUM(dt.liters) AS total_liters,
            SUM(dt.total_cost) AS total_cost,
            COUNT(dt.id) AS trips
            FROM diesel_transactions dt
            JOIN trucks t ON t.id = dt.truck_id
            GROUP BY dt.truck_id, t.plate_number, t.truck_model
            ORDER BY total_liters DESC
            LIMIT 10")->fetchAll();

        // Daily (last 7 days)
        $daily = $pdo->query("SELECT 
            transaction_date,
            SUM(liters) AS liters,
            SUM(total_cost) AS cost
            FROM diesel_transactions
            WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY transaction_date
            ORDER BY transaction_date ASC")->fetchAll();

        return [
            'overall' => $overall,
            'weekly' => array_reverse($weekly),
            'by_truck' => $byTruck,
            'daily' => $daily,
            'baseline' => self::BASELINE_LITERS,
        ];
    }
}

