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

        Settings::setMany([
            'company_name' => $companyName,
            'company_email' => $companyEmail,
            'company_phone' => $companyPhone,
            'company_address' => $companyAddress,
            'company_tagline' => $companyTagline,
            'fuel_baseline_liters' => (string) ($fuelBaseline ?: 15),
            'fuel_price_per_liter' => (string) ($fuelPrice ?: 65.00),
        ]);

        $_SESSION['notice'] = 'System settings updated successfully.';
        if ($this->isModalRequest()) {
            $this->json(['redirect' => 'index.php?route=settings']);
        }
        header('Location: index.php?route=settings');
        exit;
    }
}

