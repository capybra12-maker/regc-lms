<?php
require_once __DIR__ . '/../../models/Proof.php';
$podDoc = Proof::forShipment((int) $shipment['id']);
?>

<div class="shipment-summary workspace-card mb-4">
    <div class="summary-heading">
        <div>
            <span class="eyebrow">Tracking Number</span>
            <h2><?= e($shipment['tracking_number'] ?: 'Shipment #' . $shipment['id']); ?></h2>
        </div>
        <span class="status-badge status-<?= e($shipment['current_status']); ?>">
            <?= e(ucwords(str_replace('_', ' ', $shipment['current_status']))); ?>
        </span>
    </div>

    <ol class="delivery-progress" aria-label="Delivery progress">
        <?php $currentStep = array_search($shipment['current_status'], Operations::STATUSES, true); foreach (Operations::STATUSES as $step => $stepStatus): ?>
            <li class="<?= $step <= $currentStep ? 'complete' : ''; ?>" <?= $step === $currentStep ? 'aria-current="step"' : ''; ?>>
                <span><?= $step + 1; ?></span><?= e(ucwords(str_replace('_', ' ', $stepStatus))); ?>
            </li>
        <?php endforeach; ?>
    </ol>

    <div class="shipment-route">
        <div>
            <small>Origin / Pickup Pier</small>
            <strong><?= e($shipment['pickup_location']); ?></strong>
        </div>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
        <div>
            <small>Destination Warehouse</small>
            <strong><?= e($shipment['destination']); ?></strong>
        </div>
    </div>

    <!-- Live Real-Time Route & GPS Tracker Map -->
    <div class="mt-3 mb-4 rounded-3 overflow-hidden border bg-white" style="position: relative;">
        <div class="d-flex justify-content-between align-items-center p-2 px-3 bg-light border-bottom">
            <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                <i class="bi bi-geo-alt-fill text-warning"></i> Real-Time Delivery Route &amp; GPS Telematics
            </span>
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($shipment['gps_lat']) && !empty($shipment['gps_lng'])): ?>
                    <span class="badge bg-success" style="font-size: 11px;">
                        <i class="bi bi-broadcast me-1"></i> Driver Live GPS: <?= number_format((float)$shipment['gps_lat'], 4); ?>, <?= number_format((float)$shipment['gps_lng'], 4); ?>
                    </span>
                <?php elseif (in_array($shipment['current_status'], ['pickup', 'in_transit', 'arrived'], true)): ?>
                    <span class="badge bg-warning text-dark" style="font-size: 11px;">
                        <i class="bi bi-truck me-1"></i> In Transit
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary" style="font-size: 11px;">
                        Planned Route
                    </span>
                <?php endif; ?>
                <a href="https://www.google.com/maps/dir/?api=1&origin=<?= urlencode($shipment['pickup_location']); ?>&destination=<?= urlencode($shipment['destination']); ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary" style="font-size: 11px; padding: 2px 8px;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Google Maps
                </a>
            </div>
        </div>
        <div id="shipment-live-map" style="width: 100%; height: 260px; background: #f8fafc;"></div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function() {
        var mapEl = document.getElementById('shipment-live-map');
        if (!mapEl) return;

        var pLat = <?= json_encode(!empty($shipment['pickup_lat']) ? (float)$shipment['pickup_lat'] : null); ?>;
        var pLng = <?= json_encode(!empty($shipment['pickup_lng']) ? (float)$shipment['pickup_lng'] : null); ?>;
        var dLat = <?= json_encode(!empty($shipment['destination_lat']) ? (float)$shipment['destination_lat'] : null); ?>;
        var dLng = <?= json_encode(!empty($shipment['destination_lng']) ? (float)$shipment['destination_lng'] : null); ?>;
        var gLat = <?= json_encode(!empty($shipment['gps_lat']) ? (float)$shipment['gps_lat'] : null); ?>;
        var gLng = <?= json_encode(!empty($shipment['gps_lng']) ? (float)$shipment['gps_lng'] : null); ?>;

        var originName = <?= json_encode($shipment['pickup_location']); ?>;
        var destName = <?= json_encode($shipment['destination']); ?>;
        var driverName = <?= json_encode($shipment['driver_name'] ?? 'Driver'); ?>;

        // Default to Tagoloan, Misamis Oriental
        var centerLat = gLat || pLat || dLat || 8.5333;
        var centerLng = gLng || pLng || dLng || 124.7333;

        var sMap = L.map('shipment-live-map').setView([centerLat, centerLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap | REGC Live GPS'
        }).addTo(sMap);

        var bounds = [];

        // Pickup Marker
        if (pLat && pLng) {
            L.circleMarker([pLat, pLng], {
                radius: 8,
                fillColor: '#3b82f6',
                color: '#fff',
                weight: 2,
                fillOpacity: 1
            }).addTo(sMap).bindPopup('<strong>Origin:</strong> ' + originName);
            bounds.push([pLat, pLng]);
        }

        // Destination Marker
        if (dLat && dLng) {
            L.circleMarker([dLat, dLng], {
                radius: 8,
                fillColor: '#ef4444',
                color: '#fff',
                weight: 2,
                fillOpacity: 1
            }).addTo(sMap).bindPopup('<strong>Destination:</strong> ' + destName);
            bounds.push([dLat, dLng]);
        }

        // Live Driver Marker
        if (gLat && gLng) {
            var truckIcon = L.divIcon({
                className: 'live-truck-marker',
                html: '<div style="background:#22c55e;width:34px;height:34px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 12px rgba(34,197,94,0.8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;"><i class="bi bi-truck"></i></div>',
                iconSize: [34, 34],
                iconAnchor: [17, 17]
            });
            L.marker([gLat, gLng], { icon: truckIcon }).addTo(sMap).bindPopup('<strong>Live Driver:</strong> ' + driverName).openPopup();
            bounds.push([gLat, gLng]);
        }

        // Draw connecting route line
        if (bounds.length >= 2) {
            L.polyline(bounds, {
                color: '#f5c842',
                weight: 4,
                opacity: 0.85,
                dashArray: '6, 6'
            }).addTo(sMap);
            sMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
        } else if (bounds.length === 1) {
            sMap.setView(bounds[0], 13);
        }
    })();
    </script>

    <dl class="detail-grid">
        <div><dt>Customer</dt><dd><?= e($shipment['company_name'] ?: $shipment['contact_person']); ?></dd></div>
        <div><dt>Contact Person</dt><dd><?= e($shipment['contact_person']); ?></dd></div>
        <div><dt>Cargo Type &amp; Weight</dt><dd><?= e($shipment['cargo_type']); ?> &middot; <?= number_format((float) $shipment['cargo_weight_kg'], 2); ?> kg</dd></div>
        <div><dt>Assigned Vehicle</dt><dd><?= e($shipment['plate_number'] ?? 'Unassigned'); ?></dd></div>
        <div><dt>Assigned Driver</dt><dd><?= e($shipment['driver_name'] ?? 'Unassigned'); ?></dd></div>
        <div><dt>Pickup Datetime</dt><dd><?= e(formatDate($shipment['shipment_date'])); ?></dd></div>
        <div><dt>Expected Delivery</dt><dd><?= e(formatDate($shipment['expected_delivery_date'])); ?></dd></div>
        <div><dt>Freight Amount</dt><dd class="text-success fw-bold"><?= $shipment['freight_amount'] ? '₱' . number_format((float) $shipment['freight_amount'], 2) : 'TBD'; ?></dd></div>
        <div><dt>Registered Date</dt><dd><?= e(formatDate($shipment['created_at'])); ?></dd></div>
    </dl>

    <?php if (!empty($shipment['cargo_description'])): ?>
        <div class="detail-notes">
            <strong>Cargo Description</strong>
            <p><?= nl2br(e($shipment['cargo_description'])); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($shipment['notes'])): ?>
        <div class="detail-notes">
            <strong>Handling &amp; Dispatch Notes</strong>
            <p><?= nl2br(e($shipment['notes'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Proof of Delivery Section -->
    <?php if ($podDoc): ?>
        <div class="p-3 mt-4 rounded-3 border bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="badge bg-success mb-1"><i class="bi bi-shield-check"></i> Proof of Delivery Submitted</span>
                <div class="small text-dark fw-bold">Received by: <?= e($podDoc['received_by']); ?> on <?= e(formatDate($podDoc['uploaded_at'])); ?></div>
            </div>
            <a href="index.php?route=proof-view&amp;shipment_id=<?= (int) $shipment['id']; ?>" class="btn btn-outline-dark btn-sm" data-modal-link>
                <i class="bi bi-file-earmark-image"></i> View Verified POD
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="workspace-card">
    <div class="section-heading">
        <h3 class="section-title">Tracking History</h3>
        <small class="text-muted">Chronological audit log &middot; <?= e(APP_TIMEZONE); ?></small>
    </div>

    <?php if (!$history): ?>
        <p class="text-muted mb-0">No updates have been recorded yet.</p>
    <?php else: ?>
        <ol class="tracking-timeline">
            <?php foreach ($history as $entry): ?>
            <li>
                <div class="timeline-heading">
                    <strong><?= e(ucwords(str_replace('_', ' ', $entry['status']))); ?></strong>
                    <time datetime="<?= e(date('c', strtotime($entry['created_at']))); ?>"><?= e(formatDate($entry['created_at'])); ?></time>
                </div>
                <p><?= nl2br(e($entry['remarks'])); ?></p>
                <small class="text-muted">Recorded by <?= e($entry['updated_by_name']); ?></small>
            </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
