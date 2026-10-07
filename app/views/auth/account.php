<?php $title = 'My account'; require __DIR__ . '/../partials/header.php'; ?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">Profile and security</span><h1>My account</h1><p>Review your account details and keep your sign-in secure.</p></div>
    <a class="btn btn-regc" href="index.php?route=account-password" data-modal-link><i class="bi bi-key me-1" aria-hidden="true"></i> Change password</a>
</div>
<div class="account-grid">
    <section class="workspace-card account-card">
        <div class="account-avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))); ?></div>
        <div><span class="eyebrow">Account owner</span><h2><?= e($user['full_name']); ?></h2><p class="mb-0 text-muted"><?= e($user['email']); ?></p></div>
    </section>
    <section class="workspace-card">
        <h2 class="section-title">Account details</h2>
        <dl class="detail-grid account-details">
            <div><dt>Role</dt><dd><?= e(ucfirst($user['role'])); ?></dd></div>
            <div><dt>Status</dt><dd><span class="status-badge status-<?= e($user['status']); ?>"><?= e(ucfirst($user['status'])); ?></span></dd></div>
            <div><dt>Last sign-in</dt><dd><?= e(formatDate($user['last_login_at'])); ?></dd></div>
            <div><dt>Account created</dt><dd><?= e(formatDate($user['created_at'])); ?></dd></div>
        </dl>
    </section>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
