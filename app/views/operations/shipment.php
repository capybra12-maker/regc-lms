<?php
$title = 'Shipment Details';
$module = 'shipments';
require __DIR__ . '/../partials/header.php';
$isAdmin = hasRole('administrator');
?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Freight Tracking</span>
        <h1>Delivery Overview</h1>
        <p>Live status timeline, dispatch assignments, and verification documents.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($isAdmin && in_array($shipment['current_status'], ['pending', 'assigned'], true)): ?>
            <a class="btn btn-regc" href="index.php?route=shipments&amp;action=edit&amp;id=<?= (int) $shipment['id']; ?>" data-modal-link>
                <i class="bi bi-pencil-square"></i> Edit / Assign
            </a>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <a class="btn btn-warning text-dark fw-bold" href="index.php?route=shipments&amp;action=email-tracking&amp;id=<?= (int) $shipment['id']; ?>" onclick="return confirm('Send tracking email with code <?= e($shipment['tracking_number']); ?> to the customer?');">
                <i class="bi bi-envelope-at-fill"></i> Email Customer
            </a>
            <button type="button" class="btn btn-success text-white fw-bold" data-bs-toggle="modal" data-bs-target="#smsCustomerModal" title="Send tracking number via text message (SMS)">
                <i class="bi bi-phone-fill"></i> SMS Customer
            </button>
            <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=email-preview&amp;id=<?= (int) $shipment['id']; ?>" target="_blank" title="Preview what the customer receives">
                <i class="bi bi-eye"></i> Preview Email
            </a>
            <a class="btn btn-outline-secondary" href="index.php?route=diesel-form&amp;shipment_id=<?= (int) $shipment['id']; ?>&amp;truck_id=<?= (int) ($shipment['truck_id'] ?? 0); ?>&amp;driver_id=<?= (int) ($shipment['driver_id'] ?? 0); ?>" data-modal-link>
                <i class="bi bi-fuel-pump"></i> Log Fuel
            </a>
        <?php endif; ?>

        <?php if ($shipment['current_status'] === 'arrived'): ?>
            <a class="btn btn-regc" href="index.php?route=proof-form&amp;shipment_id=<?= (int) $shipment['id']; ?>" data-modal-link>
                <i class="bi bi-file-earmark-check"></i> Upload Proof of Delivery
            </a>
        <?php endif; ?>

        <a class="btn btn-outline-secondary" href="index.php?route=shipments">
            <i class="bi bi-list-ul"></i> All Shipments
        </a>
        <a class="btn btn-outline-secondary" href="index.php?route=tracking&amp;number=<?= e(urlencode($shipment['tracking_number'] ?? '')); ?>">
            <i class="bi bi-geo-alt"></i> Public Tracking
        </a>
    </div>
</div>

<?php if ($shipment['current_status'] === 'pending'): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>Assign an available truck and driver using <strong>Edit / Assign</strong> to dispatch this shipment.</div>
    </div>
<?php elseif ($shipment['current_status'] !== 'delivered'):
    $next = Operations::STATUSES[array_search($shipment['current_status'], Operations::STATUSES, true) + 1] ?? null;
    if ($next): ?>
    <div class="workspace-card status-update mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="flex-grow-1">
            <strong class="fs-6 text-dark d-block">Advance Delivery Milestone</strong>
            <p class="text-muted mb-0">Record the next checkpoint: <strong><?= e(ucwords(str_replace('_', ' ', $shipment['current_status']))); ?> &rarr; <?= e(ucwords(str_replace('_', ' ', $next))); ?></strong>.</p>
        </div>
        <a class="btn btn-regc" href="index.php?route=shipments&amp;action=status-form&amp;id=<?= (int) $shipment['id']; ?>" data-modal-link>
            <i class="bi bi-arrow-right-circle"></i> Mark as <?= e(ucwords(str_replace('_', ' ', $next))); ?>
        </a>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>Delivery completed. Assigned truck and driver have been released back to available status.</div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/shipment-detail.php'; ?>

<?php if ($isAdmin):
    $customerPhone = trim($shipment['contact_number'] ?? '');
    $customerPerson = $shipment['contact_person'] ?? 'Valued Customer';
    $customerCompany = $shipment['company_name'] ?? '';
    $trackingNum = $shipment['tracking_number'] ?? '';
    $smsMsg = "Hi " . explode(' ', trim($customerPerson))[0] . "! Your REGC cargo (" . $trackingNum . ") is dispatched. Track live at " . (defined('APP_URL') ? APP_URL : '') . "/index.php?route=tracking&number=" . urlencode($trackingNum);
?>
<!-- SMS Customer Modal -->
<div class="modal fade" id="smsCustomerModal" tabindex="-1" aria-labelledby="smsCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="smsCustomerModalLabel">
                    <i class="bi bi-chat-text-fill me-2"></i>Send SMS to Customer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Recipient</label>
                    <div class="fw-bold fs-6 text-dark"><?= e($customerPerson); ?><?= $customerCompany ? ' <span class="text-muted fw-normal">(' . e($customerCompany) . ')</span>' : ''; ?></div>
                    <div class="text-primary fw-semibold mt-1">
                        <i class="bi bi-telephone-fill me-1"></i><?= $customerPhone ? e($customerPhone) : '<span class="text-danger">No phone number recorded</span>'; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Tracking Number</label>
                    <div class="p-2 bg-light border rounded font-monospace fw-bold fs-6 text-dark">
                        <?= e($trackingNum); ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Message Preview</label>
                    <textarea class="form-control font-monospace" rows="3" readonly style="font-size: 13px; background-color: #f8fafc;"><?= e($smsMsg); ?></textarea>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted"><?= mb_strlen($smsMsg); ?> characters</small>
                        <small class="text-success"><i class="bi bi-check-circle"></i> Standard SMS</small>
                    </div>
                </div>

                <?php if (!$customerPhone): ?>
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Please add a contact number to this customer's record to dispatch SMS alerts.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ($customerPhone): ?>
                    <a href="sms:<?= e(preg_replace('/[^0-9+]/', '', $customerPhone)); ?>?body=<?= e(rawurlencode($smsMsg)); ?>" class="btn btn-outline-success">
                        <i class="bi bi-phone"></i> Open in Phone App
                    </a>
                    <a href="index.php?route=shipments&amp;action=sms-tracking&amp;id=<?= (int) $shipment['id']; ?>" class="btn btn-success fw-bold">
                        <i class="bi bi-send-fill me-1"></i> Send Server SMS
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
