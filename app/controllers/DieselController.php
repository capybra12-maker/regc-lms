<?php

require_once __DIR__ . '/../models/Diesel.php';
require_once __DIR__ . '/../models/Operations.php';

class DieselController
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
        requireRole(['administrator']);

        $search = trim((string) ($_GET['q'] ?? ''));
        $truckId = trim((string) ($_GET['truck_id'] ?? ''));
        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $listing = Diesel::listing($search, $truckId, $startDate, $endDate, $page);
        $analytics = Diesel::analytics();
        $trucks = Operations::choices('trucks');
        $module = 'diesel';

        require __DIR__ . '/../views/diesel/listing.php';
    }

    public function form(): void
    {
        requireRole(['administrator']);

        $id = (int) ($_GET['id'] ?? 0);
        $record = $id ? Diesel::find($id) : null;

        if ($id && !$record) {
            http_response_code(404);
            exit('Fuel log record not found.');
        }

        $trucks = Operations::choices('trucks');
        $drivers = Operations::choices('drivers');
        $shipments = db()->query("SELECT id, tracking_number, cargo_type FROM shipments ORDER BY id DESC LIMIT 50")->fetchAll();

        $modalRequest = $this->isModalRequest();
        $error = null;
        $values = $record ?? [
            'truck_id' => '',
            'driver_id' => '',
            'shipment_id' => '',
            'transaction_date' => date('Y-m-d'),
            'liters' => '',
            'price_per_liter' => '65.00',
            'trip_distance_km' => '',
            'notes' => '',
        ];

        require __DIR__ . '/../views/diesel/form.php';
    }

    public function save(): void
    {
        requireRole(['administrator']);
        verifyCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $error = null;

        try {
            Diesel::save($_POST, $id);
            $_SESSION['notice'] = $id ? 'Diesel record updated.' : 'Diesel consumption record saved.';

            if ($this->isModalRequest()) {
                $this->json(['redirect' => 'index.php?route=diesel']);
            }
            header('Location: index.php?route=diesel');
            exit;
        } catch (DomainException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Diesel save error: ' . $e->getMessage());
            $error = 'Failed to save diesel record.';
        }

        http_response_code(422);
        $modalRequest = $this->isModalRequest();
        $trucks = Operations::choices('trucks');
        $drivers = Operations::choices('drivers');
        $shipments = db()->query("SELECT id, tracking_number, cargo_type FROM shipments ORDER BY id DESC LIMIT 50")->fetchAll();
        $values = $_POST;
        require __DIR__ . '/../views/diesel/form.php';
    }

    public function delete(): void
    {
        requireRole(['administrator']);
        verifyCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        try {
            Diesel::delete($id);
            $_SESSION['notice'] = 'Diesel log record deleted.';
        } catch (DomainException $e) {
            $_SESSION['operation_error'] = $e->getMessage();
        }

        if ($this->isModalRequest()) {
            $this->json(['redirect' => 'index.php?route=diesel']);
        }
        header('Location: index.php?route=diesel');
        exit;
    }
}

