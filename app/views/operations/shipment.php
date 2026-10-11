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
            <button type="button" class="btn btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#emailCustomerModal" title="Send tracking email to the customer">
                <i class="bi bi-envelope-at-fill"></i> Email Customer
            </button>
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
    $customerEmail  = trim($shipment['email'] ?? '');
    $customerPhone  = trim($shipment['contact_number'] ?? '');
    $customerPerson = $shipment['contact_person'] ?? 'Valued Customer';
    $customerCompany= $shipment['company_name'] ?? '';
    $trackingNum    = $shipment['tracking_number'] ?? '';
    $appUrl         = defined('APP_URL') && APP_URL ? APP_URL : 'http://localhost/regc-lms';
    $trackingUrl    = $appUrl . '/index.php?route=tracking&number=' . urlencode($trackingNum);

    $emailSubject   = "Your Cargo Tracking Code: {$trackingNum} — REGC Fleet Management";
    $emailBodyText  = "Dear {$customerPerson},\n\nYour cargo shipment has been registered in our logistics dispatch system.\n\nTracking Code: {$trackingNum}\nCargo: {$shipment['cargo_type']}\nOrigin: {$shipment['pickup_location']}\nDestination: {$shipment['destination']}\n\nYou can track your cargo live 24/7 at:\n{$trackingUrl}\n\nThank you for choosing REGC Fleet Management!";

    $mailtoUrl      = 'mailto:' . rawurlencode($customerEmail) . '?subject=' . rawurlencode($emailSubject) . '&body=' . rawurlencode($emailBodyText);
    $smsMsg         = "Hi " . explode(' ', trim($customerPerson))[0] . "! Your REGC cargo (" . $trackingNum . ") is dispatched. Track live at " . $trackingUrl;

    require_once __DIR__ . '/../../services/Mailer.php';
    $smtpInfo       = Mailer::getSmtpConfig();
?>

<!-- Email Customer Modal -->
<div class="modal fade" id="emailCustomerModal" tabindex="-1" aria-labelledby="emailCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="emailCustomerModalLabel">
                    <i class="bi bi-envelope-at-fill me-2"></i>Send Tracking Email to Customer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Recipient</label>
                    <div class="fw-bold fs-6 text-dark"><?= e($customerPerson); ?><?= $customerCompany ? ' <span class="text-muted fw-normal">(' . e($customerCompany) . ')</span>' : ''; ?></div>
                    <div class="mt-1">
                        <?php if ($customerEmail): ?>
                            <span class="badge bg-light text-primary border fs-6 fw-normal">
                                <i class="bi bi-envelope-fill text-warning me-1"></i><?= e($customerEmail); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                <i class="bi bi-exclamation-circle-fill me-1"></i>No email address recorded
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Tracking Number &amp; Milestone</label>
                    <div class="p-2 bg-light border rounded d-flex justify-content-between align-items-center">
                        <span class="font-monospace fw-bold fs-6 text-dark"><?= e($trackingNum); ?></span>
                        <span class="badge bg-success"><?= e(ucwords(str_replace('_', ' ', $shipment['current_status']))); ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Email Subject &amp; Route</label>
                    <div class="p-2 bg-light border rounded small">
                        <div class="fw-semibold text-dark mb-1"><?= e($emailSubject); ?></div>
                        <div class="text-muted" style="font-size:12px;">
                            <i class="bi bi-box-seam me-1"></i><?= e($shipment['cargo_type']); ?> (<?= number_format((float) $shipment['cargo_weight_kg'], 2); ?> kg)<br>
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($shipment['pickup_location']); ?> &rarr; <?= e($shipment['destination']); ?>
                        </div>
                    </div>
                </div>

                <!-- Gateway Status Alert -->
                <div class="mb-3">
                    <?php if ($smtpInfo['is_configured']): ?>
                        <div class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                            <div>
                                <strong>Server SMTP Active</strong> (<?= e($smtpInfo['host']); ?>)<br>
                                <span class="text-muted">Sends formatted HTML email directly through your server.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-info-circle-fill fs-5 text-warning"></i>
                            <div>
                                <strong>Server SMTP Not Configured</strong><br>
                                <span class="text-muted">You can open in your local email app (mailto:) or configure Gmail in <a href="index.php?route=settings" class="fw-bold">Settings</a>.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$customerEmail): ?>
                    <div class="alert alert-danger py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Please add an email address to this customer's profile to dispatch tracking emails.
                        <a href="index.php?route=customers&amp;action=edit&amp;id=<?= (int)$shipment['customer_id']; ?>" class="fw-bold d-block mt-1">
                            &rarr; Edit Customer Profile
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <a href="index.php?route=shipments&amp;action=email-preview&amp;id=<?= (int) $shipment['id']; ?>" target="_blank" class="btn btn-outline-dark" title="Preview branded HTML email">
                    <i class="bi bi-eye"></i> Preview HTML
                </a>
                <?php if ($customerEmail): ?>
                    <a href="<?= e($mailtoUrl); ?>" class="btn btn-outline-warning text-dark fw-bold" title="Open in Outlook, Gmail, Apple Mail on your device">
                        <i class="bi bi-envelope-paper-fill me-1"></i> Open in Email App
                    </a>
                    <a href="index.php?route=shipments&amp;action=email-tracking&amp;id=<?= (int) $shipment['id']; ?>" class="btn btn-warning text-dark fw-bold" onclick="return confirm('Send live email via server SMTP?');">
                        <i class="bi bi-send-fill me-1"></i> Send Server Email
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
