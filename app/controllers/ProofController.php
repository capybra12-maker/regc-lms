<?php

require_once __DIR__ . '/../models/Proof.php';
require_once __DIR__ . '/../models/Operations.php';

class ProofController
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

    public function form(): void
    {
        requireAuth();
        $shipmentId = (int) ($_GET['shipment_id'] ?? 0);
        $shipment = Operations::shipmentDetail($shipmentId);

        if (!$shipment) {
            http_response_code(404);
            exit('Shipment not found.');
        }

        $existing = Proof::forShipment($shipmentId);
        $modalRequest = $this->isModalRequest();
        $error = null;
        $values = $existing ?? [
            'received_by' => '',
            'notes' => '',
        ];

        require __DIR__ . '/../views/proof/form.php';
    }

    public function upload(): void
    {
        requireAuth();
        verifyCsrf();

        $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
        $markDelivered = !empty($_POST['mark_delivered']);
        $error = null;

        try {
            Proof::save($shipmentId, $_POST, $_FILES);

            // If user checked "Mark as Delivered" and shipment is arrived
            if ($markDelivered) {
                $shipment = Operations::find('shipments', $shipmentId);
                if ($shipment && $shipment['current_status'] === 'arrived') {
                    Operations::advanceShipment($shipmentId, 'delivered', 'Delivery confirmed with proof of delivery documentation.');
                }
            }

            $_SESSION['notice'] = 'Proof of delivery submitted successfully.';

            $redirectUrl = hasRole('driver') ? 'index.php?route=driver' : (hasRole('warehouse') ? 'index.php?route=warehouse' : "index.php?route=shipments&action=show&id={$shipmentId}");

            if ($this->isModalRequest()) {
                $this->json(['redirect' => $redirectUrl]);
            }
            header('Location: ' . $redirectUrl);
            exit;
        } catch (DomainException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('POD upload error: ' . $e->getMessage());
            $error = 'Failed to upload proof of delivery. Please try again.';
        }

        http_response_code(422);
        $modalRequest = $this->isModalRequest();
        $shipment = Operations::shipmentDetail($shipmentId);
        $values = $_POST;
        require __DIR__ . '/../views/proof/form.php';
    }

    public function view(): void
    {
        requireAuth();
        $shipmentId = (int) ($_GET['shipment_id'] ?? 0);
        $shipment = Operations::shipmentDetail($shipmentId);
        $pod = Proof::forShipment($shipmentId);

        if (!$shipment || !$pod) {
            http_response_code(404);
            exit('Proof of delivery not found.');
        }

        $modalRequest = $this->isModalRequest();
        require __DIR__ . '/../views/proof/view.php';
    }
}

