<?php

class Proof
{
    public static function forShipment(int $shipmentId): ?array
    {
        $stmt = db()->prepare('SELECT pod.*, u.full_name AS uploaded_by_name
            FROM proof_of_delivery pod
            JOIN users u ON u.id = pod.uploaded_by
            WHERE pod.shipment_id = ?
            ORDER BY pod.id DESC
            LIMIT 1');
        $stmt->execute([$shipmentId]);
        return $stmt->fetch() ?: null;
    }

    public static function save(int $shipmentId, array $data, ?array $files = null): int
    {
        $receivedBy = trim((string) ($data['received_by'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        $uploadedBy = authUser()['id'] ?? 1;

        if ($receivedBy === '') {
            throw new DomainException('Recipient name is required.');
        }

        $proofImage = null;
        $deliveryReceipt = null;
        $signatureFile = null;

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/pod';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $fields = ['proof_image', 'delivery_receipt', 'signature_file'];

        // 1. Process Base64 encoded uploads (bypasses OpenResty / WAF multipart blocks on shared hosting)
        foreach ($fields as $field) {
            $base64Key = $field . '_base64';
            if (!empty($data[$base64Key]) && is_string($data[$base64Key])) {
                $raw = trim($data[$base64Key]);
                if (preg_match('/^data:(image\/(jpeg|jpg|png|webp)|application\/pdf);base64,(.+)$/is', $raw, $matches)) {
                    $mime = strtolower($matches[1]);
                    $ext = match ($mime) {
                        'image/jpeg', 'image/jpg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        'application/pdf' => 'pdf',
                        default => 'jpg'
                    };
                    $binary = base64_decode($matches[3], true);
                    if ($binary !== false && strlen($binary) > 0) {
                        $filename = 'pod_' . $shipmentId . '_' . $field . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $targetPath = $uploadDir . '/' . $filename;
                        if (file_put_contents($targetPath, $binary) !== false) {
                            $$field = $filename;
                        }
                    }
                }
            }
        }

        // 2. Fallback to standard $_FILES if available
        if ($files) {
            foreach ($fields as $field) {
                if (empty($$field) && !empty($files[$field]['tmp_name']) && is_uploaded_file($files[$field]['tmp_name'])) {
                    $ext = strtolower(pathinfo($files[$field]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExtensions, true)) {
                        throw new DomainException("Invalid file type for {$field}. Only JPG, PNG, WEBP, and PDF files are permitted.");
                    }
                    if ($files[$field]['size'] > 10 * 1024 * 1024) {
                        throw new DomainException("File {$field} exceeds maximum size of 10MB.");
                    }
                    $filename = 'pod_' . $shipmentId . '_' . $field . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . '/' . $filename;
                    if (move_uploaded_file($files[$field]['tmp_name'], $targetPath)) {
                        $$field = $filename;
                    }
                }
            }
        }

        // Check if existing record
        $existing = self::forShipment($shipmentId);
        if ($existing) {
            $proofImage = $proofImage ?: $existing['proof_image'];
            $deliveryReceipt = $deliveryReceipt ?: $existing['delivery_receipt'];
            $signatureFile = $signatureFile ?: $existing['signature_file'];

            $stmt = db()->prepare('UPDATE proof_of_delivery SET
                received_by = ?, notes = ?, proof_image = ?, delivery_receipt = ?, signature_file = ?, uploaded_by = ?, uploaded_at = NOW()
                WHERE id = ?');
            $stmt->execute([$receivedBy, $notes, $proofImage, $deliveryReceipt, $signatureFile, $uploadedBy, $existing['id']]);
            logActivity('Update POD', 'Shipments', "Updated proof of delivery for Shipment #{$shipmentId}");
            return (int) $existing['id'];
        }

        $stmt = db()->prepare('INSERT INTO proof_of_delivery
            (shipment_id, received_by, notes, proof_image, delivery_receipt, signature_file, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$shipmentId, $receivedBy, $notes, $proofImage, $deliveryReceipt, $signatureFile, $uploadedBy]);
        $podId = (int) db()->lastInsertId();

        logActivity('Upload POD', 'Shipments', "Uploaded proof of delivery for Shipment #{$shipmentId} (Received by: {$receivedBy})");
        createNotification(null, 'administrator', 'Proof of Delivery Uploaded', "Proof of delivery was submitted for Shipment #{$shipmentId} by {$receivedBy}.", 'success', "index.php?route=shipments&action=show&id={$shipmentId}");

        return $podId;
    }
}

