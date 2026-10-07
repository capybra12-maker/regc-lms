<?php
$title = 'Delivery Schedules';
$module = 'schedule';
require __DIR__ . '/../partials/header.php';
$isAdmin = hasRole('administrator');
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Dispatch Scheduling</span>
        <h1>Delivery Schedules</h1>
        <p>Operational timeline, route dispatch plans, and automated conflict detection.</p>
    </div>
    <?php if ($isAdmin): ?>
    <a class="btn btn-regc" href="index.php?route=schedule-form" data-modal-link>
        <i class="bi bi-calendar-plus"></i> Schedule Delivery
    </a>
    <?php endif; ?>
</div>

<div class="workspace-card">
    <form method="get" class="filter-bar mb-4">
        <input type="hidden" name="route" value="schedule">
        <div>
            <label class="form-label" for="start_date">From Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate); ?>">
        </div>
        <div>
            <label class="form-label" for="end_date">To Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDate); ?>">
        </div>
        <button class="btn btn-navy" type="submit"><i class="bi bi-filter"></i> Filter</button>
        <a class="btn btn-outline-secondary" href="index.php?route=schedule">Current Window</a>
    </form>

    <?php if (!$schedules): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-calendar-x fs-1 text-muted"></i>
            <h4 class="mt-3">No delivery schedules in this timeframe</h4>
            <p class="text-muted">Schedule trips to organize trucks, drivers, and pickup windows.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>Pickup Datetime</th>
                        <th>Shipment</th>
                        <th>Cargo Specifications</th>
                        <th>Route Plan</th>
                        <th>Assigned Vehicle</th>
                        <th>Assigned Driver</th>
                        <th>Status</th>
                        <?php if ($isAdmin): ?><th class="text-end">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $sched): ?>
                    <tr>
                        <td>
                            <strong class="text-dark d-block"><?= e(date('M d, Y', strtotime($sched['pickup_datetime']))); ?></strong>
                            <small class="text-muted"><?= e(date('g:i A', strtotime($sched['pickup_datetime']))); ?></small>
                        </td>
                        <td>
                            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $sched['shipment_id']; ?>">
                                <?= e($sched['tracking_number']); ?>
                            </a>
                        </td>
                        <td>
                            <strong><?= e($sched['cargo_type']); ?></strong>
                            <div class="small text-muted"><?= number_format((float) $sched['cargo_weight_kg'], 1); ?> kg</div>
                        </td>
                        <td>
                            <div class="small text-dark"><?= e($sched['pickup_location']); ?></div>
                            <div class="small text-muted">&rarr; <?= e($sched['destination']); ?></div>
                        </td>
                        <td>
                            <strong><?= e($sched['plate_number']); ?></strong>
                            <div class="small text-muted"><?= e($sched['truck_model']); ?></div>
                        </td>
                        <td>
                            <div><?= e($sched['driver_name']); ?></div>
                            <small class="text-muted"><?= e($sched['driver_phone'] ?: 'N/A'); ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?= e($sched['current_status']); ?>">
                                <?= e(ucwords(str_replace('_', ' ', $sched['current_status']))); ?>
                            </span>
                        </td>
                        <?php if ($isAdmin): ?>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="index.php?route=schedule-form&amp;id=<?= (int) $sched['id']; ?>" data-modal-link>
                                Edit
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

