<?php
$title = 'Fleet GPS Tracker';
$module = 'driver';
require __DIR__ . '/partials/header.php';
$driversJson = json_encode($drivers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Fleet Operations</span>
        <h1>Live Fleet GPS Tracker</h1>
        <p>Real-time driver positions and delivery routes powered by GPS telematics.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-warning btn-sm d-flex align-items-center gap-1 fw-bold text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#cpGpsModal">
            <i class="bi bi-phone"></i> Connect Cellphone (CP)
        </button>
        <button id="btn-refresh-gps" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-arrow-clockwise"></i> Refresh GPS
        </button>
        <a href="index.php?route=driver" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Driver Portal
        </a>
    </div>
</div>

<!-- Map Legend and GPS Status Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 p-3 bg-white rounded-3 border">
    <div class="d-flex flex-wrap gap-3">
        <span class="d-flex align-items-center gap-2 small">
            <span style="width:14px;height:14px;border-radius:50%;background:#22c55e;display:inline-block;box-shadow: 0 0 6px rgba(34,197,94,0.6);"></span> On Delivery (Live GPS)
        </span>
        <span class="d-flex align-items-center gap-2 small">
            <span style="width:14px;height:14px;border-radius:50%;background:#f59e0b;display:inline-block;"></span> Available / In Depot
        </span>
        <span class="d-flex align-items-center gap-2 small">
            <span style="width:14px;height:14px;border-radius:50%;background:#3b82f6;display:inline-block;"></span> Pickup Origin
        </span>
        <span class="d-flex align-items-center gap-2 small">
            <span style="width:14px;height:14px;border-radius:50%;background:#ef4444;display:inline-block;"></span> Destination Warehouse
        </span>
    </div>
    <div class="small text-muted d-flex align-items-center gap-2">
        <span class="badge bg-success" id="telematics-badge"><i class="bi bi-broadcast me-1"></i> Telematics Active</span>
        <span id="last-sync-time">Synced just now</span>
    </div>
</div>

<!-- Live Interactive Map Container -->
<div class="workspace-card p-0 mb-4 overflow-hidden shadow-sm" style="height: 540px; position: relative;">
    <div id="fleet-map" style="width:100%; height:100%; z-index: 1;"></div>
    <div id="map-loading" style="display:none; position:absolute; top:12px; right:12px; z-index:1000; background:rgba(255,255,255,0.9); padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; box-shadow:0 2px 10px rgba(0,0,0,0.15);">
        <span class="spinner-border spinner-border-sm text-warning me-1" role="status"></span> Updating live signals...
    </div>
</div>

<!-- API Integration Helper Modal / Card -->
<div class="workspace-card p-3 mb-4 bg-light border">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong class="d-block text-dark"><i class="bi bi-cpu-fill text-warning me-1"></i> External GPS Tracker &amp; Mobile API</strong>
            <small class="text-muted">Hardware GPS devices (Teltonika, Traccar, etc.) or mobile apps can send real-time coordinates to this endpoint.</small>
        </div>
        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#api-docs-collapse">
            <i class="bi bi-code-slash"></i> View GPS API Docs
        </button>
    </div>
    <div class="collapse mt-3" id="api-docs-collapse">
        <div class="p-3 bg-white rounded border small">
            <h6 class="fw-bold mb-2">GPS Telematics Webhook Specification</h6>
            <p class="mb-2">Send an HTTP POST request to update any driver's live GPS coordinates:</p>
            <pre class="bg-dark text-light p-3 rounded mb-2"><code>POST <?= e(APP_URL ?: 'http://localhost/regc-lms'); ?>/index.php?route=driver-gps
Content-Type: application/json
X-API-Key: regc-gps-live

{
  "driver_id": 1,
  "lat": 6.116400,
  "lng": 125.171600
}</code></pre>
            <div class="text-muted">Drivers logged in to the mobile driver portal also transmit their position automatically via the HTML5 Geolocation Beacon every 30 seconds.</div>
        </div>
    </div>
</div>

<!-- Driver Fleet Cards Grid -->
<div class="row g-3">
    <?php foreach ($drivers as $d): ?>
    <div class="col-md-4 col-lg-3">
        <div class="workspace-card p-3 h-100 driver-map-card" data-driver-id="<?= (int) $d['id']; ?>" style="cursor:pointer; transition: all 0.2s;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar" style="width:36px;height:36px;font-size:13px;"><?= e(mb_strtoupper(mb_substr($d['full_name'], 0, 1))); ?></div>
                    <div>
                        <strong class="d-block text-dark small mb-0"><?= e($d['full_name']); ?></strong>
                        <span class="status-badge status-<?= e($d['status']); ?>" style="font-size:10px;"><?= e(str_replace('_', ' ', $d['status'])); ?></span>
                    </div>
                </div>
            </div>
            <?php if ($d['tracking_number']): ?>
            <div class="small p-2 bg-light rounded border mb-2">
                <div class="text-truncate fw-bold text-dark"><i class="bi bi-box-seam me-1 text-warning"></i><?= e($d['tracking_number']); ?></div>
                <div class="text-truncate text-muted" style="font-size:11px;"><i class="bi bi-geo-alt me-1"></i><?= e($d['destination'] ?: 'Destination'); ?></div>
            </div>
            <?php else: ?>
            <div class="small text-muted p-2 bg-light rounded border mb-2">No active shipment assigned</div>
            <?php endif; ?>
            <?php if ($d['gps_lat'] && $d['gps_lng']): ?>
            <div class="small text-success">
                <i class="bi bi-broadcast me-1"></i>
                <?= number_format((float)$d['gps_lat'], 5); ?>, <?= number_format((float)$d['gps_lng'], 5); ?>
                <br><span class="text-muted" style="font-size:10.5px;">Last Signal: <?= e($d['gps_updated_at'] ? formatDate($d['gps_updated_at']) : 'Active'); ?></span>
            </div>
            <?php else: ?>
            <div class="small text-warning"><i class="bi bi-wifi-off me-1"></i> Awaiting first GPS beacon</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$drivers): ?>
    <div class="col-12">
        <div class="empty-state py-5 text-center">
            <i class="bi bi-truck fs-1 text-warning mb-3 d-block"></i>
            <h4>No active drivers</h4>
            <p class="text-muted">Drivers added in fleet operations will appear here.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var drivers = <?= $driversJson; ?>;
    var map;
    var markers = {};
    var polylines = [];

    // Initialize Map with Philippines/Mindanao default center (General Santos City)
    var defaultLat = 6.1164;
    var defaultLng = 125.1716;

    map = L.map('fleet-map').setView([defaultLat, defaultLng], 11);

    // High quality OpenStreetMap tiles (free, instant, zero API key required)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors | REGC Fleet GPS'
    }).addTo(map);

    function createDriverIcon(color, initial) {
        return L.divIcon({
            className: 'regc-driver-marker',
            html: '<div style="background:' + color + ';width:32px;height:32px;border-radius:50%;border:3px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:13px;">' + initial + '</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -18]
        });
    }

    function renderDriversOnMap(data) {
        // Clear previous markers & polylines
        Object.values(markers).forEach(m => map.removeLayer(m));
        polylines.forEach(p => map.removeLayer(p));
        markers = {};
        polylines = [];

        var bounds = [];

        data.forEach(function(driver) {
            var hasGps = driver.gps_lat && driver.gps_lng;
            var lat = hasGps ? parseFloat(driver.gps_lat) : null;
            var lng = hasGps ? parseFloat(driver.gps_lng) : null;

            if (lat && lng) {
                var color = driver.status === 'on_delivery' ? '#22c55e' : '#f59e0b';
                var initial = (driver.full_name || 'D').charAt(0).toUpperCase();
                var marker = L.marker([lat, lng], { icon: createDriverIcon(color, initial) }).addTo(map);

                var popupHtml = '<div style="font-family:Inter,sans-serif;min-width:200px;">' +
                    '<div style="font-weight:700;font-size:14px;margin-bottom:4px;color:#0e1726;">' + driver.full_name + '</div>' +
                    '<div style="font-size:12px;margin-bottom:6px;"><span class="badge bg-secondary">' + driver.status.replace('_', ' ') + '</span></div>';

                if (driver.tracking_number) {
                    popupHtml += '<div style="font-size:12px;color:#333;margin-bottom:4px;"><strong>Shipment:</strong> ' + driver.tracking_number + '</div>' +
                        '<div style="font-size:12px;color:#666;"><strong>To:</strong> ' + (driver.destination || 'N/A') + '</div>';

                    // If pickup and destination coordinates exist, draw real-time route polyline
                    if (driver.pickup_lat && driver.destination_lat) {
                        var routePoints = [
                            [parseFloat(driver.pickup_lat), parseFloat(driver.pickup_lng)],
                            [lat, lng],
                            [parseFloat(driver.destination_lat), parseFloat(driver.destination_lng)]
                        ];
                        var line = L.polyline(routePoints, {
                            color: '#f5c842',
                            weight: 4,
                            opacity: 0.85,
                            dashArray: '8, 8'
                        }).addTo(map);
                        polylines.push(line);
                    }
                }

                popupHtml += '<div style="margin-top:6px;font-size:11px;color:#888;">GPS: ' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</div>' +
                    '<div style="font-size:10px;color:#aaa;">Updated: ' + (driver.gps_updated_at || 'Live') + '</div>' +
                    '</div>';

                marker.bindPopup(popupHtml);
                markers[driver.id] = marker;
                bounds.push([lat, lng]);
            }

            // Mark pickup and destination if available
            if (driver.pickup_lat && driver.pickup_lng) {
                var pLat = parseFloat(driver.pickup_lat);
                var pLng = parseFloat(driver.pickup_lng);
                var pMarker = L.circleMarker([pLat, pLng], {
                    radius: 7,
                    fillColor: '#3b82f6',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                }).addTo(map).bindPopup('<strong>Pickup:</strong> ' + (driver.pickup_location || 'Origin'));
                bounds.push([pLat, pLng]);
            }

            if (driver.destination_lat && driver.destination_lng) {
                var dLat = parseFloat(driver.destination_lat);
                var dLng = parseFloat(driver.destination_lng);
                var dMarker = L.circleMarker([dLat, dLng], {
                    radius: 7,
                    fillColor: '#ef4444',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                }).addTo(map).bindPopup('<strong>Destination:</strong> ' + (driver.destination || 'Destination'));
                bounds.push([dLat, dLng]);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
        }
    }

    renderDriversOnMap(drivers);

    // Card click focuses marker
    document.querySelectorAll('.driver-map-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var id = parseInt(card.dataset.driverId);
            if (markers[id]) {
                var pos = markers[id].getLatLng();
                map.setView(pos, 15);
                markers[id].openPopup();
            }
        });
    });

    // Auto-refresh GPS telematics every 15s
    var refreshBtn = document.getElementById('btn-refresh-gps');
    var loadingEl = document.getElementById('map-loading');
    var syncTimeEl = document.getElementById('last-sync-time');

    function refreshGpsPositions() {
        if (loadingEl) loadingEl.style.display = 'block';
        fetch('index.php?route=driver-gps-positions')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok && Array.isArray(data.drivers)) {
                    // Clear old markers and polylines
                    Object.values(markers).forEach(function(m) { map.removeLayer(m); });
                    polylines.forEach(function(p) { map.removeLayer(p); });
                    markers = {};
                    polylines = [];
                    renderDriversOnMap(data.drivers);
                }
                if (syncTimeEl) syncTimeEl.textContent = 'Synced ' + new Date().toLocaleTimeString();
                if (loadingEl) loadingEl.style.display = 'none';
            })
            .catch(function() {
                if (loadingEl) loadingEl.style.display = 'none';
            });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshGpsPositions);
    }

    setInterval(refreshGpsPositions, 15000);
})();
</script>

<!-- Connect Cellphone (CP) Modal -->
<div class="modal fade" id="cpGpsModal" tabindex="-1" aria-labelledby="cpGpsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="cpGpsModalLabel">
                    <i class="bi bi-broadcast text-warning me-2"></i> Connect Driver Phone (Mobile Data &amp; Wi-Fi)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">

                <?php
                // Use the live domain — no tunnel needed on production
                $mobileGpsUrl = rtrim(APP_URL, '/') . '/index.php?route=mobile-gps';
                $activeQr = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($mobileGpsUrl);
                ?>

                <!-- Live Domain Status Alert -->
                <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                        <div>
                            <strong class="d-block">Live Domain — Accessible Anywhere!</strong>
                            <span class="small text-muted">Drivers can use this link on any network: Globe, Smart, DITO, Wi-Fi.</span>
                        </div>
                    </div>
                    <span class="badge bg-success font-monospace px-2 py-1">🔒 HTTPS Secured</span>
                </div>

                <div class="row g-4 align-items-center">
                    <!-- QR Code Column -->
                    <div class="col-md-5 text-center">
                        <div class="p-3 bg-light rounded-3 d-inline-block border shadow-sm">
                            <img src="<?= e($activeQr); ?>" alt="Scan QR Code with Phone" style="width: 190px; height: 190px;" class="d-block mx-auto">
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-camera me-1"></i> Scan with driver's phone camera
                        </small>
                    </div>

                    <!-- Instructions and Links Column -->
                    <div class="col-md-7">
                        <!-- Primary Cellular Link -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark d-flex justify-content-between">
                                <span><i class="bi bi-phone-fill text-success me-1"></i> Mobile Data Link (Cellular / Load):</span>
                                <?php if ($tunnelBase): ?>
                                    <span class="text-success small"><i class="bi bi-dot"></i>Live Anywhere</span>
                                <?php endif; ?>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm font-monospace" value="<?= e($mobileGpsUrl); ?>" id="cp-cellular-input" readonly>
                                <button class="btn btn-outline-success btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cp-cellular-input').value); alert('Link copied! Send to driver via SMS, Messenger, or Viber.');">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Send this link to your driver via text message, Messenger, or Viber.
                            </small>
                        </div>

                        <!-- Driver Features Highlights -->
                        <div class="p-3 rounded-3 bg-light border small">
                            <strong class="d-block text-dark mb-2"><i class="bi bi-stars text-warning me-1"></i> Built for Drivers on the Road:</strong>
                            <ul class="mb-0 ps-3 text-secondary" style="font-size: 12px; line-height: 1.6;">
                                <li><strong>Screen Wake Lock:</strong> Phone screen stays on while mounted in the cab so GPS doesn't sleep.</li>
                                <li><strong>Data-Saver Engine:</strong> Throttles updates when stopped in traffic to save 85% of driver's mobile data/load.</li>
                                <li><strong>Zero-Loss Queue:</strong> Buffers coordinates offline if signal drops in tunnels and auto-syncs upon reconnect.</li>
                                <li><strong>1-Tap Milestones:</strong> Driver can advance trip status right on the GPS screen.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div class="small text-muted">
                    Double-click <code>start-driver-gps-tunnel.bat</code> in the project folder to start the gateway anytime.
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= e($activeUrl); ?>" target="_blank" class="btn btn-outline-dark btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Test Transmitter
                    </a>
                    <button type="button" class="btn btn-regc btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
