<?php

/**
 * SMS Service — Semaphore (Philippine SMS Gateway)
 *
 * Get a FREE API key at: https://semaphore.co
 * Free tier: 1,000 SMS/month. No credit card needed.
 *
 * .env keys required:
 *   SMS_PROVIDER=semaphore
 *   SMS_API_KEY=your-semaphore-api-key
 *   SMS_SENDER_NAME=REGCLOGISTICS   (max 11 chars, no spaces)
 */
class SMS
{
    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Send tracking number SMS to the customer for a given shipment.
     *
     * @param int $shipmentId
     * @return array{ok: bool, message: string}
     */
    public static function sendTrackingNotification(int $shipmentId): array
    {
        require_once __DIR__ . '/../models/Operations.php';

        $shipment = Operations::shipmentDetail($shipmentId);
        if (!$shipment) {
            return ['ok' => false, 'message' => 'Shipment record not found.'];
        }

        // Fetch customer record
        $stmt = db()->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $shipment['customer_id']]);
        $customer = $stmt->fetch();

        if (!$customer || empty($customer['contact_number'])) {
            return [
                'ok'      => false,
                'message' => 'Customer does not have a contact number on file. '
                    . 'Please update the customer profile first.',
            ];
        }

        $trackingNumber = $shipment['tracking_number']
            ?: ('REGC-' . date('Y') . '-' . str_pad($shipment['id'], 6, '0', STR_PAD_LEFT));

        $appUrl   = defined('APP_URL') && APP_URL ? APP_URL : '';
        $name     = $customer['contact_person'] ?? 'Customer';
        $dest     = $shipment['destination'] ?? 'your destination';

        // Build the SMS message (keep under 160 chars for single SMS)
        $message = self::buildMessage($name, $trackingNumber, $dest, $appUrl);

        // Format phone number to Philippine format
        $phone = self::formatPhone($customer['contact_number']);

        if (!$phone) {
            return [
                'ok'      => false,
                'message' => "Invalid phone number format: {$customer['contact_number']}. "
                    . 'Expected: 09XXXXXXXXX or +639XXXXXXXXX',
            ];
        }

        // ── Archive copy of SMS in storage/sms/ ───────────────────────
        $storageDir = __DIR__ . '/../../storage/sms';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }
        $safeTracking = preg_replace('/[^A-Za-z0-9_-]/', '_', $trackingNumber);
        $archiveFile  = sprintf('%s/%s_%s_%s.txt', $storageDir, date('Ymd_His'), $phone, $safeTracking);
        @file_put_contents($archiveFile, "TO: {$phone}\nCUSTOMER: {$recipientName}\nTRACKING: {$trackingNumber}\nDATE: " . date('Y-m-d H:i:s') . "\nMESSAGE:\n{$message}\n");

        // Send via configured provider
        $result = self::send($phone, $message);
        $result['phone'] = $phone;
        $result['message_text'] = $message;
        $result['archive_file'] = $archiveFile;

        // Audit log
        $recipientName = $customer['contact_person'] ?? 'Customer';
        logActivity(
            'SMS Tracking',
            'Shipments',
            "Tracking SMS ({$trackingNumber}) → {$phone} ({$recipientName}). "
                . ($result['ok'] ? 'Delivered.' : 'NOTICE: ' . $result['message'])
        );

        // Internal notification
        createNotification(
            null,
            'administrator',
            ($result['ok'] ? '📱 SMS Sent: ' : '⚠ SMS Queued/Unsent: ') . $trackingNumber,
            ($result['ok']
                ? "Tracking SMS sent to {$phone}."
                : "SMS gateway notice: " . $result['message']),
            $result['ok'] ? 'success' : 'warning',
            "index.php?route=shipments&action=show&id={$shipmentId}"
        );

        return $result;
    }

    /**
     * Send a raw SMS to any phone number.
     *
     * @param string $to     Phone number (will be formatted automatically)
     * @param string $message SMS body text
     * @return array{ok: bool, message: string}
     */
    public static function send(string $to, string $message): array
    {
        $phone = self::formatPhone($to);
        if (!$phone) {
            return ['ok' => false, 'message' => "Invalid phone number: {$to}"];
        }

        if (!defined('SMS_API_KEY') || SMS_API_KEY === '' || SMS_API_KEY === 'your-semaphore-api-key') {
            return [
                'ok'      => false,
                'message' => 'SMS Gateway is not active. To send automated SMS from the server, sign up at semaphore.co and set SMS_API_KEY in .env. You can also send directly from your phone using the "Direct Phone SMS" button.',
            ];
        }

        $provider = defined('SMS_PROVIDER') ? strtolower(SMS_PROVIDER) : 'semaphore';

        return match ($provider) {
            'semaphore' => self::sendViaSemaphore($phone, $message),
            default     => ['ok' => false, 'message' => "Unknown SMS provider: {$provider}"],
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Format any Philippine phone number to 09XXXXXXXXX format.
     * Accepts: 09xxxxxxxxx, +639xxxxxxxxx, 639xxxxxxxxx
     */
    public static function formatPhone(string $raw): ?string
    {
        $clean = preg_replace('/[^0-9+]/', '', $raw);

        // +639XXXXXXXXX → 09XXXXXXXXX
        if (str_starts_with($clean, '+63') && strlen($clean) === 13) {
            return '0' . substr($clean, 3);
        }

        // 639XXXXXXXXX → 09XXXXXXXXX
        if (str_starts_with($clean, '63') && strlen($clean) === 12) {
            return '0' . substr($clean, 2);
        }

        // Already 09XXXXXXXXX
        if (str_starts_with($clean, '09') && strlen($clean) === 11) {
            return $clean;
        }

        return null; // invalid format
    }

    /**
     * Build the SMS body. Kept under 160 chars to avoid multi-part billing.
     */
    public static function buildMessage(
        string $name,
        string $trackingNumber,
        string $destination,
        string $appUrl
    ): string {
        $firstName = explode(' ', trim($name))[0]; // use first name only to save space

        if ($appUrl) {
            $trackUrl = $appUrl . '/index.php?route=tracking&number=' . urlencode($trackingNumber);
            $msg = "Hi {$firstName}! Your REGC cargo is en route to {$destination}. "
                 . "Tracking#: {$trackingNumber}. Track: {$trackUrl}";
        } else {
            $msg = "Hi {$firstName}! Your REGC cargo is en route to {$destination}. "
                 . "Tracking#: {$trackingNumber}. Reply TRACK for status.";
        }

        // Truncate to 160 chars if too long (URL makes it long)
        if (strlen($msg) > 160) {
            $msg = "Hi {$firstName}! REGC Tracking#: {$trackingNumber} "
                 . "→ {$destination}. Visit our site and enter your code to track live.";
        }

        return $msg;
    }

    // ── Provider Implementations ──────────────────────────────────────────────

    /**
     * Send via Semaphore API (https://semaphore.co)
     * Free: 1,000 SMS/month. No credit card required.
     */
    private static function sendViaSemaphore(string $phone, string $message): array
    {
        $senderName = defined('SMS_SENDER_NAME') && SMS_SENDER_NAME !== ''
            ? SMS_SENDER_NAME
            : 'REGCLMS';

        $payload = http_build_query([
            'apikey'      => SMS_API_KEY,
            'number'      => $phone,
            'message'     => $message,
            'sendername'  => $senderName,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                           . "Accept: application/json\r\n",
                'content' => $payload,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents('https://api.semaphore.co/api/v4/messages', false, $ctx);

        if ($raw === false) {
            // Try HTTP as fallback if HTTPS fails (cert issues on some XAMPP configs)
            $raw = @file_get_contents('http://api.semaphore.co/api/v4/messages', false, $ctx);
        }

        if ($raw === false) {
            return [
                'ok'      => false,
                'message' => 'Could not reach Semaphore API. Check internet connection.',
            ];
        }

        $json = json_decode($raw, true);

        // Semaphore returns an array of message objects on success
        if (is_array($json) && !empty($json)) {
            $first = is_array($json[0]) ? $json[0] : $json;
            $status = strtolower($first['status'] ?? 'unknown');

            if (in_array($status, ['pending', 'sent', 'queued'], true)) {
                return [
                    'ok'         => true,
                    'message'    => "📱 SMS queued for {$phone}. Status: {$status}.",
                    'message_id' => $first['message_id'] ?? null,
                ];
            }

            // API-level error returned in body
            if (isset($first['message'])) {
                return ['ok' => false, 'message' => 'Semaphore: ' . $first['message']];
            }
        }

        // Semaphore error (e.g. invalid API key) often returns {"status":"error","message":"..."}
        if (isset($json['message'])) {
            return ['ok' => false, 'message' => 'Semaphore: ' . $json['message']];
        }

        return ['ok' => false, 'message' => 'Unexpected response from Semaphore: ' . substr($raw, 0, 200)];
    }
}
