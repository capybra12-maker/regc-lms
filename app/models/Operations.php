<?php

/** Field definitions are shared by server validation and the edit forms. */
class Operations
{
    public const STATUSES = ['pending', 'assigned', 'pickup', 'in_transit', 'arrived', 'delivered'];

    public static function catalogs(): array
    {
        return [
            'customers' => ['title' => 'Customers', 'singular' => 'Customer', 'label' => 'contact_person', 'fields' => [
                'company_name' => ['Company name', 'text', false, 180],
                'contact_person' => ['Contact person', 'text', true, 150],
                'contact_number' => ['Contact number', 'text', false, 50],
                'email' => ['Email address', 'email', false, 190],
                'address' => ['Address', 'textarea', false, 5000],
                'status' => ['Status', 'select', true, ['active', 'inactive']],
            ]],
            'trucks' => ['title' => 'Trucks', 'singular' => 'Truck', 'label' => 'plate_number', 'fields' => [
                'plate_number' => ['Plate number', 'text', true, 50],
                'truck_model' => ['Truck model', 'text', false, 150],
                'truck_type' => ['Truck type', 'text', false, 100],
                'capacity_kg' => ['Capacity (kg)', 'number', true, 9999999999.99],
                'registration_number' => ['Registration number', 'text', false, 100],
                'registration_expiration' => ['Registration expiration', 'date', false, null],
                'notes' => ['Notes', 'textarea', false, 5000],
                'status' => ['Status', 'select', true, ['available', 'maintenance', 'unavailable']],
            ]],
            'drivers' => ['title' => 'Drivers', 'singular' => 'Driver', 'label' => 'full_name', 'fields' => [
                'full_name' => ['Full name', 'text', true, 150],
                'contact_number' => ['Contact number', 'text', false, 50],
                'license_number' => ['License number', 'text', true, 100],
                'license_expiration' => ['License expiration', 'date', false, null],
                'emergency_contact' => ['Emergency contact', 'text', false, 150],
                'emergency_contact_number' => ['Emergency contact number', 'text', false, 50],
                'status' => ['Status', 'select', true, ['available', 'off_duty']],
            ]],
        ];
    }

    public static function shipmentFields(): array
    {
        return [
            'customer_id' => ['Customer', 'customer', true, null],
            'cargo_type' => ['Cargo type', 'text', true, 100],
            'cargo_description' => ['Cargo description', 'textarea', false, 5000],
            'cargo_weight_kg' => ['Cargo weight (kg)', 'number', true, 9999999999.99],
            'pickup_location' => ['Pickup location', 'text', true, 255],
            'destination' => ['Destination', 'text', true, 255],
            'shipment_date' => ['Pickup date and time', 'datetime-local', false, null],
            'expected_delivery_date' => ['Expected delivery', 'datetime-local', false, null],
            'freight_amount' => ['Freight amount (₱)', 'number', false, 9999999999.99],
            'truck_id' => ['Truck', 'truck', false, null],
            'driver_id' => ['Driver', 'driver', false, null],
            'notes' => ['Notes', 'textarea', false, 5000],
        ];
    }

    public static function validate(array $fields, array $input): array
    {
        $data = [];
        foreach ($fields as $key => [$label, $type, $required, $limit]) {
            $value = $input[$key] ?? '';
            if (!is_scalar($value)) {
                throw new DomainException("Invalid {$label}.");
            }
            $value = trim((string) $value);
            if ($value === '') {
                if ($required) {
                    throw new DomainException("{$label} is required.");
                }
                $data[$key] = null;
                continue;
            }
            if (in_array($type, ['text', 'textarea', 'email'], true) && mb_strlen($value) > $limit) {
                throw new DomainException("{$label} must be {$limit} characters or fewer.");
            }
            if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new DomainException('Enter a valid email address.');
            }
            if ($type === 'select' && !in_array($value, $limit, true)) {
                throw new DomainException("Choose a valid {$label}.");
            }
            if ($type === 'number' && (!preg_match('/^\d+(\.\d{1,2})?$/D', $value) || (float) $value <= 0 || (float) $value > $limit)) {
                throw new DomainException("{$label} must be greater than zero, with at most two decimal places.");
            }
            if (in_array($type, ['customer', 'truck', 'driver'], true) && !filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                throw new DomainException("Choose a valid {$label}.");
            }
            if (in_array($type, ['date', 'datetime-local'], true)) {
                $format = $type === 'date' ? 'Y-m-d' : 'Y-m-d\TH:i';
                $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
                if (!$date || $date->format($format) !== $value || (int) $date->format('Y') < 1000) {
                    throw new DomainException("Enter a valid {$label}.");
                }
                $value = $date->format($type === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s');
            }
            $data[$key] = $value;
        }
        return $data;
    }

    private static function table(string $table): void
    {
        if (!isset(self::catalogs()[$table]) && $table !== 'shipments') {
            throw new InvalidArgumentException('Unknown module.');
        }
    }

    public static function find(string $table, int $id, bool $lock = false): ?array
    {
        self::table($table);
        $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = ?" . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function listing(string $table, string $search, string $status, int $page): array
    {
        self::table($table);
        $columns = match ($table) {
            'customers' => ['contact_person', 'company_name', 'email', 'contact_number'],
            'trucks' => ['plate_number', 'truck_model', 'truck_type'],
            'drivers' => ['full_name', 'license_number', 'contact_number'],
            'shipments' => ['tracking_number', 'cargo_type', 'pickup_location', 'destination'],
        };
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $columns)) . ')';
            $params = array_fill(0, count($columns), '%' . $search . '%');
        }
        if ($status !== '') {
            $where[] = ($table === 'shipments' ? 'current_status' : 'status') . ' = ?';
            $params[] = $status;
        }
        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $stmt = db()->prepare("SELECT COUNT(*) FROM {$table}{$sql}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $pages = max(1, (int) ceil($total / 20));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * 20;
        $stmt = db()->prepare("SELECT * FROM {$table}{$sql} ORDER BY id DESC LIMIT 20 OFFSET {$offset}");
        $stmt->execute($params);
        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    private static function transaction(callable $action): mixed
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $result = $action();
            $pdo->commit();
            return $result;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private static function write(string $table, array $data, int $id): int
    {
        if ($id) {
            $set = implode(', ', array_map(fn($key) => "$key = ?", array_keys($data)));
            db()->prepare("UPDATE {$table} SET {$set} WHERE id = ?")->execute([...array_values($data), $id]);
            return $id;
        }
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        db()->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})")->execute(array_values($data));
        return (int) db()->lastInsertId();
    }

    public static function saveCatalog(string $table, array $input, int $id): int
    {
        $config = self::catalogs()[$table] ?? throw new InvalidArgumentException('Unknown module.');
        return self::transaction(function () use ($table, $config, $input, $id) {
            $old = $id ? self::find($table, $id, true) : null;
            if ($id && !$old) {
                throw new DomainException('This record no longer exists.');
            }
            $fields = $config['fields'];
            // Operational statuses are controlled by shipment assignment, not manual edits.
            if ($old && in_array($old['status'], ['assigned', 'in_transit', 'on_delivery'], true)) {
                $fields['status'][3][] = $old['status'];
                $input['status'] = $old['status'];
            }
            $data = self::validate($fields, $input);
            $saved = self::write($table, $data, $id);
            logActivity($id ? 'Update' : 'Create', $config['title'], $config['singular'] . ' #' . $saved);
            return $saved;
        });
    }

    public static function delete(string $table, int $id): void
    {
        self::table($table);
        self::transaction(function () use ($table, $id) {
            $record = self::find($table, $id, true);
            if (!$record) {
                throw new DomainException('This record no longer exists.');
            }
            if ($table === 'shipments') {
                if ($record['current_status'] !== 'pending' || $record['truck_id'] || $record['driver_id']) {
                    throw new DomainException('Only unassigned pending shipments can be deleted.');
                }
            } else {
                $column = ['customers' => 'customer_id', 'trucks' => 'truck_id', 'drivers' => 'driver_id'][$table];
                $stmt = db()->prepare("SELECT id FROM shipments WHERE {$column} = ? LIMIT 1");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    throw new DomainException('This record is used by a shipment and must be kept for its history.');
                }
            }
            db()->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
            logActivity('Delete', ucfirst($table), 'Record #' . $id);
        });
    }

    public static function choices(string $table): array
    {
        $config = self::catalogs()[$table] ?? throw new InvalidArgumentException('Unknown module.');
        return db()->query("SELECT * FROM {$table} ORDER BY {$config['label']}, id")->fetchAll();
    }

    private static function history(int $id, string $status, string $remarks): void
    {
        db()->prepare('INSERT INTO shipment_status_history (shipment_id, status, updated_by, remarks) VALUES (?, ?, ?, ?)')
            ->execute([$id, $status, authUser()['id'], $remarks]);
    }

    private static function resourceStatus(string $table, int $id, string $status): void
    {
        db()->prepare("UPDATE {$table} SET status = ? WHERE id = ?")->execute([$status, $id]);
    }

    public static function saveShipment(array $input, int $id): int
    {
        $data = self::validate(self::shipmentFields(), $input);
        if ((bool) $data['truck_id'] !== (bool) $data['driver_id']) {
            throw new DomainException('Choose both a truck and a driver, or leave both unassigned.');
        }
        if ($data['shipment_date'] && $data['expected_delivery_date'] && $data['expected_delivery_date'] < $data['shipment_date']) {
            throw new DomainException('Expected delivery cannot be before pickup.');
        }

        // Capture optional GPS coordinates from Maps autocomplete
        $parseCoord = function (string $key, float $min, float $max) use ($input): ?float {
            $v = isset($input[$key]) ? trim((string) $input[$key]) : '';
            if ($v === '') return null;
            $f = filter_var($v, FILTER_VALIDATE_FLOAT);
            return ($f !== false && $f >= $min && $f <= $max) ? $f : null;
        };
        $data['pickup_lat']      = $parseCoord('pickup_lat', -90, 90);
        $data['pickup_lng']      = $parseCoord('pickup_lng', -180, 180);
        $data['destination_lat'] = $parseCoord('destination_lat', -90, 90);
        $data['destination_lng'] = $parseCoord('destination_lng', -180, 180);

        return self::transaction(function () use ($data, $id) {
            $old = $id ? self::find('shipments', $id, true) : null;
            if ($id && !$old) {
                throw new DomainException('This shipment no longer exists.');
            }
            if ($old && !in_array($old['current_status'], ['pending', 'assigned'], true)) {
                throw new DomainException('Shipment details cannot be edited after pickup.');
            }
            $customer = self::find('customers', (int) $data['customer_id'], true);
            if (!$customer || ($customer['status'] !== 'active' && (!$old || $old['customer_id'] != $data['customer_id']))) {
                throw new DomainException('Choose an active customer.');
            }
            foreach (['trucks' => 'truck_id', 'drivers' => 'driver_id'] as $table => $key) {
                // Lock old and new resources in a stable order to serialize assignments.
                $ids = array_unique(array_filter([(int) ($old[$key] ?? 0), (int) $data[$key]]));
                sort($ids);
                $resources = [];
                foreach ($ids as $resourceId) {
                    $resources[$resourceId] = self::find($table, $resourceId, true);
                }
                if ($data[$key]) {
                    $resource = $resources[(int) $data[$key]];
                    $unchanged = $old && $old[$key] == $data[$key];
                    if (!$resource || (!$unchanged && $resource['status'] !== 'available')) {
                        throw new DomainException('The selected ' . ($table === 'trucks' ? 'truck' : 'driver') . ' is no longer available.');
                    }
                    $stmt = db()->prepare("SELECT id FROM shipments WHERE {$key} = ? AND id <> ? AND current_status <> 'delivered' LIMIT 1");
                    $stmt->execute([$data[$key], $id]);
                    if ($stmt->fetchColumn()) {
                        throw new DomainException('The selected ' . ($table === 'trucks' ? 'truck' : 'driver') . ' already has an active shipment.');
                    }
                    $expiry = $resource[$table === 'trucks' ? 'registration_expiration' : 'license_expiration'];
                    $serviceDate = substr($data['shipment_date'] ?: date('Y-m-d'), 0, 10);
                    if ($expiry && $expiry < max(date('Y-m-d'), $serviceDate)) {
                        throw new DomainException($table === 'trucks' ? 'The truck registration is expired for the pickup date.' : 'The driver license is expired for the pickup date.');
                    }
                }
                if (!empty($old[$key]) && $old[$key] != $data[$key]) {
                    self::resourceStatus($table, (int) $old[$key], 'available');
                }
                if ($data[$key]) {
                    self::resourceStatus($table, (int) $data[$key], 'assigned');
                }
            }
            $data['current_status'] = $data['truck_id'] ? 'assigned' : 'pending';
            if (!$id) {
                $data['created_by'] = authUser()['id'];
                $year = date('Y');
                $stmt = db()->prepare("SELECT COUNT(*) FROM shipments WHERE tracking_number LIKE ?");
                $stmt->execute(["REGC-{$year}-%"]);
                $seq = (int) $stmt->fetchColumn() + 1;
                $data['tracking_number'] = sprintf('REGC-%s-%06d', $year, $seq);
            }
            $saved = self::write('shipments', $data, $id);
            $trackingNumber = $old ? $old['tracking_number'] : $data['tracking_number'];
            $assignment = $data['truck_id'] ? ' Truck #' . $data['truck_id'] . ', driver #' . $data['driver_id'] . '.' : ' Unassigned.';
            self::history($saved, $data['current_status'], ($id ? 'Shipment details updated.' : 'Shipment created.') . $assignment);
            logActivity($id ? 'Update' : 'Create', 'Shipments', 'Shipment #' . $saved);

            if (!$id) {
                createNotification(null, 'administrator', 'New Shipment Logged', "Shipment {$data['tracking_number']} created ({$data['cargo_type']})", 'info', "index.php?route=shipments&action=show&id={$saved}");
                // Auto-send tracking email
                try {
                    require_once __DIR__ . '/../services/Mailer.php';
                    Mailer::sendTrackingEmail($saved);
                } catch (Throwable $e) {
                    error_log('Auto tracking email notice: ' . $e->getMessage());
                }
                // Auto-send tracking SMS
                try {
                    require_once __DIR__ . '/../services/SMS.php';
                    SMS::sendTrackingNotification($saved);
                } catch (Throwable $e) {
                    error_log('Auto tracking SMS notice: ' . $e->getMessage());
                }
            }
            if ($data['driver_id']) {
                $driverUser = db()->prepare('SELECT user_id FROM drivers WHERE id = ?');
                $driverUser->execute([$data['driver_id']]);
                $dUid = $driverUser->fetchColumn();
                if ($dUid) {
                    createNotification((int) $dUid, 'driver', 'Delivery Assigned', "You have been assigned to delivery {$trackingNumber}.", 'info', "index.php?route=driver");
                }
            }

            return $saved;
        });
    }

    public static function advanceShipment(int $id, string $next, string $remarks): void
    {
        if (mb_strlen($remarks) > 5000) {
            throw new DomainException('Remarks must be 5000 characters or fewer.');
        }
        self::transaction(function () use ($id, $next, $remarks) {
            $shipment = self::find('shipments', $id, true);
            if (!$shipment) {
                throw new DomainException('Shipment not found.');
            }
            $current = array_search($shipment['current_status'], self::STATUSES, true);
            if ($shipment['current_status'] === 'pending' || $current === false || (self::STATUSES[$current + 1] ?? null) !== $next) {
                throw new DomainException('Invalid status change. Refresh the shipment and follow the delivery steps in order.');
            }
            if (!$shipment['truck_id'] || !$shipment['driver_id']) {
                throw new DomainException('Assign a truck and driver before starting pickup.');
            }
            self::find('trucks', (int) $shipment['truck_id'], true);
            self::find('drivers', (int) $shipment['driver_id'], true);
            db()->prepare('UPDATE shipments SET current_status = ? WHERE id = ?')->execute([$next, $id]);
            self::resourceStatus('trucks', (int) $shipment['truck_id'], $next === 'delivered' ? 'available' : 'in_transit');
            self::resourceStatus('drivers', (int) $shipment['driver_id'], $next === 'delivered' ? 'available' : 'on_delivery');
            self::history($id, $next, $remarks ?: 'Status changed to ' . str_replace('_', ' ', $next) . '.');
            logActivity('Status update', 'Shipments', $shipment['tracking_number'] . ': ' . $next);

            // Trigger role notifications
            $statusLabel = ucwords(str_replace('_', ' ', $next));
            createNotification(null, 'administrator', "Status Update: {$shipment['tracking_number']}", "Shipment status is now {$statusLabel}.", 'info', "index.php?route=shipments&action=show&id={$id}");
            if ($next === 'in_transit' || $next === 'arrived') {
                createNotification(null, 'warehouse', "Shipment {$statusLabel}: {$shipment['tracking_number']}", "Cargo {$shipment['cargo_type']} is now {$statusLabel}.", 'warning', "index.php?route=warehouse");
            }
            if ($next === 'delivered') {
                createNotification(null, 'administrator', "Delivered: {$shipment['tracking_number']}", "Shipment successfully delivered. Truck and driver released.", 'success', "index.php?route=shipments&action=show&id={$id}");
            }
        });
    }

    public static function shipmentDetail(int $id): ?array
    {
        $stmt = db()->prepare('SELECT s.*, c.contact_person, c.company_name, c.contact_number, c.email,
            t.plate_number, d.full_name AS driver_name,
            d.gps_lat, d.gps_lng, d.gps_updated_at
            FROM shipments s JOIN customers c ON c.id = s.customer_id
            LEFT JOIN trucks t ON t.id = s.truck_id LEFT JOIN drivers d ON d.id = s.driver_id WHERE s.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function timeline(int $id): array
    {
        $stmt = db()->prepare('SELECT h.*, u.full_name AS updated_by_name FROM shipment_status_history h
            JOIN users u ON u.id = h.updated_by WHERE h.shipment_id = ? ORDER BY h.created_at DESC, h.id DESC');
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
}
