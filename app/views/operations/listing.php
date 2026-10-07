<?php
$title = $config['title'];
$columns = match ($module) {
    'customers' => ['contact_person' => 'Contact person', 'company_name' => 'Company', 'contact_number' => 'Phone', 'email' => 'Email', 'status' => 'Status'],
    'trucks' => ['plate_number' => 'Plate number', 'truck_model' => 'Model', 'truck_type' => 'Type', 'capacity_kg' => 'Capacity (kg)', 'status' => 'Status'],
    'drivers' => ['full_name' => 'Driver', 'license_number' => 'License', 'contact_number' => 'Phone', 'license_expiration' => 'License expiration', 'status' => 'Status'],
    'shipments' => ['tracking_number' => 'Tracking number', 'cargo_type' => 'Cargo', 'destination' => 'Destination', 'cargo_weight_kg' => 'Weight (kg)', 'current_status' => 'Status'],
};
$statuses = match ($module) {
    'customers' => ['active', 'inactive'],
    'trucks' => ['available', 'assigned', 'in_transit', 'maintenance', 'unavailable'],
    'drivers' => ['available', 'assigned', 'on_delivery', 'off_duty'],
    'shipments' => Operations::STATUSES,
};
require __DIR__ . '/../partials/header.php';
?>
<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Fleet &amp; Logistics</span>
        <h1><?= e($title); ?></h1>
        <p><?= (int) $listing['total']; ?> matching record<?= $listing['total'] === 1 ? '' : 's'; ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($module === 'drivers' || $module === 'shipments'): ?>
            <a class="btn btn-warning text-dark fw-bold" href="index.php?route=driver-map">
                <i class="bi bi-map-fill me-1"></i> Live Fleet GPS Map
            </a>
        <?php endif; ?>
        <a class="btn btn-regc" href="index.php?route=<?= e($module); ?>&amp;action=new" data-modal-link>
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Add <?= e(strtolower($config['singular'])); ?>
        </a>
    </div>
</div>

<div class="workspace-card">
    <form method="get" class="filter-bar">
        <input type="hidden" name="route" value="<?= e($module); ?>">
        <div class="filter-search">
            <label for="search" class="form-label">Search <?= e(strtolower($title)); ?></label>
            <input id="search" class="form-control" name="q" value="<?= e($search); ?>" maxlength="190" placeholder="Search records">
        </div>
        <div>
            <label for="status-filter" class="form-label">Status</label>
            <select class="form-select" id="status-filter" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $option): ?>
                    <option value="<?= e($option); ?>" <?= $status === $option ? 'selected' : ''; ?>>
                        <?= e(ucwords(str_replace('_', ' ', $option))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-navy" type="submit"><i class="bi bi-filter"></i> Filter</button>
        <a class="btn btn-outline-secondary" href="index.php?route=<?= e($module); ?>">Clear</a>
    </form>

    <?php if (!$listing['rows']): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-inbox fs-1 text-muted" aria-hidden="true"></i>
            <h4 class="mt-3">No <?= e(strtolower($title)); ?> found</h4>
            <p class="text-muted">Add your first <?= e(strtolower($config['singular'])); ?> or adjust your filters.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle operations-table">
            <thead>
                <tr>
                    <?php foreach ($columns as $label): ?>
                        <th scope="col"><?= e($label); ?></th>
                    <?php endforeach; ?>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listing['rows'] as $row): ?>
                <tr>
                    <?php foreach ($columns as $key => $label): ?>
                    <td>
                        <?php if (in_array($key, ['status', 'current_status'], true)): ?>
                            <span class="status-badge status-<?= e($row[$key]); ?>">
                                <?= e(ucwords(str_replace('_', ' ', $row[$key]))); ?>
                            </span>
                        <?php elseif ($key === 'tracking_number'): ?>
                            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $row['id']; ?>">
                                <?= e($row[$key] ?: 'Shipment #' . $row['id']); ?>
                            </a>
                        <?php elseif (in_array($key, ['capacity_kg', 'cargo_weight_kg'], true)): ?>
                            <?= number_format((float) $row[$key], 2); ?> kg
                        <?php else: ?>
                            <?= e($row[$key] ?? '') ?: '&mdash;'; ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <?php if ($module === 'shipments'): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $row['id']; ?>">
                                    View
                                </a>
                            <?php endif; ?>
                            <?php if ($module !== 'shipments' || in_array($row['current_status'], ['pending', 'assigned'], true)): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="index.php?route=<?= e($module); ?>&amp;action=edit&amp;id=<?= (int) $row['id']; ?>" data-modal-link>
                                    Edit
                                </a>
                            <?php endif; ?>
                            <?php if ($module !== 'shipments' || $row['current_status'] === 'pending'): ?>
                                <form method="post" action="index.php?route=<?= e($module); ?>" data-confirm="Delete this <?= e(strtolower($config['singular'])); ?>? This cannot be undone.">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav class="pagination-bar d-flex justify-content-between align-items-center pt-3" aria-label="Record pages">
        <span class="text-muted small">Page <?= $listing['page']; ?> of <?= $listing['pages']; ?> (<?= $listing['total']; ?> entries)</span>
        <div class="d-flex gap-2">
            <?php foreach ([-1 => '&laquo; Previous', 1 => 'Next &raquo;'] as $delta => $label): $target = $listing['page'] + $delta; ?>
                <?php if ($target >= 1 && $target <= $listing['pages']): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?<?= e(http_build_query(['route' => $module, 'q' => $search, 'status' => $status, 'page' => $target])); ?>">
                        <?= $label; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
