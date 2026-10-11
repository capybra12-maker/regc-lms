<?php

require_once __DIR__ . '/../models/Settings.php';

class SettingsController
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

        $settings = Settings::all();
        $module = 'settings';
        $modalRequest = $this->isModalRequest();

        require __DIR__ . '/../views/settings/index.php';
    }

    public function save(): void
    {
        requireRole(['administrator']);
        verifyCsrf();

        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $companyEmail = trim((string) ($_POST['company_email'] ?? ''));
        $companyPhone = trim((string) ($_POST['company_phone'] ?? ''));
        $companyAddress = trim((string) ($_POST['company_address'] ?? ''));
        $companyTagline = trim((string) ($_POST['company_tagline'] ?? ''));
        $fuelBaseline = filter_var($_POST['fuel_baseline_liters'] ?? 15, FILTER_VALIDATE_FLOAT);
        $fuelPrice = filter_var($_POST['fuel_price_per_liter'] ?? 65, FILTER_VALIDATE_FLOAT);

        if ($companyName === '') {
            $err = 'Company name cannot be empty.';
            if ($this->isModalRequest()) {
                $this->json(['message' => $err], 422);
            }
            $_SESSION['operation_error'] = $err;
            header('Location: index.php?route=settings');
            exit;
        }

        $mailHost     = trim((string) ($_POST['mail_host'] ?? ''));
        $mailPort     = trim((string) ($_POST['mail_port'] ?? '587'));
        $mailEnc      = trim((string) ($_POST['mail_encryption'] ?? 'tls'));
        $mailUser     = trim((string) ($_POST['mail_username'] ?? ''));
        $mailPass     = trim((string) ($_POST['mail_password'] ?? ''));
        $mailFrom     = trim((string) ($_POST['mail_from'] ?? ''));
        $mailFromName = trim((string) ($_POST['mail_from_name'] ?? ''));

        $smsApiKey    = trim((string) ($_POST['sms_api_key'] ?? ''));
        $smsSender    = trim((string) ($_POST['sms_sender_name'] ?? ''));

        $settingsData = [
            'company_name'         => $companyName,
            'company_email'        => $companyEmail,
            'company_phone'        => $companyPhone,
            'company_address'      => $companyAddress,
            'company_tagline'      => $companyTagline,
            'fuel_baseline_liters' => (string) ($fuelBaseline ?: 15),
            'fuel_price_per_liter' => (string) ($fuelPrice ?: 65.00),
            'mail_host'            => $mailHost,
            'mail_port'            => $mailPort,
            'mail_encryption'      => $mailEnc,
            'mail_username'        => $mailUser,
            'mail_from'            => $mailFrom,
            'mail_from_name'       => $mailFromName,
        ];

        // Only update mail password if a new one was entered (preserves existing password)
        if ($mailPass !== '') {
            $settingsData['mail_password'] = $mailPass;
        }

        if (isset($_POST['sms_api_key'])) {
            $settingsData['sms_api_key'] = $smsApiKey;
        }
        if (isset($_POST['sms_sender_name'])) {
            $settingsData['sms_sender_name'] = $smsSender;
        }

        Settings::setMany($settingsData);

        $_SESSION['notice'] = 'System settings updated successfully.';
        if ($this->isModalRequest()) {
            $this->json(['redirect' => 'index.php?route=settings']);
        }
        header('Location: index.php?route=settings');
        exit;
    }
}

