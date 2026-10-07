<?php
$isEdit = !empty($values['id']);
$title = $isEdit ? 'Edit Delivery Schedule' : 'Schedule Delivery';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
?>

<div data-modal-panel data-modal-title="<?= e($title); ?>">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">Delivery Planning</span><h1><?= e($title); ?></h1></div>
    <a class="btn btn-outline-secondary" href="index.php?route=schedule">&larr; Back to Schedules</a>
</div>
<?php endif; ?>

<div class="form-guidance mb-4">
    <strong>Conflict-Aware Scheduling</strong>
    <p class="mb-0">The system checks for overlapping vehicle and driver commitments before saving to avoid double-booking.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error); ?></div>
<?php endif; ?>

<form method="post" action="index.php?route=schedule-save" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form" data-modal-form>
    <?= csrfField(); ?>
    <input type="hidden" name="id" value="<?= (int) ($values['id'] ?? 0); ?>">

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="shipment_id">Select Active Shipment *</label>
            <select class="form-select" id="shipment_id" name="shipment_id" required autofocus>
                <option value="">Choose a Shipment</option>
                <?php foreach ($shipments as $s): ?>
                    <option value="<?= (int) $s['id']; ?>" <?= ($values['shipment_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                        <?= e($s['tracking_number']); ?> &middot; <?= e($s['cargo_type']); ?> (<?= e($s['pickup_location']); ?> &rarr; <?= e($s['destination']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="truck_id">Assigned Truck *</label>
            <select class="form-select" id="truck_id" name="truck_id" required>
                <option value="">Choose Vehicle</option>
                <?php foreach ($trucks as $t): ?>
                    <option value="<?= (int) $t['id']; ?>" <?= ($values['truck_id'] ?? '') == $t['id'] ? 'selected' : ''; ?>>
                        <?= e($t['plate_number']); ?> &middot; <?= e($t['truck_model']); ?> (<?= e($t['status']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="driver_id">Assigned Driver *</label>
            <select class="form-select" id="driver_id" name="driver_id" required>
                <option value="">Choose Driver</option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?= (int) $d['id']; ?>" <?= ($values['driver_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>>
                        <?= e($d['full_name']); ?> &middot; <?= e($d['license_number']); ?> (<?= e($d['status']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="pickup_datetime">Pickup Datetime *</label>
            <input class="form-control" type="datetime-local" id="pickup_datetime" name="pickup_datetime" value="<?= e(str_replace(' ', 'T', substr((string) ($values['pickup_datetime'] ?? ''), 0, 16))); ?>" required>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="expected_delivery_datetime">Expected Delivery Datetime</label>
            <input class="form-control" type="datetime-local" id="expected_delivery_datetime" name="expected_delivery_datetime" value="<?= e(str_replace(' ', 'T', substr((string) ($values['expected_delivery_datetime'] ?? ''), 0, 16))); ?>">
        </div>

        <div class="col-12">
            <label class="form-label" for="notes">Schedule Notes / Dispatch Instructions</label>
            <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="1000" placeholder="e.g. Pier entrance gate pass required. Priority vessel discharge."><?= e($values['notes'] ?? ''); ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-regc" type="submit">
            <i class="bi bi-calendar-check"></i> Save Schedule
        </button>
        <a class="btn btn-outline-secondary" href="index.php?route=schedule" data-modal-close>Cancel</a>
    </div>
</form>
</div>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>

