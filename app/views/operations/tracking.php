<?php
$isAuth = isLoggedIn();
if ($isAuth) {
    $title = 'Track Delivery';
    $module = 'tracking';
    require __DIR__ . '/../partials/header.php';
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Delivery | REGC Fleet Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="public/css/app.css" rel="stylesheet">
</head>
<body style="background: var(--background);">

<header class="landing-header py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="brand-logo" style="width: 36px; height: 36px; font-size: 18px;"><i class="bi bi-truck"></i></div>
            <strong class="text-white fs-5">REGC Fleet Management</strong>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="text-white small text-decoration-none d-none d-sm-inline">&larr; Public Home</a>
            <a href="index.php?route=login" class="btn btn-regc btn-sm">Staff Sign In</a>
        </div>
    </div>
</header>

<div class="container py-5" style="max-width: 900px;">
<?php } ?>

<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Real-Time Delivery Visibility</span>
        <h1>Track Your Shipment</h1>
        <p>Enter your tracking number to verify current cargo milestone and transit checkpoint history.</p>
    </div>
</div>

<form class="workspace-card filter-bar mb-4 shadow-sm" method="get" action="index.php">
    <input type="hidden" name="route" value="tracking">
    <div class="filter-search">
        <label for="tracking-number" class="form-label">Unique Tracking Number</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input class="form-control" id="tracking-number" name="number" value="<?= e($number); ?>" placeholder="e.g. REGC-2026-000001" maxlength="60" required autofocus>
        </div>
    </div>
    <button class="btn btn-regc px-4" type="submit">
        <i class="bi bi-geo-alt-fill"></i> Track Delivery
    </button>
</form>

<?php if ($shipment): ?>
    <div class="mb-3 text-end">
        <?php if ($isAuth): ?>
            <a class="record-link fw-bold" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>">
                <i class="bi bi-gear"></i> Manage this shipment &rarr;
            </a>
        <?php else: ?>
            <a class="record-link fw-bold text-muted small" href="index.php?route=login">
                Staff sign in to manage this shipment &rarr;
            </a>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/shipment-detail.php'; ?>
<?php elseif ($number !== ''): ?>
    <div class="workspace-card empty-state text-center py-5">
        <i class="bi bi-search fs-1 text-warning mb-3 d-block"></i>
        <h3 class="fw-bold text-dark">Tracking Record Not Found</h3>
        <p class="text-muted">No shipment was found matching "<strong><?= e($number); ?></strong>". Please double check your tracking code and retry.</p>
    </div>
<?php else: ?>
    <div class="workspace-card empty-state text-center py-5">
        <i class="bi bi-geo-alt fs-1 text-warning mb-3 d-block"></i>
        <h3 class="fw-bold text-dark">Live Pier-to-Warehouse Tracking</h3>
        <p class="text-muted" style="max-width: 500px; margin: 0 auto;">
            Enter your official <strong>REGC Tracking Number</strong> (printed on your delivery dispatch or booking invoice) to monitor shipment progress live.
        </p>
    </div>
<?php endif; ?>

<?php if ($isAuth) {
    require __DIR__ . '/../partials/footer.php';
} else { ?>
</div>
<footer class="py-4 text-center text-muted small border-top bg-white mt-5">
    &copy; <?= date('Y'); ?> REGC Fleet Management &middot; Cloud-Based Fleet Management System
</footer>
<script src="public/js/app.js"></script>
</body>
</html>
<?php } ?>
