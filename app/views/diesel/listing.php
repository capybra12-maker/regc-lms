<?php
$title = 'Diesel & Fuel Management';
$module = 'diesel';
require __DIR__ . '/../partials/header.php';
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Fleet Fuel Efficiency</span>
        <h1>Diesel &amp; Fuel Tracking</h1>
        <p>Monitor trip fuel consumption, compute expenditures, and compare against the commercial baseline (15 L/trip).</p>
    </div>
    <a class="btn btn-regc" href="index.php?route=diesel-form" data-modal-link>
        <i class="bi bi-plus-lg"></i> Log Fuel Consumption
    </a>
</div>

<!-- Diesel Baseline & Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Total Diesel Used</span>
                <h3 class="stat-value text-dark counter" data-value="<?= (int) $analytics['overall']['total_liters']; ?>"><?= number_format((float) $analytics['overall']['total_liters'], 1); ?></h3>
                <small class="text-muted">Liters logged</small>
            </div>
            <div class="stat-icon"><i class="bi bi-fuel-pump-fill"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Total Fuel Cost</span>
                <h3 class="stat-value text-danger">₱<?= number_format((float) $analytics['overall']['total_cost'], 2); ?></h3>
                <small class="text-muted">Total fuel expense</small>
            </div>
            <div class="stat-icon icon-navy"><i class="bi bi-cash-stack"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Avg. per Trip</span>
                <h3 class="stat-value text-dark"><?= number_format((float) $analytics['overall']['avg_liters_per_trip'], 1); ?> L</h3>
                <small class="text-muted">Target baseline: 15.0 L</small>
            </div>
            <div class="stat-icon <?= (float) $analytics['overall']['avg_liters_per_trip'] > 15 ? 'text-danger' : 'icon-emerald'; ?>">
                <i class="bi <?= (float) $analytics['overall']['avg_liters_per_trip'] > 15 ? 'bi-arrow-up-right-circle' : 'bi-check2-circle'; ?>"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div>
                <span class="stat-label">Total Fuel Logs</span>
                <h3 class="stat-value text-dark counter" data-value="<?= (int) $analytics['overall']['total_transactions']; ?>">0</h3>
                <small class="text-muted">Dispatches logged</small>
            </div>
            <div class="stat-icon icon-blue"><i class="bi bi-receipt"></i></div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="workspace-card">
    <form method="get" class="filter-bar">
        <input type="hidden" name="route" value="diesel">
        <div class="filter-search">
            <label for="q" class="form-label">Search Logs</label>
            <input id="q" class="form-control" name="q" value="<?= e($search); ?>" placeholder="Search truck plate, driver, tracking #">
        </div>
        <div>
            <label for="truck_id" class="form-label">Vehicle</label>
            <select class="form-select" id="truck_id" name="truck_id">
                <option value="">All Vehicles</option>
                <?php foreach ($trucks as $t): ?>
                    <option value="<?= (int) $t['id']; ?>" <?= $truckId == $t['id'] ? 'selected' : ''; ?>>
                        <?= e($t['plate_number']); ?> (<?= e($t['truck_model']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="start_date" class="form-label">From Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate); ?>">
        </div>
        <div>
            <label for="end_date" class="form-label">To Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDate); ?>">
        </div>
        <button class="btn btn-navy" type="submit"><i class="bi bi-filter"></i> Filter</button>
        <a class="btn btn-outline-secondary" href="index.php?route=diesel">Clear</a>
    </form>

    <?php if (!$listing['rows']): ?>
        <div class="empty-state py-5 text-center">
            <i class="bi bi-fuel-pump fs-1 text-muted"></i>
            <h4 class="mt-3">No diesel transaction records</h4>
            <p class="text-muted">Record fuel consumption for completed and ongoing trips.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table operations-table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Truck</th>
                        <th>Driver</th>
                        <th>Shipment</th>
                        <th>Liters Consumed</th>
                        <th>Price/Liter</th>
                        <th>Total Fuel Cost</th>
                        <th>Trip Distance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listing['rows'] as $row): 
                        $isOverBaseline = (float) $row['liters'] > 15;
                    ?>
                    <tr>
                        <td class="fw-semibold text-dark"><?= e(date('M d, Y', strtotime($row['transaction_date']))); ?></td>
                        <td>
                            <strong><?= e($row['plate_number']); ?></strong>
                            <div class="small text-muted"><?= e($row['truck_model']); ?></div>
                        </td>
                        <td><?= e($row['driver_name'] ?: 'Unassigned'); ?></td>
                        <td>
                            <?php if (!empty($row['tracking_number'])): ?>
                                <a href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $row['shipment_id']; ?>" class="record-link">
                                    <?= e($row['tracking_number']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-bold <?= $isOverBaseline ? 'text-danger' : 'text-success'; ?>">
                                <?= number_format((float) $row['liters'], 2); ?> L
                            </span>
                            <?php if ($isOverBaseline): ?>
                                <small class="text-danger d-block" style="font-size: 10.5px;">&uarr; Over baseline</small>
                            <?php endif; ?>
                        </td>
                        <td>₱<?= number_format((float) $row['price_per_liter'], 2); ?></td>
                        <td class="fw-bold text-dark">₱<?= number_format((float) $row['total_cost'], 2); ?></td>
                        <td><?= $row['trip_distance_km'] ? number_format((float) $row['trip_distance_km'], 1) . ' km' : '&mdash;'; ?></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a class="btn btn-sm btn-outline-secondary" href="index.php?route=diesel-form&amp;id=<?= (int) $row['id']; ?>" data-modal-link>
                                    Edit
                                </a>
                                <form method="post" action="index.php?route=diesel-delete" data-confirm="Delete this fuel log record?">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="pagination-bar d-flex justify-content-between align-items-center pt-3" aria-label="Page navigation">
            <span class="text-muted small">Page <?= $listing['page']; ?> of <?= $listing['pages']; ?> (<?= $listing['total']; ?> entries)</span>
            <div class="d-flex gap-2">
                <?php if ($listing['page'] > 1): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?<?= e(http_build_query(['route' => 'diesel', 'q' => $search, 'truck_id' => $truckId, 'start_date' => $startDate, 'end_date' => $endDate, 'page' => $listing['page'] - 1])); ?>">&laquo; Previous</a>
                <?php endif; ?>
                <?php if ($listing['page'] < $listing['pages']): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?<?= e(http_build_query(['route' => 'diesel', 'q' => $search, 'truck_id' => $truckId, 'start_date' => $startDate, 'end_date' => $endDate, 'page' => $listing['page'] + 1])); ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

