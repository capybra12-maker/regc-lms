<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

require_once __DIR__ . '/../models/Operations.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

class Mailer
{
    /**
     * Send tracking number and delivery details email to the customer.
     *
     * Configure SMTP in .env:
     *   MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION, MAIL_USERNAME, MAIL_PASSWORD,
     *   MAIL_FROM, MAIL_FROM_NAME
     *
     * @param int $shipmentId
     * @return array{ok: bool, message: string, recipient?: string}
     */
    public static function sendTrackingEmail(int $shipmentId): array
    {
        $shipment = Operations::shipmentDetail($shipmentId);
        if (!$shipment) {
            return ['ok' => false, 'message' => 'Shipment record not found.'];
        }

        // Fetch customer record
        $stmt = db()->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $shipment['customer_id']]);
        $customer = $stmt->fetch();

        if (!$customer || empty($customer['email'])) {
            return [
                'ok'      => false,
                'message' => 'Customer does not have an email address on file. Please update the customer profile first.',
            ];
        }

        $to             = trim($customer['email']);
        $recipientName  = $customer['contact_person'] ?: ($customer['company_name'] ?: 'Valued Customer');
        $trackingNumber = $shipment['tracking_number']
            ?: ('REGC-' . date('Y') . '-' . str_pad($shipment['id'], 6, '0', STR_PAD_LEFT));
        $subject        = "Your Cargo Tracking Code: {$trackingNumber} — REGC Fleet Management";
        $htmlBody       = self::buildTrackingEmailHtml($shipment, $customer, $trackingNumber);

        // ── Archive copy (always) ──────────────────────────────────────
        $storageDir = __DIR__ . '/../../storage/emails';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }
        $safeTracking = preg_replace('/[^A-Za-z0-9_-]/', '_', $trackingNumber);
        $archiveFile  = sprintf('%s/%s_%s.html', $storageDir, date('Ymd_His'), $safeTracking);
        @file_put_contents($archiveFile, $htmlBody);

        // ── Send via SMTP / PHPMailer ──────────────────────────────────
        $smtpConfigured = defined('MAIL_HOST') && MAIL_HOST !== '' && MAIL_USERNAME !== '';

        if ($smtpConfigured) {
            $mail = new PHPMailer(true); // true = exceptions enabled

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host        = MAIL_HOST;
                $mail->SMTPAuth    = true;
                $mail->Username    = MAIL_USERNAME;
                $mail->Password    = MAIL_PASSWORD;
                $mail->Port        = MAIL_PORT;

                // TLS / SSL
                $enc = strtolower(MAIL_ENCRYPTION);
                if ($enc === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    if (MAIL_PORT === 587) {
                        $mail->Port = 465; // standard SSL port
                    }
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }

                // Recipients
                $fromAddress = MAIL_FROM ?: MAIL_USERNAME;
                $fromName    = MAIL_FROM_NAME ?: 'REGC Fleet Management';
                $mail->setFrom($fromAddress, $fromName);
                $mail->addAddress($to, $recipientName);
                $mail->addReplyTo($fromAddress, $fromName);

                // Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->AltBody = "Your tracking code is: {$trackingNumber}\n\nTrack your cargo at: "
                    . (defined('APP_URL') ? APP_URL : 'http://localhost/regc-lms')
                    . "/index.php?route=tracking&number=" . urlencode($trackingNumber);

                $mail->send();
                $delivered = true;
                $deliveryMsg = "Email sent via SMTP to {$to}.";

            } catch (MailerException $e) {
                $delivered    = false;
                $deliveryMsg  = 'SMTP error: ' . $mail->ErrorInfo;
                // Log the SMTP error
                error_log("[REGC Mailer] SMTP failed for shipment #{$shipmentId}: " . $mail->ErrorInfo);
            }
        } else {
            // No SMTP configured — archive-only mode
            $delivered   = false;
            $deliveryMsg = 'SMTP not configured. Email archived locally only. '
                . 'Set MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD in .env to enable real delivery.';
        }

        // ── Audit log ─────────────────────────────────────────────────
        logActivity(
            'Email Tracking',
            'Shipments',
            "Tracking {$trackingNumber} → {$to} ({$recipientName}). " . $deliveryMsg
        );

        // ── Internal notification ──────────────────────────────────────
        createNotification(
            null,
            'administrator',
            ($delivered ? '✉ Email Sent: ' : '⚠ Email Queued: ') . $trackingNumber,
            ($delivered
                ? "Tracking email delivered to {$to}."
                : "Email archived locally. {$deliveryMsg}"),
            $delivered ? 'success' : 'warning',
            "index.php?route=shipments&action=show&id={$shipmentId}"
        );

        if ($delivered) {
            return [
                'ok'             => true,
                'message'        => "✅ Tracking code {$trackingNumber} emailed to {$to}.",
                'recipient'      => $to,
                'tracking_number' => $trackingNumber,
                'preview_file'   => $archiveFile,
            ];
        } else {
            return [
                'ok'             => false,
                'message'        => "⚠ Could not send email: {$deliveryMsg} — "
                    . "Preview saved: storage/emails/{$safeTracking}.html",
                'recipient'      => $to,
                'tracking_number' => $trackingNumber,
                'preview_file'   => $archiveFile,
            ];
        }
    }

    /**
     * Build responsive HTML email template with REGC branding.
     */
    public static function buildTrackingEmailHtml(array $shipment, array $customer, string $trackingNumber): string
    {
        $appUrl       = defined('APP_URL') && APP_URL ? APP_URL : 'http://localhost/regc-lms';
        $trackingUrl  = $appUrl . '/index.php?route=tracking&number=' . urlencode($trackingNumber);
        $homeUrl      = $appUrl . '/index.php';

        $customerName = htmlspecialchars($customer['contact_person'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
        $companyName  = htmlspecialchars($customer['company_name']   ?? '',         ENT_QUOTES, 'UTF-8');
        $cargoType    = htmlspecialchars($shipment['cargo_type']      ?? 'General Freight', ENT_QUOTES, 'UTF-8');
        $weight       = number_format((float)($shipment['cargo_weight_kg'] ?? 0), 2);
        $pickup       = htmlspecialchars($shipment['pickup_location'] ?? 'Designated Wharf / Pier', ENT_QUOTES, 'UTF-8');
        $dest         = htmlspecialchars($shipment['destination']     ?? 'Customer Warehouse', ENT_QUOTES, 'UTF-8');
        $date         = !empty($shipment['shipment_date'])
            ? date('M j, Y \a\t g:i A', strtotime($shipment['shipment_date']))
            : 'To be scheduled';
        $status       = ucwords(str_replace('_', ' ', $shipment['current_status'] ?? 'pending'));
        $companyLabel = $companyName !== '' ? " ({$companyName})" : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REGC Shipment Tracking</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#333;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f9; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.08);">

                    <!-- Header Banner -->
                    <tr>
                        <td style="background-color:#0e1726; padding: 30px 35px; text-align: left; border-bottom: 3px solid #f5c842;">
                            <h2 style="margin:0; color:#ffffff; font-size:22px; font-weight:800; letter-spacing:0.02em;">REGC FLEET MANAGEMENT</h2>
                            <p style="margin:4px 0 0 0; color:#f5c842; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em;">Commercial Freight &middot; Pier-to-Warehouse Logistics</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 35px 35px 25px 35px;">
                            <p style="margin:0 0 16px 0; font-size:16px; line-height:1.5; color:#1e293b;">
                                Hello <strong>{$customerName}</strong>{$companyLabel},
                            </p>
                            <p style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#475569;">
                                Your cargo shipment has been registered in our logistics dispatch system. You can monitor your delivery milestone, route progression, and live GPS coordinates 24/7.
                            </p>

                            <!-- Tracking Code Highlight Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc; border:2px dashed #f5c842; border-radius:10px; margin-bottom: 26px;">
                                <tr>
                                    <td style="padding: 22px; text-align: center;">
                                        <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.1em; color:#64748b; font-weight:700; margin-bottom:6px;">Your Official Tracking Code</div>
                                        <div style="font-size:26px; font-weight:900; color:#0e1726; letter-spacing:0.05em; font-family:Consolas,monospace;">{$trackingNumber}</div>
                                        <div style="font-size:12px; color:#22c55e; font-weight:600; margin-top:6px;">&bull; Current Milestone: {$status}</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Direct Action Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{$trackingUrl}" target="_blank" style="display:inline-block; background-color:#f5c842; color:#0e1726; font-size:15px; font-weight:800; text-decoration:none; padding:14px 34px; border-radius:8px; box-shadow:0 4px 15px rgba(245,200,66,0.4);">
                                            &#128269; Track Your Cargo Live
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Search on Homepage Instructions -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eff6ff; border-radius:8px; margin-bottom: 26px; border:1px solid #bfdbfe;">
                                <tr>
                                    <td style="padding:14px; font-size:13px; color:#1e3a8a; line-height:1.6;">
                                        <strong>&#128270; How to track anytime on our homepage:</strong><br>
                                        1. Visit: <a href="{$homeUrl}" style="color:#1d4ed8; font-weight:bold;">{$homeUrl}</a><br>
                                        2. Enter tracking code <code>{$trackingNumber}</code> in the search bar.<br>
                                        3. Click <strong>Track Live</strong> to view driver GPS updates and delivery status.
                                    </td>
                                </tr>
                            </table>

                            <!-- Cargo Specs Table -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; margin-bottom: 20px;">
                                <tr>
                                    <td colspan="2" style="font-size:13px; font-weight:700; text-transform:uppercase; color:#0e1726; padding-bottom:8px; border-bottom:1px solid #e2e8f0;">Shipment Summary</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #f1f5f9;">Cargo Type:</td>
                                    <td style="padding:10px 0; font-size:14px; color:#0e1726; font-weight:600; text-align:right; border-bottom:1px solid #f1f5f9;">{$cargoType} ({$weight} kg)</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #f1f5f9;">Origin / Pickup:</td>
                                    <td style="padding:10px 0; font-size:14px; color:#0e1726; font-weight:600; text-align:right; border-bottom:1px solid #f1f5f9;">{$pickup}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #f1f5f9;">Destination:</td>
                                    <td style="padding:10px 0; font-size:14px; color:#0e1726; font-weight:600; text-align:right; border-bottom:1px solid #f1f5f9;">{$dest}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; font-size:14px; color:#64748b;">Pickup Schedule:</td>
                                    <td style="padding:10px 0; font-size:14px; color:#0e1726; font-weight:600; text-align:right;">{$date}</td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#080d16; padding: 24px 35px; text-align: center; color: #94a3b8; font-size: 12px; line-height: 1.6;">
                            <strong style="color:#ffffff;">REGC Fleet Management & Shipment Tracking</strong><br>
                            Makar Wharf / Port Road, General Santos City, Philippines<br>
                            Dispatch Helpline: (083) 552-0000 &middot; Email: dispatch@regctrucking.com<br>
                            <span style="font-size:11px; opacity:0.7;">This is an automated delivery notification. Please do not reply directly to this email.</span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
