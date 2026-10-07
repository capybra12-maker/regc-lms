<?php
$title = 'Change password';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { $module = 'account'; require __DIR__ . '/../partials/header.php'; }
?>
<div data-modal-panel data-modal-title="Change password">
<?php if (!$modalRequest): ?><div class="page-heading mb-4"><div><span class="eyebrow">Account security</span><h1>Change password</h1></div></div><?php endif; ?>
<p class="text-muted">Use at least 12 characters. A longer passphrase is easier to remember and harder to guess.</p>
<?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= e($error); ?></div><?php endif; ?>
<form method="post" action="index.php?route=account-password" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form" data-modal-form>
    <?= csrfField(); ?>
    <div class="mb-3"><label class="form-label" for="current-password">Current password *</label><input class="form-control" type="password" id="current-password" name="current_password" autocomplete="current-password" required autofocus></div>
    <div class="mb-3"><label class="form-label" for="new-password">New password *</label><input class="form-control" type="password" id="new-password" name="password" minlength="12" autocomplete="new-password" required></div>
    <div><label class="form-label" for="confirm-password">Confirm new password *</label><input class="form-control" type="password" id="confirm-password" name="password_confirmation" minlength="12" autocomplete="new-password" required></div>
    <div class="form-actions"><button class="btn btn-regc" type="submit">Update password</button><a class="btn btn-outline-secondary" href="index.php?route=account" data-modal-close>Cancel</a></div>
</form>
</div>
<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>
