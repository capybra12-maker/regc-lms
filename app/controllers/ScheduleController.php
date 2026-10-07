<?php

require_once __DIR__ . '/../models/Schedule.php';
require_once __DIR__ . '/../models/Operations.php';

class ScheduleController
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
        requireRole(['administrator', 'driver', 'warehouse']);

        $startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-d')));
        $endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d', strtotime('+14 days'))));

        $schedules = Schedule::listing($startDate, $endDate);
        $module = 'schedule';

        require __DIR__ . '/../views/schedule/index.php';
    }

    public function form(): void
    {
        requireRole(['administrator']);

        $id = (int) ($_GET['id'] ?? 0);
        $schedule = null;
        if ($id) {
            $stmt = db()->prepare('SELECT * FROM delivery_schedules WHERE id = ?');
            $stmt->execute([$id]);
            $schedule = $stmt->fetch();
        }

        $trucks = Operations::choices('trucks');
        $drivers = Operations::choices('drivers');
        $shipments = db()->query("SELECT id, tracking_number, cargo_type, pickup_location, destination FROM shipments WHERE current_status <> 'delivered' ORDER BY id DESC")->fetchAll();

        $modalRequest = $this->isModalRequest();
        $error = null;
        $values = $schedule ?? [
            'shipment_id' => (int) ($_GET['shipment_id'] ?? 0),
            'truck_id' => '',
            'driver_id' => '',
            'pickup_datetime' => date('Y-m-d\TH:i'),
            'expected_delivery_datetime' => date('Y-m-d\TH:i', strtotime('+1 day')),
            'notes' => '',
        ];

        require __DIR__ . '/../views/schedule/form.php';
    }

    public function save(): void
    {
        requireRole(['administrator']);
        verifyCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $error = null;

        try {
            Schedule::save($_POST, $id);
            $_SESSION['notice'] = $id ? 'Schedule updated successfully.' : 'Delivery scheduled successfully.';

            if ($this->isModalRequest()) {
                $this->json(['redirect' => 'index.php?route=schedule']);
            }
            header('Location: index.php?route=schedule');
            exit;
        } catch (DomainException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Schedule save error: ' . $e->getMessage());
            $error = 'Failed to save delivery schedule.';
        }

        http_response_code(422);
        $modalRequest = $this->isModalRequest();
        $trucks = Operations::choices('trucks');
        $drivers = Operations::choices('drivers');
        $shipments = db()->query("SELECT id, tracking_number, cargo_type, pickup_location, destination FROM shipments WHERE current_status <> 'delivered' ORDER BY id DESC")->fetchAll();
        $values = $_POST;
        require __DIR__ . '/../views/schedule/form.php';
    }
}

