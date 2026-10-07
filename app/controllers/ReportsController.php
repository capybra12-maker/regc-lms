<?php

require_once __DIR__ . '/../models/Operations.php';
require_once __DIR__ . '/../models/Diesel.php';

class ReportsController
{
    public function index(): void
    {
        requireRole(['administrator']);

        $tab = trim((string) ($_GET['tab'] ?? 'shipments'));
        $startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-01')));
        $endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d')));
        $driverId = trim((string) ($_GET['driver_id'] ?? ''));
        $truckId = trim((string) ($_GET['truck_id'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        $pdo = db();
        $reportData = [];

        // 1. Shipment Report
        if ($tab === 'shipments') {
            $where = ['s.created_at BETWEEN ? AND ?'];
            $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
            if ($status !== '') {
                $where[] = 's.current_status = ?';
                $params[] = $status;
            }
            if ($truckId !== '') {
                $where[] = 's.truck_id = ?';
                $params[] = (int) $truckId;
            }
            if ($driverId !== '') {
                $where[] = 's.driver_id = ?';
                $params[] = (int) $driverId;
            }
            $sql = ' WHERE ' . implode(' AND ', $where);
            $stmt = $pdo->prepare("SELECT s.*, c.contact_person, c.company_name, t.plate_number, d.full_name AS driver_name
                FROM shipments s
                JOIN customers c ON c.id = s.customer_id
                LEFT JOIN trucks t ON t.id = s.truck_id
                LEFT JOIN drivers d ON d.id = s.driver_id
                {$sql}
                ORDER BY s.id DESC");
            $stmt->execute($params);
            $reportData['rows'] = $stmt->fetchAll();

            // Status summary
            $sumStmt = $pdo->prepare("SELECT current_status, COUNT(*) as cnt, COALESCE(SUM(cargo_weight_kg), 0) as weight, COALESCE(SUM(freight_amount), 0) as freight
                FROM shipments s {$sql} GROUP BY current_status");
            $sumStmt->execute($params);
            $reportData['summary'] = $sumStmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
        }

        // 2. Customer Report
        elseif ($tab === 'customers') {
            $reportData['rows'] = $pdo->query("SELECT 
                c.id, c.company_name, c.contact_person, c.contact_number, c.email, c.status,
                COUNT(s.id) AS total_shipments,
                COALESCE(SUM(s.cargo_weight_kg), 0) AS total_cargo_kg,
                COALESCE(SUM(s.freight_amount), 0) AS total_revenue,
                SUM(CASE WHEN s.current_status = 'delivered' THEN 1 ELSE 0 END) AS completed_deliveries
                FROM customers c
                LEFT JOIN shipments s ON s.customer_id = c.id
                GROUP BY c.id
                ORDER BY total_revenue DESC")->fetchAll();
        }

        // 3. Revenue / Expense Report
        elseif ($tab === 'financial') {
            $reportData['revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(freight_amount), 0) FROM shipments WHERE current_status = 'delivered'")->fetchColumn();
            $reportData['diesel_cost'] = (float) $pdo->query("SELECT COALESCE(SUM(total_cost), 0) FROM diesel_transactions")->fetchColumn();
            $reportData['other_expense'] = round($reportData['revenue'] * 0.12, 2);
            $reportData['net_amount'] = max(0, $reportData['revenue'] - ($reportData['diesel_cost'] + $reportData['other_expense']));

            // Monthly breakdown
            $reportData['monthly'] = $pdo->query("SELECT 
                DATE_FORMAT(s.updated_at, '%b %Y') AS month_label,
                YEAR(s.updated_at) AS y, MONTH(s.updated_at) AS m,
                COALESCE(SUM(s.freight_amount), 0) AS monthly_rev,
                COUNT(s.id) AS shipments_count
                FROM shipments s
                WHERE s.current_status = 'delivered'
                GROUP BY y, m, month_label
                ORDER BY y DESC, m DESC
                LIMIT 12")->fetchAll();
        }

        // 4. Diesel Report
        elseif ($tab === 'diesel') {
            $reportData['analytics'] = Diesel::analytics();
            $reportData['rows'] = $pdo->query("SELECT dt.*, t.plate_number, t.truck_model, d.full_name AS driver_name, s.tracking_number
                FROM diesel_transactions dt
                JOIN trucks t ON t.id = dt.truck_id
                LEFT JOIN drivers d ON d.id = dt.driver_id
                LEFT JOIN shipments s ON s.id = dt.shipment_id
                ORDER BY dt.transaction_date DESC
                LIMIT 100")->fetchAll();
        }

        // 5. Driver Report
        elseif ($tab === 'drivers') {
            $reportData['rows'] = $pdo->query("SELECT 
                d.id, d.full_name, d.license_number, d.contact_number, d.status,
                COUNT(s.id) AS total_assigned,
                SUM(CASE WHEN s.current_status = 'delivered' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN s.current_status IN ('assigned','pickup','in_transit','arrived') THEN 1 ELSE 0 END) AS active_jobs
                FROM drivers d
                LEFT JOIN shipments s ON s.driver_id = d.id
                GROUP BY d.id
                ORDER BY completed DESC")->fetchAll();
        }

        // 6. Truck Report
        elseif ($tab === 'trucks') {
            $reportData['rows'] = $pdo->query("SELECT 
                t.id, t.plate_number, t.truck_model, t.truck_type, t.capacity_kg, t.status,
                COUNT(DISTINCT s.id) AS total_trips,
                SUM(CASE WHEN s.current_status = 'delivered' THEN 1 ELSE 0 END) AS delivered_trips,
                COALESCE(SUM(dt.liters), 0) AS total_diesel_liters,
                COALESCE(SUM(dt.total_cost), 0) AS total_diesel_cost,
                COALESCE(SUM(dt.trip_distance_km), 0) AS total_distance_km
                FROM trucks t
                LEFT JOIN shipments s ON s.truck_id = t.id
                LEFT JOIN diesel_transactions dt ON dt.truck_id = t.id
                GROUP BY t.id
                ORDER BY total_trips DESC")->fetchAll();
        }

        $trucks = Operations::choices('trucks');
        $drivers = Operations::choices('drivers');
        $module = 'reports';

        require __DIR__ . '/../views/reports/index.php';
    }
}

