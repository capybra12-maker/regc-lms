<?php

require_once __DIR__ . '/../models/User.php';

class UserController
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
        $role = trim((string) ($_GET['role'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $listing = User::listing($search, $role, $status, $page);
        $module = 'users';

        require __DIR__ . '/../views/users/listing.php';
    }

    public function form(): void
    {
        requireRole(['administrator']);

        $id = (int) ($_GET['id'] ?? 0);
        $user = $id ? User::findById($id) : null;

        if ($id && !$user) {
            http_response_code(404);
            exit('User not found.');
        }

        $modalRequest = $this->isModalRequest();
        $error = null;
        $values = $user ?? [
            'full_name' => '',
            'email' => '',
            'role' => 'warehouse',
            'status' => 'active',
        ];

        require __DIR__ . '/../views/users/form.php';
    }

    public function save(): void
    {
        requireRole(['administrator']);
        verifyCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $error = null;

        try {
            User::saveUser($_POST, $id);
            $_SESSION['notice'] = $id ? 'User account updated successfully.' : 'User account created successfully.';

            if ($this->isModalRequest()) {
                $this->json(['redirect' => 'index.php?route=users']);
            }
            header('Location: index.php?route=users');
            exit;
        } catch (DomainException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('User save error: ' . $e->getMessage());
            $error = 'Failed to save user account. Please check your entries.';
        }

        http_response_code(422);
        $modalRequest = $this->isModalRequest();
        $values = $_POST;
        require __DIR__ . '/../views/users/form.php';
    }

    public function delete(): void
    {
        requireRole(['administrator']);
        verifyCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        try {
            User::delete($id);
            $_SESSION['notice'] = 'User account removed or deactivated.';
        } catch (DomainException $e) {
            $_SESSION['operation_error'] = $e->getMessage();
        }

        if ($this->isModalRequest()) {
            $this->json(['redirect' => 'index.php?route=users']);
        }
        header('Location: index.php?route=users');
        exit;
    }
}

