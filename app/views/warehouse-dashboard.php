<?php
$title = 'Warehouse Workspace';
$module = 'warehouse';
require __DIR__ . '/partials/header.php';
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Warehouse & Hub Receiving</span>
        <h1>Cargo Arrival &amp; Receiving Portal</h1>
        <p>Monitor approaching transports, verify cargo on arrival, and submit receiving documentation.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=schedule" class="btn btn-outline-secondary">
            <i class="bi bi-calendar3"></i> Arrival Schedules
        </a>
    </div>
</div>

<!-- 1. En Route to Warehouse -->
<div class="workspace-card mb-4">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Approaching Depot</span>
            <h3 class="section-title">Transports In Transit (Incoming)</h3>
        </div>
        <span class="badge bg-primary px-3 py-2"><?= count($incoming); ?> En Route</span>
    </div>

    <?php if (!$incoming): ?>
        <div class="empty-state py-4 text-center">
            <i class="bi bi-truck fs-2 text-muted"></i>
            <h5 class="mt-2 text-dark">No trucks currently in transit to warehouse</h5>
            <p class="text-muted small mb-0">Check schedules or tracking for upcoming shipments.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>Tracking Number</th>
                        <th>Origin &rarr; Destination</th>
                        <th>Cargo &amp; Weight</th>
                        <th>Assigned Truck &amp; Driver</th>
                        <th>Expected Delivery</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incoming as $ship): ?>
                    <tr>
                        <td>
                            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $ship['id']; ?>">
                                <?= e($ship['tracking_number']); ?>
                            </a>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark"><?= e($ship['pickup_location']); ?></div>
                            <div class="small text-muted">&rarr; <?= e($ship['destination']); ?></div>
                        </td>
                        <td>
                            <strong><?= e($ship['cargo_type']); ?></strong>
                            <div class="small text-muted"><?= number_format((float) $ship['cargo_weight_kg'], 2); ?> kg</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= e($ship['plate_number']); ?></span>
                            <div class="small text-muted"><?= e($ship['driver_name']); ?></div>
                        </td>
                        <td><?= e(formatDate($ship['expected_delivery_date'])); ?></td>
                        <td class="text-end">
                            <form method="post" action="index.php?route=warehouse-confirm" data-confirm="Confirm that shipment <?= e($ship['tracking_number']); ?> has arrived at warehouse facility?">
                                <?= csrfField(); ?>
                                <input type="hidden" name="id" value="<?= (int) $ship['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-regc">
                                    <i class="bi bi-check-lg"></i> Confirm Arrival
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 2. Arrived at Warehouse (Awaiting Receiving & POD) -->
<div class="workspace-card mb-4">
    <div class="section-heading">
        <div>
            <span class="eyebrow">At Bay</span>
            <h3 class="section-title">Arrived Cargo (Inspection &amp; Sign-off)</h3>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2"><?= count($arrived); ?> At Facility</span>
    </div>

    <?php if (!$arrived): ?>
        <p class="text-muted mb-0">No cargo currently waiting for receiving inspection.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>Tracking Number</th>
                        <th>Customer</th>
                        <th>Cargo Specifications</th>
                        <th>Transport Vehicle</th>
                        <th>Arrival Timestamp</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arrived as $arr): ?>
                    <tr>
                        <td>
                            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $arr['id']; ?>">
                                <?= e($arr['tracking_number']); ?>
                            </a>
                        </td>
                        <td><?= e($arr['company_name'] ?: $arr['contact_person']); ?></td>
                        <td><?= e($arr['cargo_type']); ?> &middot; <?= number_format((float) $arr['cargo_weight_kg'], 2); ?> kg</td>
                        <td><?= e($arr['plate_number']); ?> &middot; <?= e($arr['driver_name']); ?></td>
                        <td class="text-muted small"><?= e(formatDate($arr['updated_at'])); ?></td>
                        <td class="text-end">
                            <a href="index.php?route=proof-form&amp;shipment_id=<?= (int) $arr['id']; ?>" class="btn btn-sm btn-regc" data-modal-link>
                                <i class="bi bi-file-earmark-check"></i> Submit Receipt &amp; POD
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 3. Recently Received -->
<div class="workspace-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Archived Deliveries</span>
            <h3 class="section-title">Recently Cleared Cargo</h3>
        </div>
    </div>

    <?php if (!$recentlyDelivered): ?>
        <p class="text-muted mb-0">No recent cleared cargo on record.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>Tracking Number</th>
                        <th>Customer</th>
                        <th>Cargo Type</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Completed On</th>
                        <th class="text-end">Proof of Delivery</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentlyDelivered as $rec): ?>
                    <tr>
                        <td>
                            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $rec['id']; ?>">
                                <?= e($rec['tracking_number']); ?>
                            </a>
                        </td>
                        <td><?= e($rec['company_name'] ?: $rec['contact_person']); ?></td>
                        <td><?= e($rec['cargo_type']); ?></td>
                        <td><?= e($rec['plate_number']); ?></td>
                        <td><span class="status-badge status-delivered">Delivered</span></td>
                        <td class="text-muted small"><?= e(formatDate($rec['updated_at'])); ?></td>
                        <td class="text-end">
                            <a href="index.php?route=proof-view&amp;shipment_id=<?= (int) $rec['id']; ?>" class="btn btn-sm btn-outline-secondary" data-modal-link>
                                <i class="bi bi-file-earmark-image"></i> View POD
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>

