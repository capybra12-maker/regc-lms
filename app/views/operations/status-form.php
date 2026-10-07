<?php
$title = 'Update delivery status';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
$step = array_search($shipment['current_status'], Operations::STATUSES, true);
$next = Operations::STATUSES[$step + 1] ?? null;
?>
<div data-modal-panel data-modal-title="Update delivery status">
<?php if (!$modalRequest): ?><h1 class="mb-4">Update delivery status</h1><?php endif; ?>
<?php if ($shipment['current_status'] === 'pending' || !$next): ?>
    <div class="alert alert-info">This shipment has no delivery update available. Assign resources to a pending shipment, or return to its details.</div>
    <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>" data-modal-close>Close</a>
<?php else: ?>
    <p class="text-muted"><?= e($shipment['tracking_number']); ?></p>
    <div class="form-guidance mb-4"><strong><?= e(ucwords(str_replace('_', ' ', $shipment['current_status']))); ?> &rarr; <?= e(ucwords(str_replace('_', ' ', $next))); ?></strong><p class="mb-0">This update will be added to the tracking history.<?= $next === 'delivered' ? ' Completing delivery releases the truck and driver.' : ''; ?></p></div>
    <form method="post" action="index.php?route=shipments" data-modal-form>
        <?= csrfField(); ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $shipment['id']; ?>"><input type="hidden" name="status" value="<?= e($next); ?>">
        <label for="remarks" class="form-label">Delivery update <span class="text-muted">(optional remarks)</span></label>
        <textarea id="remarks" name="remarks" class="form-control" rows="4" maxlength="5000" placeholder="Add a location or delivery note"></textarea>
        <div class="form-actions"><button class="btn btn-regc" type="submit">Mark as <?= e(str_replace('_', ' ', $next)); ?></button><a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>" data-modal-close>Cancel</a></div>
    </form>
<?php endif; ?>
</div>
<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>
