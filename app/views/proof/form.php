<?php
$title = 'Proof of Delivery (POD)';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
?>

<div data-modal-panel data-modal-title="Proof of Delivery &mdash; <?= e($shipment['tracking_number']); ?>">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">Shipment Confirmation</span><h1>Proof of Delivery</h1></div>
    <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>">&larr; Back to Shipment</a>
</div>
<?php endif; ?>

<div class="form-guidance mb-4">
    <strong>Verify Receiving Handover</strong>
    <p class="mb-0">Upload delivery receipt, photograph of delivered cargo, or signed acceptance documentation to clear shipment <strong><?= e($shipment['tracking_number']); ?></strong>.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error); ?></div>
<?php endif; ?>

<form method="post" action="index.php?route=proof-upload" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form" data-modal-form>
    <?= csrfField(); ?>
    <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id']; ?>">
    <input type="hidden" name="proof_image_base64" id="proof_image_base64">
    <input type="hidden" name="delivery_receipt_base64" id="delivery_receipt_base64">
    <input type="hidden" name="signature_file_base64" id="signature_file_base64">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="received_by">Recipient Full Name *</label>
            <input class="form-control" type="text" id="received_by" name="received_by" value="<?= e($values['received_by'] ?? ''); ?>" required maxlength="150" placeholder="e.g. Warehouse Manager Name" autofocus>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="proof_image">Cargo Photo / Delivery Photo</label>
            <input class="form-control" type="file" id="proof_image" accept="image/*,.pdf" data-base64-target="proof_image_base64">
            <small class="form-text text-muted">Accepts JPG, PNG, WEBP, or PDF</small>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="delivery_receipt">Signed Delivery Receipt (DR)</label>
            <input class="form-control" type="file" id="delivery_receipt" accept="image/*,.pdf" data-base64-target="delivery_receipt_base64">
            <small class="form-text text-muted">Scan or photo of signed physical paper DR</small>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="signature_file">Recipient Signature Image (Optional)</label>
            <input class="form-control" type="file" id="signature_file" accept="image/*" data-base64-target="signature_file_base64">
            <small class="form-text text-muted">Digital signature capture or photo</small>
        </div>

        <div class="col-12">
            <label class="form-label" for="notes">Condition / Handover Remarks</label>
            <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000" placeholder="Confirm seal number, bag counts, cargo condition upon arrival..."><?= e($values['notes'] ?? ''); ?></textarea>
        </div>

        <?php if ($shipment['current_status'] === 'arrived'): ?>
        <div class="col-12">
            <div class="form-check p-3 rounded bg-light border">
                <input class="form-check-input" type="checkbox" id="mark_delivered" name="mark_delivered" value="1" checked>
                <label class="form-check-label fw-bold text-dark" for="mark_delivered">
                    Mark Shipment as "Delivered" upon submission
                </label>
                <small class="d-block text-muted">Releases assigned truck (<?= e($shipment['plate_number'] ?? 'N/A'); ?>) and driver to available status.</small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button class="btn btn-regc" type="submit">
            <i class="bi bi-cloud-arrow-up-fill"></i> Upload Proof of Delivery
        </button>
        <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>" data-modal-close>Cancel</a>
    </div>
</form>
</div>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>

