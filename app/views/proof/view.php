<?php
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { $title = 'Verified Proof of Delivery'; require __DIR__ . '/../partials/header.php'; }
?>

<div data-modal-panel data-modal-title="Verified POD &mdash; <?= e($shipment['tracking_number']); ?>">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">Receiving Verification</span><h1>Proof of Delivery Documentation</h1></div>
    <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>">&larr; Back to Shipment</a>
</div>
<?php endif; ?>

<div class="workspace-card mb-4 border bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="eyebrow">Verified Custody Sign-off</span>
        <span class="status-badge status-delivered"><i class="bi bi-shield-check"></i> Verified</span>
    </div>

    <dl class="detail-grid mb-3">
        <div>
            <dt>Tracking Number</dt>
            <dd><?= e($shipment['tracking_number']); ?></dd>
        </div>
        <div>
            <dt>Received By</dt>
            <dd><strong><?= e($pod['received_by'] ?: 'Warehouse Receiving Staff'); ?></strong></dd>
        </div>
        <div>
            <dt>Verified Timestamp</dt>
            <dd><?= e(formatDate($pod['uploaded_at'])); ?></dd>
        </div>
        <div>
            <dt>Cargo Type &amp; Weight</dt>
            <dd><?= e($shipment['cargo_type']); ?> (<?= number_format((float) $shipment['cargo_weight_kg'], 2); ?> kg)</dd>
        </div>
        <div>
            <dt>Delivering Vehicle</dt>
            <dd><?= e($shipment['plate_number'] ?? 'N/A'); ?></dd>
        </div>
        <div>
            <dt>Submitted By Staff</dt>
            <dd><?= e($pod['uploaded_by_name'] ?? 'Authorized Personnel'); ?></dd>
        </div>
    </dl>

    <?php if ($pod['notes']): ?>
        <div class="detail-notes border-top pt-3 mt-3">
            <dt class="text-muted small text-uppercase">Receiving Remarks</dt>
            <dd class="mt-1"><?= nl2br(e($pod['notes'])); ?></dd>
        </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php if ($pod['delivery_receipt']): ?>
    <div class="col-md-6">
        <div class="p-3 border rounded bg-light text-center">
            <strong class="d-block mb-2 text-dark"><i class="bi bi-file-earmark-text"></i> Signed Delivery Receipt</strong>
            <?php if (str_ends_with(strtolower($pod['delivery_receipt']), '.pdf')): ?>
                <a href="public/uploads/pod/<?= e($pod['delivery_receipt']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> View PDF Receipt
                </a>
            <?php else: ?>
                <a href="public/uploads/pod/<?= e($pod['delivery_receipt']); ?>" target="_blank">
                    <img src="public/uploads/pod/<?= e($pod['delivery_receipt']); ?>" class="img-fluid rounded border shadow-sm" style="max-height: 220px; object-fit: contain;" alt="Delivery Receipt">
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($pod['proof_image']): ?>
    <div class="col-md-6">
        <div class="p-3 border rounded bg-light text-center">
            <strong class="d-block mb-2 text-dark"><i class="bi bi-camera"></i> Cargo / Delivery Photo</strong>
            <?php if (str_ends_with(strtolower($pod['proof_image']), '.pdf')): ?>
                <a href="public/uploads/pod/<?= e($pod['proof_image']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> View PDF Document
                </a>
            <?php else: ?>
                <a href="public/uploads/pod/<?= e($pod['proof_image']); ?>" target="_blank">
                    <img src="public/uploads/pod/<?= e($pod['proof_image']); ?>" class="img-fluid rounded border shadow-sm" style="max-height: 220px; object-fit: contain;" alt="Proof Image">
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="form-actions mt-4">
    <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>" data-modal-close>
        Close Document
    </a>
</div>
</div>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>

