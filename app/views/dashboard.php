<?php
$title = 'Fleet Operations Dashboard';
$module = 'dashboard';
require __DIR__ . '/partials/header.php';
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Enterprise Fleet & Freight Management</span>
        <h1>Operational Dashboard</h1>
        <p>Live operational totals and real-time transit status across all routes.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-warning text-dark fw-bold" href="index.php?route=driver-map">
            <i class="bi bi-map-fill me-1"></i> Fleet GPS Map
        </a>
        <a class="btn btn-regc" href="index.php?route=shipments&amp;action=new" data-modal-link>
            <i class="bi bi-plus-lg"></i> New Shipment
        </a>
        <a class="btn btn-outline-secondary" href="index.php?route=reports">
            <i class="bi bi-file-earmark-bar-graph"></i> View Reports
        </a>
    </div>
</div>

<!-- 10 Operational Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Total Shipments</span>
                <h2 class="stat-value counter" data-value="<?= $stats['total_shipments']; ?>">0</h2>
                <div class="stat-subtext text-muted"><i class="bi bi-archive"></i> Cumulative freight</div>
            </div>
            <div class="stat-icon icon-navy"><i class="bi bi-box-seam"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Active Deliveries</span>
                <h2 class="stat-value counter" data-value="<?= $stats['active']; ?>">0</h2>
                <div class="stat-subtext text-primary"><i class="bi bi-arrow-repeat"></i> In progress / Transit</div>
            </div>
            <div class="stat-icon icon-blue"><i class="bi bi-truck"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Pending Dispatch</span>
                <h2 class="stat-value counter" data-value="<?= $stats['pending']; ?>">0</h2>
                <div class="stat-subtext text-warning"><i class="bi bi-hourglass-split"></i> Awaiting assignment</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Delivered Shipments</span>
                <h2 class="stat-value counter" data-value="<?= $stats['delivered']; ?>">0</h2>
                <div class="stat-subtext text-success"><i class="bi bi-check-circle"></i> Successfully completed</div>
            </div>
            <div class="stat-icon icon-emerald"><i class="bi bi-check2-circle"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Available Trucks</span>
                <h2 class="stat-value counter" data-value="<?= $stats['available_trucks']; ?>">0</h2>
                <div class="stat-subtext text-muted"><i class="bi bi-truck-front"></i> Ready at pier / depot</div>
            </div>
            <div class="stat-icon icon-emerald"><i class="bi bi-truck-front"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Trucks In Transit</span>
                <h2 class="stat-value counter" data-value="<?= $stats['trucks_in_transit']; ?>">0</h2>
                <div class="stat-subtext text-info"><i class="bi bi-geo-alt"></i> On highway routes</div>
            </div>
            <div class="stat-icon icon-blue"><i class="bi bi-signpost-2"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Available Drivers</span>
                <h2 class="stat-value counter" data-value="<?= $stats['available_drivers']; ?>">0</h2>
                <div class="stat-subtext text-muted"><i class="bi bi-person-check"></i> On duty & ready</div>
            </div>
            <div class="stat-icon icon-purple"><i class="bi bi-person-check"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Diesel Consumed</span>
                <h2 class="stat-value counter" data-value="<?= (int) $stats['diesel_consumption']; ?>">0</h2>
                <div class="stat-subtext text-muted"><i class="bi bi-fuel-pump"></i> Total liters logged</div>
            </div>
            <div class="stat-icon"><i class="bi bi-fuel-pump-fill"></i></div>
        </div>
    </div>
</div>

<!-- 4 Chart.js Interactive Graphs -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Shipment Status Overview -->
    <div class="col-lg-6">
        <div class="workspace-card h-100">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Status Breakdown</span>
                    <h3 class="section-title">Shipment Overview</h3>
                </div>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="shipmentStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Weekly Deliveries (Mon - Sun) -->
    <div class="col-lg-6">
        <div class="workspace-card h-100">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Delivery Frequency</span>
                    <h3 class="section-title">Weekly Deliveries (Mon - Sun)</h3>
                </div>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="weeklyDeliveryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 3: Diesel Consumption Analytics -->
    <div class="col-lg-6">
        <div class="workspace-card h-100">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Fleet Efficiency</span>
                    <h3 class="section-title">Diesel Consumption Trend (Liters)</h3>
                </div>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="dieselAnalyticsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 4: Financial Summary (Revenue vs Expenses) -->
    <div class="col-lg-6">
        <div class="workspace-card h-100">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Commercial Margins</span>
                    <h3 class="section-title">Revenue & Expenses Overview</h3>
                </div>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="financialChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Shipments Table -->
<div class="workspace-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Real-Time Dispatch</span>
            <h3 class="section-title">Recent Shipments</h3>
        </div>
        <a class="record-link" href="index.php?route=shipments">
            View all shipments <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>

    <?php if (!$recentShipments): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-box-seam fs-1 text-muted"></i>
            <h4 class="mt-3">No shipments recorded yet</h4>
            <p class="text-muted">Create your first commercial shipment to start tracking deliveries.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Tracking Number</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Cargo Type</th>
                        <th scope="col">Destination</th>
                        <th scope="col">Status</th>
                        <th scope="col">Last Updated</th>
                        <th scope="col" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentShipments as $shipment): ?>
                    <tr>
                        <td>
                            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>">
                                <?= e($shipment['tracking_number']); ?>
                            </a>
                        </td>
                        <td><?= e($shipment['company_name'] ?: $shipment['contact_person']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($shipment['cargo_type']); ?></span></td>
                        <td><?= e($shipment['destination']); ?></td>
                        <td>
                            <span class="status-badge status-<?= e($shipment['current_status']); ?>">
                                <?= e(ucwords(str_replace('_', ' ', $shipment['current_status']))); ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= e(formatDate($shipment['updated_at'])); ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>">
                                View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Chart.js Script Initialization -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    // 1. Shipment Status Donut
    new Chart(document.getElementById('shipmentStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Assigned', 'Pickup', 'In Transit', 'Arrived', 'Delivered'],
            datasets: [{
                data: <?= json_encode($chartStatusData); ?>,
                backgroundColor: ['#f59e0b', '#6366f1', '#3b82f6', '#06b6d4', '#8b5cf6', '#10b981'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } }
            }
        }
    });

    // 2. Weekly Deliveries Bar
    new Chart(document.getElementById('weeklyDeliveryChart'), {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Completed Deliveries',
                data: <?= json_encode($chartWeeklyData); ?>,
                backgroundColor: '#f5c842',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    // 3. Diesel Trend Line
    new Chart(document.getElementById('dieselAnalyticsChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chartDieselLabels ?: ['No logs']); ?>,
            datasets: [{
                label: 'Liters Consumed',
                data: <?= json_encode($chartDieselLiters ?: [0]); ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 200, 66, 0.15)',
                fill: true,
                tension: 0.35,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 4. Financial Summary
    new Chart(document.getElementById('financialChart'), {
        type: 'bar',
        data: {
            labels: ['Freight Revenue', 'Diesel Fuel Cost', 'Operating Overhead', 'Net Margins'],
            datasets: [{
                label: 'Amount (₱)',
                data: [<?= (float) $totalRevenue; ?>, <?= (float) $totalDieselExpense; ?>, <?= (float) $otherExpense; ?>, <?= (float) $netProfit; ?>],
                backgroundColor: ['#10b981', '#ef4444', '#64748b', '#3b82f6'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '₱' + value.toLocaleString(); }
                    }
                }
            }
        }
    });
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
