<?php
$title = 'User Accounts & Access';
$module = 'users';
require __DIR__ . '/../partials/header.php';
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Security &amp; Authorization</span>
        <h1>User Management</h1>
        <p><?= (int) $listing['total']; ?> registered system user<?= $listing['total'] === 1 ? '' : 's'; ?></p>
    </div>
    <a class="btn btn-regc" href="index.php?route=users-form" data-modal-link>
        <i class="bi bi-person-plus-fill"></i> Add New User
    </a>
</div>

<div class="workspace-card">
    <form method="get" class="filter-bar">
        <input type="hidden" name="route" value="users">
        <div class="filter-search">
            <label for="search" class="form-label">Search Users</label>
            <input id="search" class="form-control" name="q" value="<?= e($search); ?>" placeholder="Search by name or email">
        </div>
        <div>
            <label for="role-filter" class="form-label">Role</label>
            <select class="form-select" id="role-filter" name="role">
                <option value="">All Roles</option>
                <?php foreach (User::ROLES as $r): ?>
                    <option value="<?= e($r); ?>" <?= $role === $r ? 'selected' : ''; ?>><?= e(ucfirst($r)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="status-filter" class="form-label">Status</label>
            <select class="form-select" id="status-filter" name="status">
                <option value="">All Statuses</option>
                <?php foreach (User::STATUSES as $s): ?>
                    <option value="<?= e($s); ?>" <?= $status === $s ? 'selected' : ''; ?>><?= e(ucfirst($s)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-navy" type="submit"><i class="bi bi-filter"></i> Filter</button>
        <a class="btn btn-outline-secondary" href="index.php?route=users">Clear</a>
    </form>

    <?php if (!$listing['rows']): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-people fs-1 text-muted"></i>
            <h4 class="mt-3">No user accounts found</h4>
            <p class="text-muted">Create your first user or adjust the search parameters.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email Address</th>
                        <th>Access Role</th>
                        <th>Account Status</th>
                        <th>Last Sign-In</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listing['rows'] as $row): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar" style="width: 34px; height: 34px; font-size: 13px;">
                                    <?= e(strtoupper(substr($row['full_name'], 0, 1))); ?>
                                </div>
                                <strong><?= e($row['full_name']); ?></strong>
                            </div>
                        </td>
                        <td><?= e($row['email']); ?></td>
                        <td>
                            <span class="badge bg-light text-dark border text-capitalize px-2 py-1">
                                <?= e($row['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= e($row['status']); ?>">
                                <?= e(ucfirst($row['status'])); ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= e(formatDate($row['last_login_at'])); ?></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a class="btn btn-sm btn-outline-secondary" href="index.php?route=users-form&amp;id=<?= (int) $row['id']; ?>" data-modal-link>
                                    Edit
                                </a>
                                <?php if ($row['id'] != authUser()['id']): ?>
                                <form method="post" action="index.php?route=users-delete" data-confirm="Remove or deactivate user <?= e($row['full_name']); ?>?">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="pagination-bar d-flex justify-content-between align-items-center pt-3" aria-label="Page navigation">
            <span class="text-muted small">Page <?= $listing['page']; ?> of <?= $listing['pages']; ?> (<?= $listing['total']; ?> total)</span>
            <div class="d-flex gap-2">
                <?php if ($listing['page'] > 1): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?<?= e(http_build_query(['route' => 'users', 'q' => $search, 'role' => $role, 'status' => $status, 'page' => $listing['page'] - 1])); ?>">&laquo; Previous</a>
                <?php endif; ?>
                <?php if ($listing['page'] < $listing['pages']): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?<?= e(http_build_query(['route' => 'users', 'q' => $search, 'role' => $role, 'status' => $status, 'page' => $listing['page'] + 1])); ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

