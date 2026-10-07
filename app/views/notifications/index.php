<?php
$title = 'Notification Center';
$module = 'notifications';
require __DIR__ . '/../partials/header.php';
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Real-Time Alerts</span>
        <h1>Notification Center</h1>
        <p>Operational updates, dispatch assignments, and checkpoint alerts.</p>
    </div>
    <?php if ($unreadNotifCount > 0): ?>
    <form method="post" action="index.php?route=notifications-read-all">
        <?= csrfField(); ?>
        <button type="submit" class="btn btn-outline-secondary">
            <i class="bi bi-check2-all"></i> Mark All as Read
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="workspace-card">
    <?php if (!$notifications): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-bell-slash fs-1 text-muted"></i>
            <h4 class="mt-3">No notifications right now</h4>
            <p class="text-muted">You are all caught up! You will be alerted when new shipments, status changes, or dispatches occur.</p>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notif): 
                $iconMap = [
                    'success' => 'bi-check-circle-fill text-success',
                    'warning' => 'bi-exclamation-triangle-fill text-warning',
                    'danger' => 'bi-x-circle-fill text-danger',
                    'info' => 'bi-info-circle-fill text-primary',
                ];
                $iconClass = $iconMap[$notif['type']] ?? $iconMap['info'];
            ?>
            <div class="list-group-item py-3 px-3 d-flex align-items-start gap-3 border-bottom <?= $notif['is_read'] ? 'bg-white' : 'bg-light'; ?>" style="border-radius: var(--radius-sm); margin-bottom: 6px;">
                <i class="bi <?= $iconClass; ?> fs-4 mt-1 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <strong class="text-dark fs-6"><?= e($notif['title']); ?></strong>
                        <small class="text-muted"><?= e(formatDate($notif['created_at'])); ?></small>
                    </div>
                    <p class="mb-2 text-muted small"><?= nl2br(e($notif['message'])); ?></p>
                    <div class="d-flex gap-2 align-items-center">
                        <?php if ($notif['link']): ?>
                            <a href="<?= e($notif['link']); ?>" class="small record-link">
                                Open Record &rarr;
                            </a>
                        <?php endif; ?>
                        <?php if (!$notif['is_read']): ?>
                            <form method="post" action="index.php?route=notifications-read" class="ms-auto">
                                <?= csrfField(); ?>
                                <input type="hidden" name="id" value="<?= (int) $notif['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-decoration-none text-muted p-0 small">
                                    Mark as read
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

