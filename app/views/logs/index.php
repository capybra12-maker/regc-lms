<?php
$title = 'System Activity Logs';
$module = 'logs';
require __DIR__ . '/../partials/header.php';
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Audit &amp; Security</span>
        <h1>Activity Logs</h1>
        <p>Comprehensive immutable audit trail of system events, logins, and operational status advancements.</p>
    </div>
</div>

<div class="workspace-card">
    <form method="get" class="filter-bar">
        <input type="hidden" name="route" value="logs">
        <div class="filter-search">
            <label for="q" class="form-label">Search Event Logs</label>
            <input id="q" class="form-control" name="q" value="<?= e($search); ?>" placeholder="Search action, description, user, IP">
        </div>
        <div>
            <label for="module_filter" class="form-label">Module</label>
            <select class="form-select" id="module_filter" name="module">
                <option value="">All Modules</option>
                <?php foreach ($modules as $mod): ?>
                    <option value="<?= e($mod); ?>" <?= $moduleFilter === $mod ? 'selected' : ''; ?>><?= e($mod); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-navy" type="submit"><i class="bi bi-filter"></i> Filter</button>
        <a class="btn btn-outline-secondary" href="index.php?route=logs">Clear</a>
    </form>

    <?php if (!$logs): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-journal-x fs-1 text-muted"></i>
            <h4 class="mt-3">No activity logs found</h4>
            <p class="text-muted">Adjust your search parameters.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-nowrap small text-muted"><?= e(formatDate($log['created_at'])); ?></td>
                        <td>
                            <strong><?= e($log['user_name'] ?: 'System / Guest'); ?></strong>
                            <?php if ($log['user_email']): ?>
                                <div class="small text-muted"><?= e($log['user_email']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= e($log['module'] ?: 'General'); ?></span>
                        </td>
                        <td class="fw-semibold text-dark"><?= e($log['action']); ?></td>
                        <td style="max-width: 320px; overflow-wrap: break-word;">
                            <small class="text-secondary"><?= e($log['description']); ?></small>
                        </td>
                        <td class="small text-muted font-monospace"><?= e($log['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="pagination-bar d-flex justify-content-between align-items-center pt-3" aria-label="Page navigation">
            <span class="text-muted small">Page <?= $page; ?> of <?= $pages; ?> (<?= $total; ?> total logs)</span>
            <div class="d-flex gap-2">
                <?php if ($page > 1): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?<?= e(http_build_query(['route' => 'logs', 'q' => $search, 'module' => $moduleFilter, 'page' => $page - 1])); ?>">&laquo; Previous</a>
                <?php endif; ?>
                <?php if ($page < $pages): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?<?= e(http_build_query(['route' => 'logs', 'q' => $search, 'module' => $moduleFilter, 'page' => $page + 1])); ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

