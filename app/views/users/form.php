<?php
$isEdit = !empty($values['id']);
$title = $isEdit ? 'Edit User Account' : 'Add New User Account';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
?>

<div data-modal-panel data-modal-title="<?= e($title); ?>">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">User Management</span><h1><?= e($title); ?></h1></div>
    <a class="btn btn-outline-secondary" href="index.php?route=users">&larr; Back to Users</a>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error); ?></div>
<?php endif; ?>

<form method="post" action="index.php?route=users-save" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form" data-modal-form>
    <?= csrfField(); ?>
    <input type="hidden" name="id" value="<?= (int) ($values['id'] ?? 0); ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="user_full_name">Full Name *</label>
            <input class="form-control" type="text" id="user_full_name" name="full_name" value="<?= e($values['full_name'] ?? ''); ?>" required maxlength="150" placeholder="e.g. Juan Dela Cruz" autofocus>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="user_email">Email Address *</label>
            <input class="form-control" type="email" id="user_email" name="email" value="<?= e($values['email'] ?? ''); ?>" required maxlength="190" placeholder="user@regc.com">
        </div>

        <div class="col-md-6">
            <label class="form-label" for="user_role">Access Role *</label>
            <select class="form-select" id="user_role" name="role" required>
                <?php foreach (User::ROLES as $r): ?>
                    <option value="<?= e($r); ?>" <?= ($values['role'] ?? '') === $r ? 'selected' : ''; ?>><?= e(ucfirst($r)); ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Defines permissions and accessible workspace.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="user_status">Account Status *</label>
            <select class="form-select" id="user_status" name="status" required>
                <?php foreach (User::STATUSES as $s): ?>
                    <option value="<?= e($s); ?>" <?= ($values['status'] ?? '') === $s ? 'selected' : ''; ?>><?= e(ucfirst($s)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label" for="user_password">
                Password <?= $isEdit ? '<span class="text-muted fw-normal">(Leave blank to keep current password)</span>' : '*'; ?>
            </label>
            <div class="input-group">
                <input class="form-control border-end-0" type="password" id="user_password" name="password" minlength="8" <?= $isEdit ? '' : 'required'; ?> placeholder="Minimum 8 characters" autocomplete="new-password">
                <button class="btn btn-outline-secondary password-toggle border-start-0" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <small class="form-text text-muted">Must be at least 8 characters. Users can log in using either their Email Address or their Full Name.</small>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-regc" type="submit">
            <i class="bi bi-check-lg"></i> Save User Account
        </button>
        <a class="btn btn-outline-secondary" href="index.php?route=users" data-modal-close>Cancel</a>
    </div>
</form>
</div>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>

