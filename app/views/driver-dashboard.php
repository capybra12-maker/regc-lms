<?php
$title = 'Driver Workspace';
$module = 'driver';
require __DIR__ . '/partials/header.php';
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Fleet Operations</span>
        <h1>Driver Delivery Portal</h1>
        <p>Follow your dispatched routes, advance checkpoint statuses, and submit proof of delivery.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (\hasRole('administrator')): ?>
        <a href="index.php?route=driver-map" class="btn btn-outline-secondary">
            <i class="bi bi-map"></i> Fleet GPS Map
        </a>
        <?php endif; ?>
        <a href="index.php?route=schedule" class="btn btn-outline-secondary">
            <i class="bi bi-calendar3"></i> My Schedule
        </a>
        <?php if (\hasRole('driver')): ?>
        <span id="gps-badge" class="badge bg-secondary align-self-center" style="font-size:12px; padding:8px 12px;">
            <i class="bi bi-wifi-off me-1"></i> GPS Off
        </span>
        <?php endif; ?>
    </div>
</div>

<!-- Assigned Truck Summary (if any) -->
<?php if ($assignedTruck): ?>
<div class="workspace-card p-3 mb-4 bg-light border d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <div class="stat-icon icon-navy" style="width: 44px; height: 44px; font-size: 20px;">
            <i class="bi bi-truck"></i>
        </div>
        <div>
            <strong class="d-block text-dark fs-6"><?= e($assignedTruck['plate_number']); ?> &middot; <?= e($assignedTruck['truck_model']); ?> (<?= e($assignedTruck['truck_type']); ?>)</strong>
            <small class="text-muted">Capacity: <?= number_format((float) $assignedTruck['capacity_kg'], 2); ?> kg &middot; Status: <span class="badge bg-secondary text-capitalize"><?= e(str_replace('_', ' ', $assignedTruck['status'])); ?></span></small>
        </div>
    </div>
    <span class="badge bg-success px-3 py-2 fs-6">Assigned Vehicle</span>
</div>
<?php endif; ?>

<!-- Active Assignments -->
<div class="workspace-card mb-4">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Assigned Work</span>
            <h3 class="section-title">Active Delivery Jobs</h3>
        </div>
    </div>

    <?php if (!$activeShipments): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-check2-circle fs-1 text-success"></i>
            <h4 class="mt-3">No pending or active delivery jobs</h4>
            <p class="text-muted">You are currently available for new dispatch assignments.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($activeShipments as $job): 
                $step = array_search($job['current_status'], Operations::STATUSES, true);
                $next = Operations::STATUSES[$step + 1] ?? null;
            ?>
            <div class="p-3 p-md-4 rounded-3 border bg-white shadow-sm">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <span class="eyebrow">Tracking #</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= e($job['tracking_number']); ?></h4>
                    </div>
                    <span class="status-badge status-<?= e($job['current_status']); ?>">
                        <?= e(ucwords(str_replace('_', ' ', $job['current_status']))); ?>
                    </span>
                </div>

                <div class="shipment-route my-3">
                    <div>
                        <small>Pickup Point</small>
                        <strong><?= e($job['pickup_location']); ?></strong>
                    </div>
                    <i class="bi bi-arrow-right"></i>
                    <div>
                        <small>Destination Warehouse</small>
                        <strong><?= e($job['destination']); ?></strong>
                    </div>
                </div>

                <dl class="detail-grid mb-3">
                    <div><dt>Customer</dt><dd><?= e($job['company_name'] ?: $job['contact_person']); ?></dd></div>
                    <div><dt>Contact Phone</dt><dd><?= e($job['contact_number'] ?: 'N/A'); ?></dd></div>
                    <div><dt>Cargo</dt><dd><?= e($job['cargo_type']); ?> (<?= number_format((float) $job['cargo_weight_kg'], 2); ?> kg)</dd></div>
                    <div><dt>Truck Plate</dt><dd><?= e($job['plate_number'] ?? 'Unassigned'); ?></dd></div>
                </dl>

                <div class="d-flex gap-2 flex-wrap pt-3 border-top justify-content-end">
                    <a href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $job['id']; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye"></i> Full Details
                    </a>

                    <?php if ($job['current_status'] === 'arrived'): ?>
                        <a href="index.php?route=proof-form&amp;shipment_id=<?= (int) $job['id']; ?>" class="btn btn-regc btn-sm" data-modal-link>
                            <i class="bi bi-upload"></i> Upload POD &amp; Complete
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Completed History -->
<div class="workspace-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Past Assignments</span>
            <h3 class="section-title">Completed Delivery History</h3>
        </div>
    </div>

    <?php if (!$completedShipments): ?>
        <p class="text-muted mb-0">No past delivered shipments on record.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>Tracking Number</th>
                        <th>Customer</th>
                        <th>Cargo</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Delivered On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedShipments as $cShip): ?>
                    <tr>
                        <td>
                            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $cShip['id']; ?>">
                                <?= e($cShip['tracking_number']); ?>
                            </a>
                        </td>
                        <td><?= e($cShip['company_name'] ?: $cShip['contact_person']); ?></td>
                        <td><?= e($cShip['cargo_type']); ?> (<?= number_format((float) $cShip['cargo_weight_kg'], 2); ?> kg)</td>
                        <td><?= e($cShip['plate_number']); ?></td>
                        <td><span class="status-badge status-delivered">Delivered</span></td>
                        <td class="text-muted small"><?= e(formatDate($cShip['updated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (\hasRole('driver')): ?>
<script>
// Driver GPS Auto-Beacon — updates position every 30s
(function() {
    var badge = document.getElementById('gps-badge');
    var csrf = <?= json_encode(csrfToken()); ?>;

    function sendPosition(lat, lng) {
        fetch('index.php?route=driver-gps', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': 'regc-gps-live' },
            body: JSON.stringify({
                lat: parseFloat(lat),
                lng: parseFloat(lng),
                api_key: 'regc-gps-live'
            })
        }).then(r => r.json()).then(data => {
            if (data.ok && badge) {
                badge.className = 'badge bg-success align-self-center';
                badge.innerHTML = '<i class="bi bi-broadcast me-1"></i> GPS Live';
            }
        }).catch(() => {
            if (badge) {
                badge.className = 'badge bg-warning align-self-center';
                badge.innerHTML = '<i class="bi bi-wifi me-1"></i> GPS Error';
            }
        });
    }

    function pollGps() {
        if (!navigator.geolocation) {
            if (badge) { badge.className = 'badge bg-secondary align-self-center'; badge.innerHTML = '<i class="bi bi-wifi-off me-1"></i> GPS N/A'; }
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                sendPosition(pos.coords.latitude.toFixed(8), pos.coords.longitude.toFixed(8));
            },
            function(err) {
                if (badge) {
                    badge.className = 'badge bg-secondary align-self-center';
                    badge.innerHTML = '<i class="bi bi-wifi-off me-1"></i> GPS Denied';
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 15000 }
        );
    }

    pollGps();
    setInterval(pollGps, 30000);
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

