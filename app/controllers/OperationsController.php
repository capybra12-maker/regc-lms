<?php

require_once __DIR__ . '/../models/Operations.php';

class OperationsController
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

    private function text(array $source, string $key, string $default = ''): string
    {
        return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : $default;
    }

    private function render(string $view, array $data): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../views/operations/' . $view . '.php';
    }

    private function redirect(string $module, array $params = []): never
    {
        $url = 'index.php?' . http_build_query(['route' => $module] + $params);
        if ($this->isModalRequest()) {
            $this->json(['redirect' => $url]);
        }
        header('Location: ' . $url);
        exit;
    }

    public function handle(string $module): void
    {
        if ($this->isModalRequest() && !isLoggedIn()) {
            $this->json(['message' => 'Your session has expired. Sign in again to continue.', 'login' => 'index.php?route=login'], 401);
        }
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            exit('Method not allowed.');
        }
        if ($module === 'tracking') {
            if ($method !== 'GET') {
                http_response_code(405);
                header('Allow: GET');
                exit('Method not allowed.');
            }
            $this->tracking();
            return;
        }

        $action = $this->text($method === 'POST' ? $_POST : $_GET, 'action', 'list');

        // Viewing a shipment is allowed for drivers and warehouse staff as well
        if ($module === 'shipments' && $action === 'show') {
            requireRole(['administrator', 'driver', 'warehouse']);
        } else {
            requireRole(['administrator']);
        }
        $config = Operations::catalogs()[$module] ?? ['title' => 'Shipments', 'singular' => 'Shipment', 'fields' => Operations::shipmentFields()];
        $idInput = ($method === 'POST' ? $_POST : $_GET)['id'] ?? '0';
        $id = is_string($idInput) ? filter_var($idInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) : false;
        if ($id === false) {
            http_response_code(400);
            exit('Invalid record ID.');
        }
        $error = null;
        $values = [];
        if ($method === 'POST') {
            verifyCsrf();
            try {
                if ($action === 'save') {
                    $saved = $module === 'shipments'
                        ? Operations::saveShipment($_POST, $id)
                        : Operations::saveCatalog($module, $_POST, $id);
                    $_SESSION['notice'] = $config['singular'] . ' saved.';
                    $this->redirect($module, $module === 'shipments' ? ['action' => 'show', 'id' => $saved] : []);
                } elseif ($action === 'delete' && $id) {
                    Operations::delete($module, $id);
                    $_SESSION['notice'] = $config['singular'] . ' deleted.';
                    $this->redirect($module);
                } elseif ($action === 'status' && $module === 'shipments' && $id) {
                    Operations::advanceShipment($id, $this->text($_POST, 'status'), $this->text($_POST, 'remarks'));
                    $_SESSION['notice'] = 'Shipment status updated.';
                    $this->redirect($module, ['action' => 'show', 'id' => $id]);
                } else {
                    throw new DomainException('Unknown action.');
                }
            } catch (DomainException $exception) {
                $error = $exception->getMessage();
            } catch (PDOException $exception) {
                error_log('Operations database error: ' . $exception->getMessage());
                $error = match ((int) ($exception->errorInfo[1] ?? 0)) {
                    1062 => 'That plate number, license number, or tracking number already exists. Please use a unique value.',
                    1451 => 'This record is still in use and cannot be deleted.',
                    1205, 1213 => 'Another update is in progress. Please try saving again.',
                    default => 'The change could not be saved. Please try again.',
                };
            }
            if ($action === 'save') {
                $action = 'edit';
                $values = array_filter($_POST, 'is_string');
                http_response_code(422);
            } else {
                if ($this->isModalRequest()) {
                    $this->json(['message' => $error], 422);
                }
                $_SESSION['operation_error'] = $error;
                $this->redirect($module, $action === 'status' ? ['action' => 'show', 'id' => $id] : []);
            }
        }
        if (in_array($action, ['new', 'edit'], true)) {
            $record = $id ? Operations::find($module, $id) : null;
            if ($id && !$record) {
                $this->notFound();
            }
            if ($module === 'shipments' && $record && !in_array($record['current_status'], ['pending', 'assigned'], true)) {
                $_SESSION['operation_error'] = 'Shipment details cannot be edited after pickup.';
                $this->redirect($module, ['action' => 'show', 'id' => $id]);
            }
            if (!$values) {
                $values = $record ?? [];
            }
            $fields = $config['fields'];
            $lockedStatus = $record && $module !== 'shipments' && in_array($record['status'], ['assigned', 'in_transit', 'on_delivery'], true);
            if ($lockedStatus) {
                $fields['status'][3][] = $record['status'];
                $values['status'] = $record['status'];
            }
            $choices = $module === 'shipments' ? [
                'customer' => Operations::choices('customers'),
                'truck' => Operations::choices('trucks'),
                'driver' => Operations::choices('drivers'),
            ] : [];
            $modalRequest = $this->isModalRequest();
            $this->render('form', compact('module', 'config', 'id', 'values', 'fields', 'choices', 'error', 'lockedStatus', 'modalRequest'));
            return;
        }
        if ($action === 'status-form' && $module === 'shipments') {
            $shipment = Operations::shipmentDetail($id);
            if (!$shipment) {
                $this->notFound();
            }
            $modalRequest = $this->isModalRequest();
            $this->render('status-form', compact('module', 'shipment', 'modalRequest'));
            return;
        }
        if ($action === 'show' && $module === 'shipments') {
            $shipment = Operations::shipmentDetail($id);
            if (!$shipment) {
                $this->notFound();
            }
            $history = Operations::timeline($id);
            $this->render('shipment', compact('module', 'shipment', 'history'));
            return;
        }
        if ($action === 'email-preview' && $module === 'shipments' && $id) {
            require_once __DIR__ . '/../services/Mailer.php';
            $shipment = Operations::shipmentDetail($id);
            if (!$shipment) {
                $this->notFound();
            }
            $customer = Operations::find('customers', (int) $shipment['customer_id']);
            header('Content-Type: text/html; charset=utf-8');
            echo Mailer::buildTrackingEmailHtml($shipment, $customer ?: [], $shipment['tracking_number']);
            exit;
        }
        if ($action === 'email-tracking' && $module === 'shipments' && $id) {
            require_once __DIR__ . '/../services/Mailer.php';
            $res = Mailer::sendTrackingEmail($id);
            if ($res['ok']) {
                $_SESSION['notice'] = $res['message'];
            } else {
                $_SESSION['operation_error'] = $res['message'];
            }
            $this->redirect('shipments', ['action' => 'show', 'id' => $id]);
        }
        if ($action === 'sms-tracking' && $module === 'shipments' && $id) {
            require_once __DIR__ . '/../services/SMS.php';
            $res = SMS::sendTrackingNotification($id);
            if ($res['ok']) {
                $_SESSION['notice'] = $res['message'];
            } else {
                $_SESSION['operation_error'] = $res['message'];
            }
            $this->redirect('shipments', ['action' => 'show', 'id' => $id]);
        }
        if ($action !== 'list') {
            $this->notFound();
        }
        $search = mb_substr($this->text($_GET, 'q'), 0, 190);
        $status = $this->text($_GET, 'status');
        $listing = Operations::listing($module, $search, $status, max(1, (int) $this->text($_GET, 'page', '1')));
        $this->render('listing', compact('module', 'config', 'search', 'status', 'listing'));
    }

    private function notFound(): never
    {
        http_response_code(404);
        exit('<h1>Record not found</h1><p><a href="index.php?route=dashboard">Return to dashboard</a></p>');
    }

    private function tracking(): void
    {
        $module = 'tracking';
        $number = strtoupper(mb_substr($this->text($_GET, 'number'), 0, 60));
        $shipment = null;
        $history = [];
        if ($number !== '') {
            $stmt = db()->prepare('SELECT id FROM shipments WHERE tracking_number = ? LIMIT 1');
            $stmt->execute([$number]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $shipment = Operations::shipmentDetail((int) $id);
                $history = Operations::timeline((int) $id);
            }
        }
        $this->render('tracking', compact('module', 'number', 'shipment', 'history'));
    }
}
