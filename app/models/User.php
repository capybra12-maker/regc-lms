<?php

class User
{
    public const ROLES = ['administrator', 'driver', 'warehouse'];
    public const STATUSES = ['active', 'inactive'];

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, full_name, email, password, role, status, last_login_at, created_at, updated_at
             FROM users
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, full_name, email, password, role, status
             FROM users
             WHERE LOWER(email) = LOWER(?)
             LIMIT 1'
        );
        $stmt->execute([trim($email)]);
        return $stmt->fetch() ?: null;
    }

    public static function findByIdentifier(string $identifier): ?array
    {
        $clean = trim($identifier);
        if ($clean === '') {
            return null;
        }

        $lower = strtolower($clean);

        // 1. Direct email match (case-insensitive)
        $stmt = db()->prepare(
            'SELECT id, full_name, email, password, role, status
             FROM users
             WHERE LOWER(email) = ?
             LIMIT 1'
        );
        $stmt->execute([$lower]);
        $user = $stmt->fetch() ?: null;
        if ($user) {
            return $user;
        }

        // 2. Cross-domain fallback (.local <-> .com)
        if (str_ends_with($lower, '@regc.local')) {
            $alt = substr($lower, 0, -11) . '@regc.com';
            $stmt->execute([$alt]);
            $user = $stmt->fetch() ?: null;
            if ($user) return $user;
        } elseif (str_ends_with($lower, '@regc.com')) {
            $alt = substr($lower, 0, -9) . '@regc.local';
            $stmt->execute([$alt]);
            $user = $stmt->fetch() ?: null;
            if ($user) return $user;
        }

        // 3. Match by Full Name / Username
        $stmt = db()->prepare(
            'SELECT id, full_name, email, password, role, status
             FROM users
             WHERE LOWER(full_name) = ?
             LIMIT 1'
        );
        $stmt->execute([$lower]);
        $user = $stmt->fetch() ?: null;
        if ($user) {
            return $user;
        }

        // 4. Prefix match (e.g. typing "wiz" matches "wiz@regc.com" or "wiz@regc.local")
        if (!str_contains($lower, '@')) {
            $stmt = db()->prepare(
                'SELECT id, full_name, email, password, role, status
                 FROM users
                 WHERE LOWER(email) = ? OR LOWER(email) = ?
                 LIMIT 1'
            );
            $stmt->execute([$lower . '@regc.com', $lower . '@regc.local']);
            $user = $stmt->fetch() ?: null;
            if ($user) return $user;
        }

        return null;
    }

    public static function updateLastLogin(int $userId): void
    {
        $stmt = db()->prepare(
            'UPDATE users
             SET last_login_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$userId]);
    }

    public static function updatePasswordHash(int $userId, string $hash): void
    {
        $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $userId]);
    }

    public static function listing(string $search = '', string $role = '', string $status = '', int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(full_name LIKE ? OR email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($role !== '') {
            $where[] = 'role = ?';
            $params[] = $role;
        }

        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = db()->prepare("SELECT COUNT(*) FROM users{$sql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = db()->prepare("SELECT id, full_name, email, role, status, last_login_at, created_at FROM users{$sql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    public static function saveUser(array $data, int $id = 0): int
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $role = trim((string) ($data['role'] ?? 'warehouse'));
        $status = trim((string) ($data['status'] ?? 'active'));
        $password = (string) ($data['password'] ?? '');

        if ($fullName === '' || mb_strlen($fullName) > 150) {
            throw new DomainException('Full name is required (max 150 characters).');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            throw new DomainException('Please enter a valid email address.');
        }

        if (!in_array($role, self::ROLES, true)) {
            throw new DomainException('Invalid user role.');
        }

        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainException('Invalid user status.');
        }

        // Check unique email
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn()) {
            throw new DomainException('Email address is already taken by another user.');
        }

        if ($id === 0) {
            if (strlen($password) < 8) {
                throw new DomainException('Password must be at least 8 characters long.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$fullName, $email, $hash, $role, $status]);
            $newId = (int) db()->lastInsertId();

            if ($role === 'driver') {
                $check = db()->prepare('SELECT id FROM drivers WHERE user_id = ? OR LOWER(full_name) = ? LIMIT 1');
                $check->execute([$newId, strtolower($fullName)]);
                $existingDriver = $check->fetch();
                if ($existingDriver) {
                    db()->prepare('UPDATE drivers SET user_id = ? WHERE id = ?')->execute([$newId, $existingDriver['id']]);
                } else {
                    $lic = 'N' . date('y') . '-' . str_pad((string)$newId, 6, '0', STR_PAD_LEFT);
                    db()->prepare('INSERT INTO drivers (user_id, full_name, license_number, status) VALUES (?, ?, ?, ?)')->execute([$newId, $fullName, $lic, 'available']);
                }
            }

            logActivity('Create User', 'Users', "Created user {$fullName} ({$email}) with role {$role}");
            return $newId;
        }

        // Updating user
        if ($password !== '') {
            if (strlen($password) < 8) {
                throw new DomainException('Password must be at least 8 characters long.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?');
            $stmt->execute([$fullName, $email, $hash, $role, $status, $id]);
        } else {
            $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?');
            $stmt->execute([$fullName, $email, $role, $status, $id]);
        }

        if ($role === 'driver') {
            $check = db()->prepare('SELECT id FROM drivers WHERE user_id = ? OR LOWER(full_name) = ? LIMIT 1');
            $check->execute([$id, strtolower($fullName)]);
            $existingDriver = $check->fetch();
            if ($existingDriver) {
                db()->prepare('UPDATE drivers SET user_id = ?, full_name = ? WHERE id = ?')->execute([$id, $fullName, $existingDriver['id']]);
            } else {
                $lic = 'N' . date('y') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
                db()->prepare('INSERT INTO drivers (user_id, full_name, license_number, status) VALUES (?, ?, ?, ?)')->execute([$id, $fullName, $lic, 'available']);
            }
        }

        logActivity('Update User', 'Users', "Updated user #{$id} ({$email})");
        return $id;
    }

    public static function delete(int $id): void
    {
        $currentUserId = authUser()['id'] ?? 0;
        if ($id === (int) $currentUserId) {
            throw new DomainException('You cannot delete your own account.');
        }

        $user = self::findById($id);
        if (!$user) {
            throw new DomainException('User not found.');
        }

        // Check if user has active shipments, logs, etc.
        $stmt = db()->prepare('SELECT id FROM shipments WHERE created_by = ? LIMIT 1');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn()) {
            // Set inactive rather than deleting to preserve referential integrity
            db()->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")->execute([$id]);
            logActivity('Deactivate User', 'Users', "Deactivated user #{$id} ({$user['email']})");
            return;
        }

        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        logActivity('Delete User', 'Users', "Deleted user #{$id} ({$user['email']})");
    }
}
