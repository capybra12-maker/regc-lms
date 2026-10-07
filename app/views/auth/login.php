<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Sign In | REGC Fleet Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="public/css/app.css">
<style>
/* ── Animated Logistics Background ── */
.login-body {
    min-height: 100vh;
    background: radial-gradient(circle at 80% 20%, rgba(245, 200, 66, 0.15) 0%, transparent 45%),
                linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 60%, #152233 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
    padding: 24px 16px;
}

/* Moving Road & Truck at the bottom of login */
.login-road {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: rgba(8, 13, 23, 0.6);
    border-top: 2px dashed rgba(245, 200, 66, 0.35);
    overflow: hidden;
    z-index: 1;
}

.login-road-line {
    position: absolute;
    top: 50%;
    left: 0;
    width: 200%;
    height: 3px;
    background: repeating-linear-gradient(90deg, var(--gold) 0, var(--gold) 30px, transparent 30px, transparent 60px);
    animation: roadMove 3s linear infinite;
}

@keyframes roadMove {
    0% { transform: translateX(0); }
    100% { transform: translateX(-60px); }
}

.login-moving-truck {
    position: absolute;
    bottom: 18px;
    font-size: 26px;
    color: var(--gold);
    animation: loginTruckDrive 14s linear infinite;
    filter: drop-shadow(0 0 10px rgba(245, 200, 66, 0.6));
    z-index: 2;
}

@keyframes loginTruckDrive {
    0% { left: -60px; transform: scaleX(1); }
    100% { left: calc(100% + 60px); transform: scaleX(1); }
}

/* Floating Logistics Icon Badges */
.floating-cargo {
    position: absolute;
    color: rgba(245, 200, 66, 0.15);
    pointer-events: none;
    z-index: 1;
    animation: floatCargo 12s ease-in-out infinite;
}

.floating-cargo-1 { top: 12%; left: 8%; font-size: 42px; animation-duration: 9s; }
.floating-cargo-2 { top: 68%; left: 12%; font-size: 34px; animation-duration: 13s; animation-delay: 2s; }
.floating-cargo-3 { top: 18%; right: 10%; font-size: 48px; animation-duration: 11s; animation-delay: 1s; }
.floating-cargo-4 { top: 72%; right: 8%; font-size: 38px; animation-duration: 15s; animation-delay: 3s; }

@keyframes floatCargo {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(8deg); }
}

/* Enhanced Login Card */
.login-card {
    position: relative;
    z-index: 10;
    border: 1px solid rgba(245, 200, 66, 0.25);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 30px rgba(245, 200, 66, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.login-card:hover {
    border-color: rgba(245, 200, 66, 0.45);
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.55), 0 0 40px rgba(245, 200, 66, 0.15);
}

.brand-icon {
    position: relative;
    overflow: hidden;
}

.brand-icon i {
    animation: truckBounce 3s ease-in-out infinite;
}

@keyframes truckBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}


</style>
</head>
<body class="login-body">

<!-- Logistics Animation Elements -->
<div class="login-bg-shape login-shape-1" style="pointer-events: none;"></div>
<div class="login-bg-shape login-shape-2" style="pointer-events: none;"></div>

<!-- Floating Logistics Icons -->
<div class="floating-cargo floating-cargo-1"><i class="bi bi-box-seam"></i></div>
<div class="floating-cargo floating-cargo-2"><i class="bi bi-geo-alt-fill"></i></div>
<div class="floating-cargo floating-cargo-3"><i class="bi bi-truck-front-fill"></i></div>
<div class="floating-cargo floating-cargo-4"><i class="bi bi-shield-check"></i></div>

<!-- Animated Road with Moving Truck -->
<div class="login-road">
    <div class="login-road-line"></div>
    <div class="login-moving-truck"><i class="bi bi-truck"></i></div>
</div>

<div class="login-wrapper" style="position: relative; z-index: 10;">
    <div class="login-card">

        <div class="brand-icon">
            <i class="bi bi-truck"></i>
        </div>

        <h2>REGC Fleet Management</h2>
        <p class="tagline">Track. Deliver. Manage. Efficiently.</p>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill fs-5"></i>
                <div class="small fw-semibold"><?= e($_SESSION['error']); ?></div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="index.php?route=login-submit" method="POST">
            <?= csrfField(); ?>

            <div class="mb-3">
                <label class="form-label" for="login-email">Email Address or Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input
                        id="login-email"
                        type="text"
                        name="email"
                        class="form-control border-start-0"
                        required
                        autocomplete="username"
                        placeholder="e.g. admin@regc.com or Username"
                        value="<?= e($_SESSION['login_email'] ?? ''); ?>"
                        autofocus
                    >
                </div>
                <small class="form-text text-muted" style="font-size: 11px;">You can sign in with your email address or your full name.</small>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0" for="login-password">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input
                        id="login-password"
                        type="password"
                        name="password"
                        class="form-control border-start-0 border-end-0"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    >
                    <button class="btn btn-outline-secondary password-toggle border-start-0" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button class="btn btn-regc w-100 py-2" type="submit" id="btn-login-submit" style="cursor: pointer; position: relative; z-index: 11;">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
            </button>
        </form>


        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 text-muted small">
            <span><i class="bi bi-shield-check text-warning me-1"></i> Authorized Only</span>
            <a href="index.php" class="text-muted text-decoration-none">&larr; Public Home</a>
        </div>

    </div>
</div>

<script src="public/js/app.js"></script>
</body>
</html>
