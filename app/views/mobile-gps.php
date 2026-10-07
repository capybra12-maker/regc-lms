<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0e1726">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Driver Mobile Telematics | REGC Fleet Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root {
            --navy: #0e1726;
            --navy-dark: #080d16;
            --gold: #f5c842;
            --green: #22c55e;
            --red: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #080d16;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 24px;
            -webkit-user-select: none;
            user-select: none;
        }
        .header-bar {
            background: rgba(14, 23, 38, 0.95);
            padding: 12px 16px;
            border-bottom: 1px solid rgba(245, 200, 66, 0.25);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .radar-box {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 16px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .radar-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid var(--green);
            opacity: 0;
            pointer-events: none;
        }
        .broadcasting .radar-ring:nth-child(1) {
            animation: radarPulse 2.4s ease-out infinite;
        }
        .broadcasting .radar-ring:nth-child(2) {
            animation: radarPulse 2.4s ease-out 0.8s infinite;
        }
        .broadcasting .radar-ring:nth-child(3) {
            animation: radarPulse 2.4s ease-out 1.6s infinite;
        }
        @keyframes radarPulse {
            0% { transform: scale(0.35); opacity: 1; }
            100% { transform: scale(1.65); opacity: 0; }
        }
        .radar-center-btn {
            position: relative;
            z-index: 2;
            width: 112px;
            height: 112px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.2);
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            font-weight: 900;
            font-size: 13px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.6);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }
        .radar-center-btn:active {
            transform: scale(0.94);
        }
        .radar-center-btn.active {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 0 35px rgba(239, 68, 68, 0.7);
            border-color: rgba(255, 255, 255, 0.4);
        }
        .card-panel {
            background: rgba(14, 23, 38, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 14px;
        }
        .stat-badge-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 10px;
            text-align: center;
        }
        .stat-badge-val {
            font-size: 19px;
            font-weight: 800;
            color: var(--gold);
            font-variant-numeric: tabular-nums;
        }
        .stat-badge-lbl {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.55);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        #mobile-map {
            width: 100%;
            height: 200px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .milestone-btn {
            font-weight: 700;
            padding: 10px 14px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .milestone-btn:active {
            transform: scale(0.97);
        }
        .net-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- Header Sticky Bar -->
<div class="header-bar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-truck text-warning fs-4"></i>
        <div>
            <strong class="d-block" style="font-size: 15px; line-height: 1.2;">REGC Driver GPS</strong>
            <small class="text-secondary" style="font-size: 11px;">Mobile Cellular Telematics</small>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="net-badge bg-secondary" id="conn-pill">Idle</span>
        <span class="net-badge bg-dark border border-secondary text-light" id="battery-pill" title="Phone Battery">🔋 --%</span>
    </div>
</div>

<div class="container py-3" style="max-width: 520px;">

    <!-- Network / Gateway Info Banner -->
    <div class="mb-3 p-2 px-3 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.25);">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-broadcast text-success fs-5"></i>
            <div>
                <span class="small fw-bold text-success d-block">Cellular Data / Load Compatible</span>
                <span class="text-secondary" style="font-size: 11px;">
                    <?php if ($publicTunnelUrl): ?>
                        Secure Public Tunnel Active (HTTPS) &middot; Live anywhere in PH
                    <?php else: ?>
                        Host: <?= e($serverIp); ?> &middot; Ready for Local &amp; Mobile Data
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <span class="badge bg-success" style="font-size: 10px;"><i class="bi bi-shield-check me-1"></i>HTTPS Ready</span>
    </div>

    <!-- Driver Selector -->
    <div class="card-panel mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label small text-secondary mb-0 fw-bold">Driver Identity</label>
            <span class="text-secondary" style="font-size: 11px;" id="driver-remember-hint"><i class="bi bi-check-circle text-success me-1"></i>Auto-Saved</span>
        </div>
        <select class="form-select bg-dark text-white border-secondary" id="driver-select" style="font-weight: 600;">
            <?php foreach ($drivers as $d): ?>
                <option value="<?= (int) $d['id']; ?>" <?= ($selectedDriverId == $d['id']) ? 'selected' : ''; ?>>
                    <?= e($d['full_name']); ?> &middot; <?= e($d['license_number']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Active Delivery Milestone Card (If Assigned) -->
    <?php if ($activeShipment): ?>
    <div class="card-panel mb-3 border-warning" style="background: rgba(245, 200, 66, 0.05); border-color: rgba(245, 200, 66, 0.3) !important;" id="active-trip-box">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-warning text-dark fw-bold">Active Cargo Delivery</span>
            <span class="small font-monospace text-warning fw-bold"><?= e($activeShipment['tracking_number']); ?></span>
        </div>
        <div class="small mb-2">
            <div class="text-white fw-bold" style="font-size: 14px;"><?= e($activeShipment['cargo_type']); ?></div>
            <div class="text-secondary" style="font-size: 12px;">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($activeShipment['pickup_location']); ?> &rarr; <?= e($activeShipment['destination']); ?>
            </div>
            <?php if (!empty($activeShipment['plate_number'])): ?>
                <div class="text-secondary" style="font-size: 11px;">Truck: <strong class="text-light"><?= e($activeShipment['plate_number']); ?></strong></div>
            <?php endif; ?>
        </div>

        <!-- 1-Tap Milestone Update Button for Driver -->
        <div class="d-flex gap-2 mt-2">
            <?php if ($activeShipment['current_status'] === 'assigned'): ?>
                <button type="button" class="btn btn-warning text-dark w-100 milestone-btn" onclick="updateTripStatus(<?= (int)$activeShipment['id']; ?>, 'pickup')">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Mark Arrived at Pickup
                </button>
            <?php elseif ($activeShipment['current_status'] === 'pickup'): ?>
                <button type="button" class="btn btn-primary w-100 milestone-btn" onclick="updateTripStatus(<?= (int)$activeShipment['id']; ?>, 'in_transit')">
                    <i class="bi bi-truck me-1"></i> Departed &middot; En Route
                </button>
            <?php elseif ($activeShipment['current_status'] === 'in_transit'): ?>
                <button type="button" class="btn btn-success w-100 milestone-btn" onclick="updateTripStatus(<?= (int)$activeShipment['id']; ?>, 'arrived')">
                    <i class="bi bi-geo-fill me-1"></i> Mark Arrived at Destination
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Big Center Radar Transmitter Button -->
    <div class="radar-box" id="radar-container">
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <button type="button" class="radar-center-btn" id="toggle-gps-btn">
            <i class="bi bi-broadcast fs-3 mb-1"></i>
            <span id="btn-label">START GPS</span>
        </button>
    </div>

    <!-- Transmitter Status Bar -->
    <div class="text-center mb-3">
        <div class="small fw-semibold" id="status-text" style="color: rgba(255,255,255,0.75);">
            Tap "START GPS" to broadcast live phone movement on mobile data.
        </div>
        <div class="mt-1 d-flex justify-content-center gap-2">
            <span class="badge bg-dark border border-secondary text-secondary" id="wakelock-pill" style="font-size: 11px;">
                <i class="bi bi-brightness-high me-1"></i> Screen Awake: Off
            </span>
            <span class="badge bg-dark border border-secondary text-secondary" id="datasaver-pill" style="font-size: 11px;">
                <i class="bi bi-lightning-charge me-1"></i> Data Saver: On
            </span>
        </div>
    </div>

    <!-- Live Telematics Metrics Grid -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="stat-badge-card">
                <div class="stat-badge-val" id="disp-speed">0.0 km/h</div>
                <div class="stat-badge-lbl"><i class="bi bi-speedometer2 me-1"></i> Speed</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-badge-card">
                <div class="stat-badge-val" id="disp-acc">&plusmn; 0 m</div>
                <div class="stat-badge-lbl"><i class="bi bi-bullseye me-1"></i> GPS Accuracy</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-badge-card">
                <div class="stat-badge-val" id="disp-lat">---.-----</div>
                <div class="stat-badge-lbl">Latitude</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-badge-card">
                <div class="stat-badge-val" id="disp-lng">---.-----</div>
                <div class="stat-badge-lbl">Longitude</div>
            </div>
        </div>
    </div>

    <!-- Live Road Map -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="text-secondary fw-bold"><i class="bi bi-geo-alt-fill text-warning me-1"></i> Live Road Position</small>
            <small class="text-secondary" id="updates-count">Sent: 0 | Queued: 0</small>
        </div>
        <div id="mobile-map"></div>
    </div>

    <!-- Step Simulator (Indoor & Sandbox Testing) -->
    <div class="card-panel text-center mb-3">
        <div class="small text-secondary mb-2">Testing indoors or parked?</div>
        <button type="button" class="btn btn-outline-warning btn-sm w-100 fw-bold" id="simulate-step-btn">
            <i class="bi bi-person-walking me-1"></i> Simulate Driving Step (+5m East)
        </button>
    </div>

    <!-- Offline Queue Notice Banner -->
    <div class="p-2 rounded text-center small text-secondary" style="background: rgba(255,255,255,0.02); font-size: 11px;">
        <i class="bi bi-cloud-arrow-up me-1"></i> Zero-Data-Loss Queue: Coordinates auto-buffer if signal drops and sync immediately upon reconnect.
    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var isTracking = false;
    var watchId = null;
    var transmitInterval = null;
    var wakeLockSentinel = null;
    var updatesSent = 0;

    var currentLat = 6.1164;
    var currentLng = 125.1716;
    var lastSentLat = null;
    var lastSentLng = null;
    var lastSentTime = 0;
    var currentSpeedKmh = 0;
    var currentAccuracy = 0;
    var hasRealFix = false;

    // Offline buffer queue
    var offlineQueue = JSON.parse(localStorage.getItem('regc_gps_queue') || '[]');

    // UI elements
    var toggleBtn = document.getElementById('toggle-gps-btn');
    var btnLabel = document.getElementById('btn-label');
    var radarContainer = document.getElementById('radar-container');
    var statusText = document.getElementById('status-text');
    var connPill = document.getElementById('conn-pill');
    var batteryPill = document.getElementById('battery-pill');
    var wakelockPill = document.getElementById('wakelock-pill');
    var datasaverPill = document.getElementById('datasaver-pill');

    var dispSpeed = document.getElementById('disp-speed');
    var dispAcc = document.getElementById('disp-acc');
    var dispLat = document.getElementById('disp-lat');
    var dispLng = document.getElementById('disp-lng');
    var updatesCountEl = document.getElementById('updates-count');
    var driverSelect = document.getElementById('driver-select');
    var simStepBtn = document.getElementById('simulate-step-btn');

    // Restore saved driver from localStorage
    var savedDriverId = localStorage.getItem('regc_saved_driver_id');
    if (savedDriverId && driverSelect) {
        var opt = driverSelect.querySelector('option[value="' + savedDriverId + '"]');
        if (opt && !window.location.search.includes('driver_id')) {
            driverSelect.value = savedDriverId;
        }
    }
    driverSelect.addEventListener('change', function() {
        localStorage.setItem('regc_saved_driver_id', this.value);
        // Reload with driver_id parameter so active delivery box updates
        if (!window.location.search.includes('driver_id=' + this.value)) {
            window.location.href = 'index.php?route=mobile-gps&driver_id=' + this.value;
        }
    });

    // Monitor Phone Battery Status
    if ('getBattery' in navigator) {
        navigator.getBattery().then(function(battery) {
            function updateBattery() {
                var level = Math.round(battery.level * 100);
                var icon = battery.charging ? '⚡' : '🔋';
                batteryPill.textContent = icon + ' ' + level + '%';
            }
            updateBattery();
            battery.addEventListener('levelchange', updateBattery);
            battery.addEventListener('chargingchange', updateBattery);
        });
    }

    // Audio Chime Synthesizer (Web Audio API)
    function playBeep(freq, type, duration) {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = type || 'sine';
            osc.frequency.value = freq || 800;
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (duration || 0.15));
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + (duration || 0.15));
        } catch(e) {}
    }

    // Haptic Vibration helper
    function vibrate(pattern) {
        if ('vibrate' in navigator) {
            try { navigator.vibrate(pattern || [60]); } catch(e) {}
        }
    }

    // Initialize Leaflet Map
    var mMap = L.map('mobile-map', { zoomControl: false, attributionControl: false }).setView([currentLat, currentLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(mMap);

    var userMarker = L.circleMarker([currentLat, currentLng], {
        radius: 9,
        fillColor: '#22c55e',
        color: '#ffffff',
        weight: 3,
        fillOpacity: 1
    }).addTo(mMap);

    var pathLine = L.polyline([[currentLat, currentLng]], {
        color: '#f5c842',
        weight: 4,
        opacity: 0.8
    }).addTo(mMap);

    // Calculate distance between two lat/lng in meters (Haversine formula)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        var R = 6371e3; // meters
        var φ1 = lat1 * Math.PI / 180;
        var φ2 = lat2 * Math.PI / 180;
        var Δφ = (lat2 - lat1) * Math.PI / 180;
        var Δλ = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ/2) * Math.sin(Δλ/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // Screen Wake Lock API: Keep phone screen on while driving
    async function requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                wakeLockSentinel = await navigator.wakeLock.request('screen');
                wakelockPill.className = 'badge bg-success';
                wakelockPill.innerHTML = '<i class="bi bi-brightness-high me-1"></i> Screen Awake: ON';
                wakeLockSentinel.addEventListener('release', function() {
                    if (isTracking) {
                        requestWakeLock();
                    } else {
                        wakelockPill.className = 'badge bg-dark border border-secondary text-secondary';
                        wakelockPill.innerHTML = '<i class="bi bi-brightness-high me-1"></i> Screen Awake: OFF';
                    }
                });
            } catch (err) {
                wakelockPill.textContent = 'Wake Lock N/A';
            }
        }
    }

    function releaseWakeLock() {
        if (wakeLockSentinel !== null) {
            try { wakeLockSentinel.release(); } catch(e) {}
            wakeLockSentinel = null;
        }
        wakelockPill.className = 'badge bg-dark border border-secondary text-secondary';
        wakelockPill.innerHTML = '<i class="bi bi-brightness-high me-1"></i> Screen Awake: OFF';
    }

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible' && isTracking) {
            requestWakeLock();
        }
    });

    // Transmit Position to Server
    function transmitPosition(lat, lng) {
        var driverId = driverSelect.value;
        var now = Date.now();

        // Data-Saver Check: if vehicle hasn't moved more than 5 meters and speed < 2 km/h,
        // throttle transmissions to once every 25 seconds instead of every 4 seconds!
        if (lastSentLat !== null && lastSentLng !== null) {
            var distMoved = calculateDistance(lastSentLat, lastSentLng, lat, lng);
            var timeElapsed = (now - lastSentTime) / 1000;
            if (distMoved < 5 && currentSpeedKmh < 2 && timeElapsed < 25) {
                // Stationary: skipping unnecessary duplicate upload to save mobile data
                datasaverPill.className = 'badge bg-info text-dark';
                datasaverPill.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Data-Saver: Stationary';
                return;
            }
        }

        datasaverPill.className = 'badge bg-dark border border-secondary text-secondary';
        datasaverPill.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Data-Saver: Moving';

        // Prepare current waypoint
        var currentPoint = {
            lat: parseFloat(lat),
            lng: parseFloat(lng),
            speed: currentSpeedKmh,
            accuracy: currentAccuracy,
            time: new Date().toISOString()
        };

        // Prepare payload including any offline backlog
        var pointsToSend = offlineQueue.concat([currentPoint]);

        var payload = {
            driver_id: parseInt(driverId),
            lat: parseFloat(lat),
            lng: parseFloat(lng),
            waypoints: pointsToSend,
            api_key: 'regc-gps-live'
        };

        fetch('index.php?route=driver-gps', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': 'regc-gps-live'
            },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                updatesSent++;
                lastSentLat = lat;
                lastSentLng = lng;
                lastSentTime = now;

                // Clear offline queue upon successful sync
                offlineQueue = [];
                localStorage.removeItem('regc_gps_queue');

                updatesCountEl.textContent = 'Sent: ' + updatesSent + ' | Queued: 0';
                connPill.className = 'net-badge bg-success';
                connPill.textContent = 'Live 4G/5G';
            }
        })
        .catch(function(err) {
            // Network dropped or weak cellular connection: buffer in offline queue
            offlineQueue.push(currentPoint);
            // Limit queue size to last 100 points
            if (offlineQueue.length > 100) offlineQueue.shift();
            localStorage.setItem('regc_gps_queue', JSON.stringify(offlineQueue));

            connPill.className = 'net-badge bg-warning text-dark';
            connPill.textContent = 'Buffering (' + offlineQueue.length + ')';
            updatesCountEl.textContent = 'Sent: ' + updatesSent + ' | Queued: ' + offlineQueue.length;
        });
    }

    function onGpsPosition(pos) {
        hasRealFix = true;
        currentLat = pos.coords.latitude;
        currentLng = pos.coords.longitude;

        currentSpeedKmh = pos.coords.speed !== null && !isNaN(pos.coords.speed) ? (pos.coords.speed * 3.6).toFixed(1) : '0.0';
        currentAccuracy = pos.coords.accuracy ? Math.round(pos.coords.accuracy) : 5;

        dispLat.textContent = currentLat.toFixed(5);
        dispLng.textContent = currentLng.toFixed(5);
        dispSpeed.textContent = currentSpeedKmh + ' km/h';
        dispAcc.textContent = '± ' + currentAccuracy + ' m';

        userMarker.setLatLng([currentLat, currentLng]);
        pathLine.addLatLng([currentLat, currentLng]);
        mMap.setView([currentLat, currentLng], 16);

        statusText.innerHTML = '<span class="text-success"><i class="bi bi-broadcast me-1"></i> Live Satellite Lock Active &middot; Telematics Transmitting</span>';
    }

    function onGpsError(err) {
        var msg = 'GPS Error: ' + err.message;
        if (err.code === 1) {
            msg = 'Location permission denied. Please allow location access in your browser settings.';
        }
        statusText.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i> ' + msg + '</span>';
        connPill.className = 'net-badge bg-danger';
        connPill.textContent = 'GPS Error';
        vibrate([100, 100, 100]);
    }

    function startTracking() {
        isTracking = true;
        toggleBtn.classList.add('active');
        radarContainer.classList.add('broadcasting');
        btnLabel.textContent = 'STOP GPS';
        connPill.className = 'net-badge bg-success';
        connPill.textContent = 'Broadcasting';
        statusText.textContent = 'Acquiring high-accuracy satellite lock...';

        playBeep(920, 'sine', 0.2);
        vibrate([80]);
        requestWakeLock();

        if (navigator.geolocation) {
            watchId = navigator.geolocation.watchPosition(onGpsPosition, onGpsError, {
                enableHighAccuracy: true,
                maximumAge: 1000,
                timeout: 15000
            });
        } else {
            statusText.textContent = 'Geolocation not supported by this browser. Use Simulate Step button.';
        }

        // Transmit coordinates every 4 seconds
        transmitInterval = setInterval(function() {
            if (isTracking) {
                transmitPosition(currentLat.toFixed(8), currentLng.toFixed(8));
            }
        }, 4000);
    }

    function stopTracking() {
        isTracking = false;
        toggleBtn.classList.remove('active');
        radarContainer.classList.remove('broadcasting');
        btnLabel.textContent = 'START GPS';
        connPill.className = 'net-badge bg-secondary';
        connPill.textContent = 'Idle';
        statusText.textContent = 'GPS transmission stopped. Tap to resume.';

        playBeep(440, 'sine', 0.2);
        vibrate([50, 50]);
        releaseWakeLock();

        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        if (transmitInterval !== null) {
            clearInterval(transmitInterval);
            transmitInterval = null;
        }
    }

    toggleBtn.addEventListener('click', function() {
        if (!isTracking) {
            startTracking();
        } else {
            stopTracking();
        }
    });

    // Simulate Step Button (for testing walking movement indoors or without GPS lock)
    simStepBtn.addEventListener('click', function() {
        currentLng += 0.000045; // ~5 meters East
        currentSpeedKmh = '18.5';
        currentAccuracy = 3;

        dispLat.textContent = currentLat.toFixed(5);
        dispLng.textContent = currentLng.toFixed(5);
        dispSpeed.textContent = currentSpeedKmh + ' km/h';
        dispAcc.textContent = '± ' + currentAccuracy + ' m';

        userMarker.setLatLng([currentLat, currentLng]);
        pathLine.addLatLng([currentLat, currentLng]);
        mMap.setView([currentLat, currentLng], 17);

        transmitPosition(currentLat.toFixed(8), currentLng.toFixed(8));
        statusText.innerHTML = '<span class="text-warning"><i class="bi bi-truck me-1"></i> Simulated step transmitted to admin fleet map!</span>';
        playBeep(1100, 'triangle', 0.08);
        vibrate([40]);
    });

    // 1-Tap Trip Milestone Updater
    window.updateTripStatus = function(shipmentId, nextStatus) {
        var driverId = driverSelect.value;
        if (!confirm('Advance delivery status to ' + nextStatus.toUpperCase() + '?')) return;

        fetch('index.php?route=mobile-trip-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': 'regc-gps-live'
            },
            body: JSON.stringify({
                driver_id: parseInt(driverId),
                shipment_id: parseInt(shipmentId),
                status: nextStatus,
                api_key: 'regc-gps-live'
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                playBeep(1200, 'sine', 0.25);
                vibrate([100, 50, 100]);
                alert('✅ Status Updated: ' + data.label);
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Could not update status.'));
            }
        })
        .catch(function(err) {
            alert('Network error while updating delivery milestone.');
        });
    };

})();
</script>
</body>
</html>
