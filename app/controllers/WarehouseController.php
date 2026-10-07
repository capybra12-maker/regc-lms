<?php

require_once __DIR__ . '/../models/Operations.php';
require_once __DIR__ . '/../models/Proof.php';

class WarehouseController
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
        requireRole(['warehouse', 'administrator']);

        $pdo = db();
        // Incoming shipments: in_transit
        $incoming = $pdo->query("SELECT s.*, c.contact_person, c.company_name, t.plate_number, d.full_name AS driver_name, d.contact_number AS driver_phone
            FROM shipments s
            JOIN customers c ON c.id = s.customer_id
            LEFT JOIN trucks t ON t.id = s.truck_id
            LEFT JOIN drivers d ON d.id = s.driver_id
            WHERE s.current_status = 'in_transit'
            ORDER BY s.expected_delivery_date ASC, s.id DESC")->fetchAll();

        // Arrived awaiting receiving documentation / confirmation: arrived
        $arrived = $pdo->query("SELECT s.*, c.contact_person, c.company_name, t.plate_number, d.full_name AS driver_name, d.contact_number AS driver_phone
            FROM shipments s
            JOIN customers c ON c.id = s.customer_id
            LEFT JOIN trucks t ON t.id = s.truck_id
            LEFT JOIN drivers d ON d.id = s.driver_id
            WHERE s.current_status = 'arrived'
            ORDER BY s.updated_at DESC")->fetchAll();

        // Recently received: delivered
        $recentlyDelivered = $pdo->query("SELECT s.*, c.contact_person, c.company_name, t.plate_number, d.full_name AS driver_name
            FROM shipments s
            JOIN customers c ON c.id = s.customer_id
            LEFT JOIN trucks t ON t.id = s.truck_id
            LEFT JOIN drivers d ON d.id = s.driver_id
            WHERE s.current_status = 'delivered'
            ORDER BY s.updated_at DESC
            LIMIT 10")->fetchAll();

        $module = 'warehouse';
        require __DIR__ . '/../views/warehouse-dashboard.php';
    }

    public function confirmArrival(): void
    {
        requireRole(['warehouse', 'administrator']);
        verifyCsrf();

        $shipmentId = (int) ($_POST['id'] ?? 0);
        $remarks = trim((string) ($_POST['remarks'] ?? 'Warehouse staff confirmed cargo arrival at facility.'));

        try {
            Operations::advanceShipment($shipmentId, 'arrived', $remarks);
            $_SESSION['notice'] = 'Cargo arrival confirmed successfully.';

            if ($this->isModalRequest()) {
                $this->json(['redirect' => 'index.php?route=warehouse']);
            }
            header('Location: index.php?route=warehouse');
            exit;
        } catch (DomainException $e) {
            if ($this->isModalRequest()) {
                $this->json(['message' => $e->getMessage()], 422);
            }
            $_SESSION['operation_error'] = $e->getMessage();
            header('Location: index.php?route=warehouse');
            exit;
        }
    }
}

