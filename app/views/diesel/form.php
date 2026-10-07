<?php
$isEdit = !empty($values['id']);
$title = $isEdit ? 'Edit Fuel Record' : 'Log Diesel Consumption';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
?>

<div data-modal-panel data-modal-title="<?= e($title); ?>">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">Diesel Management</span><h1><?= e($title); ?></h1></div>
    <a class="btn btn-outline-secondary" href="index.php?route=diesel">&larr; Back to Diesel</a>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error); ?></div>
<?php endif; ?>

<form method="post" action="index.php?route=diesel-save" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form" data-modal-form>
    <?= csrfField(); ?>
    <input type="hidden" name="id" value="<?= (int) ($values['id'] ?? 0); ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="truck_id">Vehicle / Truck *</label>
            <select class="form-select" id="truck_id" name="truck_id" required autofocus>
                <option value="">Choose a Truck</option>
                <?php foreach ($trucks as $t): ?>
                    <option value="<?= (int) $t['id']; ?>" <?= ($values['truck_id'] ?? '') == $t['id'] ? 'selected' : ''; ?>>
                        <?= e($t['plate_number']); ?> &middot; <?= e($t['truck_model']); ?> (<?= e($t['truck_type']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="driver_id">Driver (Optional)</label>
            <select class="form-select" id="driver_id" name="driver_id">
                <option value="">Unassigned / Depot Fill</option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?= (int) $d['id']; ?>" <?= ($values['driver_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>>
                        <?= e($d['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="shipment_id">Linked Shipment (Optional)</label>
            <select class="form-select" id="shipment_id" name="shipment_id">
                <option value="">None / Standalone Tank Fill</option>
                <?php foreach ($shipments as $s): ?>
                    <option value="<?= (int) $s['id']; ?>" <?= ($values['shipment_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                        <?= e($s['tracking_number']); ?> &middot; <?= e($s['cargo_type']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="transaction_date">Date of Fueling *</label>
            <input class="form-control" type="date" id="transaction_date" name="transaction_date" value="<?= e($values['transaction_date'] ?? date('Y-m-d')); ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="liters">Liters Consumed *</label>
            <input class="form-control" type="number" step="0.01" min="0.1" id="liters" name="liters" value="<?= e($values['liters'] ?? ''); ?>" required placeholder="e.g. 15.00">
            <small class="form-text text-muted">Baseline: 15 L per trip</small>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="price_per_liter">Price per Liter (₱) *</label>
            <input class="form-control" type="number" step="0.01" min="1" id="price_per_liter" name="price_per_liter" value="<?= e($values['price_per_liter'] ?? '65.00'); ?>" required placeholder="65.00">
        </div>

        <div class="col-md-4">
            <label class="form-label" for="total_cost">Total Fuel Cost (₱)</label>
            <input class="form-control bg-light fw-bold" type="text" id="total_cost" readonly value="<?= e($values['total_cost'] ?? ''); ?>" placeholder="Calculated automatically">
        </div>

        <div class="col-md-6">
            <label class="form-label" for="trip_distance_km">Trip Distance (KM)</label>
            <input class="form-control" type="number" step="0.1" min="0" id="trip_distance_km" name="trip_distance_km" value="<?= e($values['trip_distance_km'] ?? ''); ?>" placeholder="e.g. 180.5">
        </div>

        <div class="col-md-6">
            <label class="form-label" for="notes">Notes / Fuel Station</label>
            <input class="form-control" type="text" id="notes" name="notes" value="<?= e($values['notes'] ?? ''); ?>" maxlength="255" placeholder="e.g. Shell Pier Depot Station">
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-regc" type="submit">
            <i class="bi bi-check-lg"></i> Save Fuel Log
        </button>
        <a class="btn btn-outline-secondary" href="index.php?route=diesel" data-modal-close>Cancel</a>
    </div>
</form>
</div>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>

