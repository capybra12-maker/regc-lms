<?php
$title = 'Reports & Commercial Analytics';
$module = 'reports';
require __DIR__ . '/../partials/header.php';
?>

<div class="page-heading mb-4 d-print-none">
    <div>
        <span class="eyebrow">Executive Intelligence</span>
        <h1>Reports &amp; Analytics</h1>
        <p>Comprehensive operational, fleet performance, and financial analytics.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print / Export Report
        </button>
    </div>
</div>

<!-- Report Navigation Tabs -->
<div class="d-flex gap-2 flex-wrap mb-4 d-print-none border-bottom pb-2">
    <a href="index.php?route=reports&amp;tab=shipments" class="btn btn-sm <?= $tab === 'shipments' ? 'btn-regc' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-box-seam"></i> Shipments Report
    </a>
    <a href="index.php?route=reports&amp;tab=customers" class="btn btn-sm <?= $tab === 'customers' ? 'btn-regc' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-people"></i> Customers Report
    </a>
    <a href="index.php?route=reports&amp;tab=financial" class="btn btn-sm <?= $tab === 'financial' ? 'btn-regc' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-cash-coin"></i> Revenue &amp; Expenses
    </a>
    <a href="index.php?route=reports&amp;tab=diesel" class="btn btn-sm <?= $tab === 'diesel' ? 'btn-regc' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-fuel-pump"></i> Diesel Consumption
    </a>
    <a href="index.php?route=reports&amp;tab=drivers" class="btn btn-sm <?= $tab === 'drivers' ? 'btn-regc' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-person-badge"></i> Drivers Performance
    </a>
    <a href="index.php?route=reports&amp;tab=trucks" class="btn btn-sm <?= $tab === 'trucks' ? 'btn-regc' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-truck"></i> Fleet &amp; Vehicles
    </a>
</div>

<!-- Print Header (Visible only on print) -->
<div class="d-none d-print-block mb-4 text-center">
    <h2 class="fw-bold mb-1">REGC Fleet Management & Shipment Tracking</h2>
    <p class="text-muted mb-1">Commercial Logistics Management System &middot; Operational Report</p>
    <small class="text-muted">Generated on <?= date('F j, Y, g:i A'); ?> &middot; Module: <?= strtoupper(e($tab)); ?></small>
    <hr>
</div>

<!-- 1. SHIPMENTS REPORT -->
<?php if ($tab === 'shipments'): ?>
<div class="workspace-card mb-4 d-print-none">
    <form method="get" class="filter-bar mb-0">
        <input type="hidden" name="route" value="reports">
        <input type="hidden" name="tab" value="shipments">
        <div>
            <label class="form-label" for="start_date">From Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate); ?>">
        </div>
        <div>
            <label class="form-label" for="end_date">To Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDate); ?>">
        </div>
        <div>
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (Operations::STATUSES as $st): ?>
                    <option value="<?= e($st); ?>" <?= $status === $st ? 'selected' : ''; ?>><?= e(ucwords(str_replace('_', ' ', $st))); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-navy" type="submit"><i class="bi bi-filter"></i> Apply Filters</button>
    </form>
</div>

<div class="workspace-card">
    <div class="section-heading">
        <h3 class="section-title">Shipment Operations Summary</h3>
        <span class="badge bg-light text-dark border"><?= count($reportData['rows']); ?> Shipments in Period</span>
    </div>

    <div class="table-responsive">
        <table class="table operations-table align-middle">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Customer</th>
                    <th>Cargo</th>
                    <th>Route</th>
                    <th>Vehicle &amp; Driver</th>
                    <th>Status</th>
                    <th>Freight (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php $totalFreight = 0; $totalWeight = 0; foreach ($reportData['rows'] as $row): 
                    $totalFreight += (float) ($row['freight_amount'] ?? 0);
                    $totalWeight += (float) $row['cargo_weight_kg'];
                ?>
                <tr>
                    <td><strong><?= e($row['tracking_number']); ?></strong></td>
                    <td><?= e($row['company_name'] ?: $row['contact_person']); ?></td>
                    <td><?= e($row['cargo_type']); ?> (<?= number_format((float) $row['cargo_weight_kg'], 1); ?> kg)</td>
                    <td><small class="text-muted"><?= e($row['pickup_location']); ?> &rarr; <?= e($row['destination']); ?></small></td>
                    <td><?= e($row['plate_number'] ?? 'Unassigned'); ?> &middot; <?= e($row['driver_name'] ?? 'Unassigned'); ?></td>
                    <td><span class="status-badge status-<?= e($row['current_status']); ?>"><?= e(ucwords(str_replace('_', ' ', $row['current_status']))); ?></span></td>
                    <td class="fw-bold">₱<?= number_format((float) $row['freight_amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="2">Period Totals</td>
                    <td colspan="4"><?= number_format($totalWeight, 1); ?> kg cumulative cargo</td>
                    <td>₱<?= number_format($totalFreight, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- 2. CUSTOMERS REPORT -->
<?php elseif ($tab === 'customers'): ?>
<div class="workspace-card">
    <div class="section-heading">
        <h3 class="section-title">Customer Commercial Activity</h3>
    </div>
    <div class="table-responsive">
        <table class="table operations-table align-middle">
            <thead>
                <tr>
                    <th>Customer / Company</th>
                    <th>Contact Person</th>
                    <th>Phone / Email</th>
                    <th>Total Shipments</th>
                    <th>Completed Deliveries</th>
                    <th>Total Cargo Hauled</th>
                    <th>Total Freight Invoiced</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['rows'] as $crow): ?>
                <tr>
                    <td><strong><?= e($crow['company_name'] ?: $crow['contact_person']); ?></strong></td>
                    <td><?= e($crow['contact_person']); ?></td>
                    <td><small class="text-muted"><?= e($crow['contact_number'] ?: 'N/A'); ?> &middot; <?= e($crow['email'] ?: 'N/A'); ?></small></td>
                    <td><span class="badge bg-light text-dark border"><?= (int) $crow['total_shipments']; ?></span></td>
                    <td><span class="badge bg-success"><?= (int) $crow['completed_deliveries']; ?></span></td>
                    <td><?= number_format((float) $crow['total_cargo_kg'], 1); ?> kg</td>
                    <td class="fw-bold text-dark">₱<?= number_format((float) $crow['total_revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 3. FINANCIAL REPORT -->
<?php elseif ($tab === 'financial'): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Total Freight Revenue</span>
                <h3 class="stat-value text-success">₱<?= number_format((float) $reportData['revenue'], 2); ?></h3>
                <small class="text-muted">Delivered freight billings</small>
            </div>
            <div class="stat-icon icon-emerald"><i class="bi bi-wallet2"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Total Diesel Fuel Cost</span>
                <h3 class="stat-value text-danger">₱<?= number_format((float) $reportData['diesel_cost'], 2); ?></h3>
                <small class="text-muted">Fleet fuel transactions</small>
            </div>
            <div class="stat-icon"><i class="bi bi-fuel-pump-fill"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Operating Overhead</span>
                <h3 class="stat-value text-muted">₱<?= number_format((float) $reportData['other_expense'], 2); ?></h3>
                <small class="text-muted">PMS &middot; Pier dues &middot; Toll</small>
            </div>
            <div class="stat-icon icon-navy"><i class="bi bi-tools"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Net Operating Margin</span>
                <h3 class="stat-value text-primary">₱<?= number_format((float) $reportData['net_amount'], 2); ?></h3>
                <small class="text-muted">Net commercial income</small>
            </div>
            <div class="stat-icon icon-blue"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
</div>

<div class="workspace-card">
    <div class="section-heading">
        <h3 class="section-title">Monthly Revenue Breakdown</h3>
    </div>
    <div class="table-responsive">
        <table class="table operations-table align-middle">
            <thead>
                <tr>
                    <th>Billing Month</th>
                    <th>Completed Shipments</th>
                    <th>Invoiced Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['monthly'] as $m): ?>
                <tr>
                    <td><strong><?= e($m['month_label']); ?></strong></td>
                    <td><?= (int) $m['shipments_count']; ?> deliveries</td>
                    <td class="fw-bold text-success">₱<?= number_format((float) $m['monthly_rev'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 4. DIESEL REPORT -->
<?php elseif ($tab === 'diesel'): ?>
<div class="workspace-card">
    <div class="section-heading">
        <h3 class="section-title">Fleet Diesel Consumption Logs</h3>
    </div>
    <div class="table-responsive">
        <table class="table operations-table align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Shipment</th>
                    <th>Liters Consumed</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['rows'] as $drow): ?>
                <tr>
                    <td><?= e(date('M d, Y', strtotime($drow['transaction_date']))); ?></td>
                    <td><strong><?= e($drow['plate_number']); ?></strong> (<?= e($drow['truck_model']); ?>)</td>
                    <td><?= e($drow['driver_name'] ?: 'Depot Fill'); ?></td>
                    <td><?= e($drow['tracking_number'] ?: 'Stand-alone'); ?></td>
                    <td class="fw-bold <?= (float) $drow['liters'] > 15 ? 'text-danger' : 'text-success'; ?>"><?= number_format((float) $drow['liters'], 1); ?> L</td>
                    <td>₱<?= number_format((float) $drow['total_cost'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 5. DRIVER PERFORMANCE REPORT -->
<?php elseif ($tab === 'drivers'): ?>
<div class="workspace-card">
    <div class="section-heading">
        <h3 class="section-title">Driver Assignment &amp; Delivery Performance</h3>
    </div>
    <div class="table-responsive">
        <table class="table operations-table align-middle">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>License Number</th>
                    <th>Contact Phone</th>
                    <th>Current Status</th>
                    <th>Total Assigned Jobs</th>
                    <th>Delivered Completions</th>
                    <th>Active Assignments</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['rows'] as $driver): ?>
                <tr>
                    <td><strong><?= e($driver['full_name']); ?></strong></td>
                    <td><?= e($driver['license_number']); ?></td>
                    <td><?= e($driver['contact_number'] ?: 'N/A'); ?></td>
                    <td><span class="status-badge status-<?= e($driver['status']); ?>"><?= e(ucwords(str_replace('_', ' ', $driver['status']))); ?></span></td>
                    <td><span class="badge bg-light text-dark border"><?= (int) $driver['total_assigned']; ?></span></td>
                    <td><span class="badge bg-success"><?= (int) $driver['completed']; ?></span></td>
                    <td><span class="badge bg-primary"><?= (int) $driver['active_jobs']; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 6. TRUCK REPORT -->
<?php elseif ($tab === 'trucks'): ?>
<div class="workspace-card">
    <div class="section-heading">
        <h3 class="section-title">Fleet Utilization &amp; Fuel Efficiency</h3>
    </div>
    <div class="table-responsive">
        <table class="table operations-table align-middle">
            <thead>
                <tr>
                    <th>Plate Number</th>
                    <th>Model &amp; Type</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Completed Trips</th>
                    <th>Total Distance</th>
                    <th>Diesel Consumed</th>
                    <th>Fuel Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['rows'] as $trk): ?>
                <tr>
                    <td><strong><?= e($trk['plate_number']); ?></strong></td>
                    <td><?= e($trk['truck_model']); ?> (<?= e($trk['truck_type']); ?>)</td>
                    <td><?= number_format((float) $trk['capacity_kg'], 1); ?> kg</td>
                    <td><span class="status-badge status-<?= e($trk['status']); ?>"><?= e(ucwords(str_replace('_', ' ', $trk['status']))); ?></span></td>
                    <td><span class="badge bg-light text-dark border"><?= (int) $trk['delivered_trips']; ?> trips</span></td>
                    <td><?= number_format((float) $trk['total_distance_km'], 1); ?> km</td>
                    <td><?= number_format((float) $trk['total_diesel_liters'], 1); ?> L</td>
                    <td class="fw-bold">₱<?= number_format((float) $trk['total_diesel_cost'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>

