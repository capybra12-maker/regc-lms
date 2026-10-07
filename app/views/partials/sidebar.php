<?php
$currentUser = authUser();
$role = $currentUser['role'] ?? 'warehouse';
$activeModule = $module ?? 'dashboard';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo"><i class="bi bi-truck" aria-hidden="true"></i></div>
        <div class="sidebar-brand-text">
            <strong>REGC LMS</strong>
            <small>Fleet Management & Tracking</small>
        </div>
    </div>
    <nav aria-label="Main navigation">
        <?php if ($role === 'administrator'): ?>
            <div class="sidebar-section-title">Overview</div>
            <a href="index.php?route=dashboard" class="sidebar-link <?= $activeModule === 'dashboard' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
            </a>

            <div class="sidebar-section-title">Operations</div>
            <a href="index.php?route=shipments" class="sidebar-link <?= $activeModule === 'shipments' ? 'active' : ''; ?>">
                <i class="bi bi-box-seam-fill"></i><span>Shipments</span>
            </a>
            <a href="index.php?route=schedule" class="sidebar-link <?= $activeModule === 'schedule' ? 'active' : ''; ?>">
                <i class="bi bi-calendar3"></i><span>Schedules</span>
            </a>
            <a href="index.php?route=tracking" class="sidebar-link <?= $activeModule === 'tracking' ? 'active' : ''; ?>">
                <i class="bi bi-geo-alt-fill"></i><span>Live Tracking</span>
            </a>
            <a href="index.php?route=customers" class="sidebar-link <?= $activeModule === 'customers' ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i><span>Customers</span>
            </a>
            <a href="index.php?route=trucks" class="sidebar-link <?= $activeModule === 'trucks' ? 'active' : ''; ?>">
                <i class="bi bi-truck-front-fill"></i><span>Fleet & Trucks</span>
            </a>
            <a href="index.php?route=drivers" class="sidebar-link <?= $activeModule === 'drivers' ? 'active' : ''; ?>">
                <i class="bi bi-person-badge-fill"></i><span>Drivers</span>
            </a>
            <a href="index.php?route=driver-map" class="sidebar-link <?= $activeModule === 'driver-map' ? 'active' : ''; ?>">
                <i class="bi bi-map-fill text-warning"></i><span>Fleet GPS Map</span>
            </a>

            <div class="sidebar-section-title">Analytics & Fleet</div>
            <a href="index.php?route=diesel" class="sidebar-link <?= $activeModule === 'diesel' ? 'active' : ''; ?>">
                <i class="bi bi-fuel-pump-fill"></i><span>Diesel & Fuel</span>
            </a>
            <a href="index.php?route=reports" class="sidebar-link <?= $activeModule === 'reports' ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart-line-fill"></i><span>Reports & Bi</span>
            </a>

            <div class="sidebar-section-title">Administration</div>
            <a href="index.php?route=users" class="sidebar-link <?= $activeModule === 'users' ? 'active' : ''; ?>">
                <i class="bi bi-person-gear"></i><span>Users & Roles</span>
            </a>
            <a href="index.php?route=logs" class="sidebar-link <?= $activeModule === 'logs' ? 'active' : ''; ?>">
                <i class="bi bi-journal-text"></i><span>Activity Logs</span>
            </a>
            <a href="index.php?route=settings" class="sidebar-link <?= $activeModule === 'settings' ? 'active' : ''; ?>" data-modal-link>
                <i class="bi bi-gear-fill"></i><span>Settings</span>
            </a>

        <?php elseif ($role === 'driver'): ?>
            <div class="sidebar-section-title">Driver Workspace</div>
            <a href="index.php?route=driver" class="sidebar-link <?= $activeModule === 'driver' ? 'active' : ''; ?>">
                <i class="bi bi-truck"></i><span>My Deliveries</span>
            </a>
            <a href="index.php?route=schedule" class="sidebar-link <?= $activeModule === 'schedule' ? 'active' : ''; ?>">
                <i class="bi bi-calendar3"></i><span>My Schedule</span>
            </a>
            <a href="index.php?route=tracking" class="sidebar-link <?= $activeModule === 'tracking' ? 'active' : ''; ?>">
                <i class="bi bi-geo-alt-fill"></i><span>Track Shipment</span>
            </a>
            <a href="index.php?route=mobile-gps" class="sidebar-link <?= $activeModule === 'mobile-gps' ? 'active' : ''; ?>">
                <i class="bi bi-broadcast text-success"></i><span>Live GPS Transmitter</span>
            </a>

        <?php elseif ($role === 'warehouse'): ?>
            <div class="sidebar-section-title">Warehouse Workspace</div>
            <a href="index.php?route=warehouse" class="sidebar-link <?= $activeModule === 'warehouse' ? 'active' : ''; ?>">
                <i class="bi bi-building-check"></i><span>Incoming Cargo</span>
            </a>
            <a href="index.php?route=schedule" class="sidebar-link <?= $activeModule === 'schedule' ? 'active' : ''; ?>">
                <i class="bi bi-calendar3"></i><span>Arrival Schedules</span>
            </a>
            <a href="index.php?route=tracking" class="sidebar-link <?= $activeModule === 'tracking' ? 'active' : ''; ?>">
                <i class="bi bi-geo-alt-fill"></i><span>Track Delivery</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-divider"></div>

        <a href="index.php?route=account" class="sidebar-link <?= $activeModule === 'account' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i><span>My Account</span>
        </a>
        <form method="post" action="index.php?route=logout" class="sidebar-logout-form mt-2" data-confirm="Are you sure you want to sign out of your REGC account?">
            <?= csrfField(); ?>
            <button type="submit" class="sidebar-link logout" title="Log out">
                <i class="bi bi-box-arrow-left"></i><span>Sign Out</span>
            </button>
        </form>
    </nav>
</aside>
