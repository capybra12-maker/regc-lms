<?php

class DashboardController
{
    public function index(): void
    {
        requireAuth();
        $user = authUser();

        // If driver or warehouse, forward to their dedicated workspaces
        if ($user['role'] === 'driver') {
            header('Location: index.php?route=driver');
            exit;
        }
        if ($user['role'] === 'warehouse') {
            header('Location: index.php?route=warehouse');
            exit;
        }

        $pdo = db();
        $stats = [];

        $stats['total_shipments'] = (int) $pdo->query('SELECT COUNT(*) FROM shipments')->fetchColumn();
        $stats['pending'] = (int) $pdo->query("SELECT COUNT(*) FROM shipments WHERE current_status = 'pending'")->fetchColumn();
        $stats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM shipments WHERE current_status IN ('assigned','pickup','in_transit','arrived')")->fetchColumn();
        $stats['delivered'] = (int) $pdo->query("SELECT COUNT(*) FROM shipments WHERE current_status = 'delivered'")->fetchColumn();
        $stats['available_trucks'] = (int) $pdo->query("SELECT COUNT(*) FROM trucks WHERE status = 'available'")->fetchColumn();
        $stats['trucks_in_transit'] = (int) $pdo->query("SELECT COUNT(*) FROM trucks WHERE status = 'in_transit'")->fetchColumn();
        $stats['available_drivers'] = (int) $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'available'")->fetchColumn();

        // Diesel & Financial stats
        $stats['diesel_consumption'] = (float) $pdo->query("SELECT COALESCE(SUM(liters), 0) FROM diesel_transactions")->fetchColumn();
        $stats['weekly_deliveries'] = (int) $pdo->query("SELECT COUNT(*) FROM shipments WHERE current_status = 'delivered' AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['monthly_revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(freight_amount), 0) FROM shipments WHERE current_status = 'delivered' AND MONTH(updated_at) = MONTH(NOW()) AND YEAR(updated_at) = YEAR(NOW())")->fetchColumn();

        // Chart 1: Shipment status distribution
        $statusCounts = $pdo->query("SELECT current_status, COUNT(*) as cnt FROM shipments GROUP BY current_status")->fetchAll(PDO::FETCH_KEY_PAIR);
        $statuses = ['pending', 'assigned', 'pickup', 'in_transit', 'arrived', 'delivered'];
        $chartStatusData = [];
        foreach ($statuses as $st) {
            $chartStatusData[] = (int) ($statusCounts[$st] ?? 0);
        }

        // Chart 2: Weekly Delivery Chart (Monday to Sunday)
        $dayDeliveries = $pdo->query("SELECT DAYOFWEEK(updated_at) as dow, COUNT(*) as cnt 
            FROM shipments 
            WHERE current_status = 'delivered' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY dow")->fetchAll(PDO::FETCH_KEY_PAIR);
        // MySQL DAYOFWEEK: 1=Sun, 2=Mon, 3=Tue, 4=Wed, 5=Thu, 6=Fri, 7=Sat
        // Desired array: Mon, Tue, Wed, Thu, Fri, Sat, Sun
        $dowOrder = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun'];
        $chartWeeklyData = [];
        foreach ($dowOrder as $dowIndex => $dowLabel) {
            $chartWeeklyData[] = (int) ($dayDeliveries[$dowIndex] ?? 0);
        }

        // Chart 3: Diesel analytics (last 6 transactions or days)
        $dieselTimeline = $pdo->query("SELECT DATE_FORMAT(transaction_date, '%b %d') as dt_label, SUM(liters) as total_l, SUM(total_cost) as total_c 
            FROM diesel_transactions 
            GROUP BY transaction_date 
            ORDER BY transaction_date DESC 
            LIMIT 7")->fetchAll();
        $chartDieselLabels = [];
        $chartDieselLiters = [];
        foreach (array_reverse($dieselTimeline) as $dt) {
            $chartDieselLabels[] = $dt['dt_label'];
            $chartDieselLiters[] = (float) $dt['total_l'];
        }

        // Chart 4: Financial Overview (Total revenue vs diesel cost vs net)
        $totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(freight_amount), 0) FROM shipments WHERE current_status = 'delivered'")->fetchColumn();
        $totalDieselExpense = (float) $pdo->query("SELECT COALESCE(SUM(total_cost), 0) FROM diesel_transactions")->fetchColumn();
        $otherExpense = round($totalRevenue * 0.15, 2); // operational overhead
        $netProfit = max(0, $totalRevenue - ($totalDieselExpense + $otherExpense));

        // Recent shipments
        $recentShipments = $pdo
            ->query(
                'SELECT s.id, s.tracking_number, s.destination, s.current_status,
                        s.updated_at, s.cargo_type, s.freight_amount, c.contact_person, c.company_name
                 FROM shipments s
                 JOIN customers c ON c.id = s.customer_id
                 ORDER BY s.updated_at DESC, s.id DESC
                 LIMIT 6'
            )
            ->fetchAll();

        require __DIR__ . '/../views/dashboard.php';
    }
}
