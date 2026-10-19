<?php
$appName = APP_NAME ?? 'REGC Fleet Management & Shipment Tracking';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($appName) ?> | Commercial Freight &amp; Pier-to-Warehouse Logistics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="public/css/app.css" rel="stylesheet">
    <style>
        .landing-page { overflow-x: hidden; }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Scroll reveal */
        .reveal {
            opacity: 0;
            transform: translateY(35px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }

        /* Full-bleed hero */
        .landing-hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
        }
        .hero-bg-image {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .hero-bg-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 35%;
            filter: brightness(0.28) contrast(1.1);
        }
        .hero-bg-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(14,23,38,0.94) 0%, rgba(14,23,38,0.72) 55%, rgba(14,23,38,0.92) 100%);
            z-index: 1;
        }

        /* Animated truck route line */
        .hero-route-animation {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 90px;
            z-index: 2;
            overflow: hidden;
            opacity: 0.5;
            pointer-events: none;
        }
        .route-line-svg {
            position: absolute;
            bottom: 20px;
            left: 0;
            width: 200%;
        }
        .route-line-svg path {
            stroke: var(--gold);
            stroke-width: 2;
            fill: none;
            stroke-dasharray: 12 8;
            animation: dashMove 2.5s linear infinite;
        }
        @keyframes dashMove {
            to { stroke-dashoffset: -40; }
        }
        .truck-icon-moving {
            position: absolute;
            bottom: 26px;
            font-size: 26px;
            color: var(--gold);
            animation: truckDrive 14s linear infinite;
            filter: drop-shadow(0 0 10px rgba(245, 200, 66, 0.6));
        }
        @keyframes truckDrive {
            0% { left: -5%; }
            100% { left: 105%; }
        }

        /* Full Picture Filled Card */
        .img-fill-card {
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            background: #fff;
            border: 1px solid rgba(14,23,38,0.08);
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .img-fill-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 45px rgba(14,23,38,0.14);
            border-color: rgba(245,200,66,0.5);
        }
        .img-fill-wrap {
            position: relative;
            width: 100%;
            height: 210px;
            overflow: hidden;
        }
        .img-fill-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .img-fill-card:hover .img-fill-wrap img {
            transform: scale(1.08);
        }
        .img-fill-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(14,23,38,0.85);
            backdrop-filter: blur(8px);
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: 1px solid rgba(245,200,66,0.3);
        }
        .img-fill-body {
            padding: 22px 20px 24px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        /* Photo Showcase Grid */
        .photo-showcase-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-auto-rows: 240px;
            gap: 16px;
        }
        .showcase-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        .showcase-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .showcase-item:hover img {
            transform: scale(1.07);
        }
        .showcase-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(14,23,38,0.92) 0%, rgba(14,23,38,0.2) 60%, transparent 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 18px;
            color: #fff;
        }
        .showcase-item.col-span-2 {
            grid-column: span 2;
        }
        .showcase-item.row-span-2 {
            grid-row: span 2;
        }

        @media (max-width: 991.98px) {
            .photo-showcase-grid {
                grid-template-columns: repeat(2, 1fr);
                grid-auto-rows: 220px;
            }
            .showcase-item.col-span-2 { grid-column: span 1; }
            .showcase-item.row-span-2 { grid-row: span 1; }
        }
        @media (max-width: 575.98px) {
            .photo-showcase-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Step Card with Photo */
        .step-photo-card {
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--border);
            height: 100%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: transform 0.3s ease;
        }
        .step-photo-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(0,0,0,0.1);
        }
        .step-photo-wrap {
            position: relative;
            height: 160px;
            overflow: hidden;
        }
        .step-photo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .step-number-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--navy);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }

        /* Live pulsating radar dot */
        .live-dot {
            width: 10px; height: 10px;
            background: #22c55e;
            border-radius: 50%;
            display: inline-block;
            animation: pulseGlow 2s ease-in-out infinite;
            box-shadow: 0 0 0 0 rgba(34,197,94,0.4);
        }

        /* Nav Header Glass */
        .landing-header-glass {
            background: rgba(14,23,38,0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(245,200,66,0.15);
            transition: all 0.3s ease;
        }
        .landing-header-glass.scrolled {
            background: rgba(14,23,38,0.98);
            box-shadow: 0 4px 25px rgba(0,0,0,0.4);
        }
        .nav-link-landing {
            position: relative;
            color: rgba(255,255,255,0.75) !important;
            transition: color 0.3s ease;
        }
        .nav-link-landing::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gold);
            transition: width 0.3s ease;
        }
        .nav-link-landing:hover { color: #fff !important; }
        .nav-link-landing:hover::after { width: 100%; }

        /* Full-bleed CTA Banner */
        .cta-banner-filled {
            position: relative;
            background: #0e1726;
            overflow: hidden;
            border-radius: 20px;
        }
        .cta-banner-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.22);
        }
        .cta-banner-content {
            position: relative;
            z-index: 2;
            padding: 60px 30px;
        }
    </style>
</head>
<body class="landing-page">

<!-- Header Navigation -->
<header class="landing-header landing-header-glass position-fixed w-100" style="top:0; z-index:1090; padding:14px 0;" id="landing-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none brand-logo-landing">
                <div class="brand-logo flex-shrink-0" style="width:40px; height:40px; font-size:18px;">
                    <i class="bi bi-truck"></i>
                </div>
                <div>
                    <strong class="text-white fs-5 d-block" style="line-height:1.2;">REGC Fleet Management</strong>
                    <small class="text-secondary d-none d-sm-block" style="font-size:11px;">Fleet Management &amp; Shipment Tracking</small>
                </div>
            </a>
            <nav class="d-none d-md-flex align-items-center gap-4">
                <a href="#about" class="text-decoration-none small fw-semibold nav-link-landing">About</a>
                <a href="#services" class="text-decoration-none small fw-semibold nav-link-landing">Services</a>
                <a href="#fleet" class="text-decoration-none small fw-semibold nav-link-landing">Fleet</a>
                <a href="#hubs" class="text-decoration-none small fw-semibold nav-link-landing">Operations</a>
                <a href="#pipeline" class="text-decoration-none small fw-semibold nav-link-landing">Pipeline</a>
                <a href="#track" class="text-decoration-none small fw-semibold nav-link-landing">Live Tracking</a>
            </nav>
            <div class="flex-shrink-0" style="position:relative; z-index:1095;">
                <?php if (isLoggedIn()): ?>
                    <a href="index.php?route=<?= e(dashboardRouteForRole(authUser()['role'])); ?>" class="btn btn-regc text-nowrap">
                        <i class="bi bi-speedometer2"></i> Portal Dashboard
                    </a>
                <?php else: ?>
                    <a href="index.php?route=login" class="btn btn-regc text-nowrap">
                        <i class="bi bi-box-arrow-in-right"></i> Staff Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Hero Section with Full-Bleed Photography -->
<section class="landing-hero">
    <div class="hero-bg-image">
        <img src="https://images.unsplash.com/photo-1519003722824-194d4455a60c?w=1920&q=80" alt="Commercial freight trucks on coastal highway" loading="eager">
    </div>
    <div class="hero-bg-overlay"></div>

    <!-- Animated Logistics Trail -->
    <div class="hero-route-animation">
        <svg class="route-line-svg" viewBox="0 0 2000 60" preserveAspectRatio="none">
            <path d="M0,30 Q250,5 500,30 T1000,30 T1500,30 T2000,30" />
        </svg>
        <div class="truck-icon-moving"><i class="bi bi-truck"></i></div>
    </div>

    <div class="container position-relative" style="z-index:3; padding-top:110px; padding-bottom:70px;">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="reveal visible">
                    <span class="hero-tagline"><span class="live-dot me-2"></span> Cloud-Based Logistics &amp; Real-Time Telematics</span>
                    <h1 class="hero-title">Reliable Logistics.<br><span style="color: var(--gold);">Smarter Live Tracking.</span></h1>
                    <p class="hero-desc">
                        Specialized bulk cargo transportation from pier to warehouse across Mindanao and inter-provincial corridors. Real-time GPS telematics, instant proof of delivery, and verified timestamps.
                    </p>

                    <!-- Real-Time Tracking Input -->
                    <form action="index.php" method="GET" class="hero-track-box">
                        <input type="hidden" name="route" value="tracking">
                        <input type="text" name="number" placeholder="Enter Tracking # (e.g. REGC-2026-000001)" required maxlength="60">
                        <button type="submit" class="btn btn-regc px-4">
                            <i class="bi bi-geo-alt-fill"></i> Track Live
                        </button>
                    </form>
                    <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
                        <small class="text-secondary"><i class="bi bi-check-circle-fill text-warning me-1"></i> Real-time GPS map</small>
                        <small class="text-secondary"><i class="bi bi-check-circle-fill text-warning me-1"></i> Verified digital POD</small>
                        <small class="text-secondary"><i class="bi bi-check-circle-fill text-warning me-1"></i> Multi-axle fleet</small>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="hero-stat-card reveal reveal-delay-1 visible">
                            <i class="bi bi-box-seam fs-2 text-warning mb-2 d-block"></i>
                            <h3 class="fw-bold mb-1 counter" data-value="<?= $stats['total_shipments']; ?>"><?= $stats['total_shipments']; ?></h3>
                            <small class="text-secondary">Shipments Handled</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stat-card reveal reveal-delay-2 visible">
                            <i class="bi bi-check2-circle fs-2 text-success mb-2 d-block"></i>
                            <h3 class="fw-bold mb-1 counter" data-value="<?= $stats['delivered']; ?>"><?= $stats['delivered']; ?></h3>
                            <small class="text-secondary">Delivered Safely</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stat-card reveal reveal-delay-3 visible">
                            <i class="bi bi-truck-front fs-2 text-info mb-2 d-block"></i>
                            <h3 class="fw-bold mb-1 counter" data-value="<?= $stats['active_fleet']; ?>"><?= $stats['active_fleet']; ?></h3>
                            <small class="text-secondary">Active Vehicles</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stat-card reveal reveal-delay-4 visible">
                            <i class="bi bi-geo-alt fs-2 text-warning mb-2 d-block"></i>
                            <h3 class="fw-bold mb-1 counter" data-value="<?= $stats['provinces']; ?>"><?= $stats['provinces']; ?></h3>
                            <small class="text-secondary">Provinces Served</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section with Photo Collage -->
<section id="about" class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 reveal">
                <span class="eyebrow">About REGC Fleet Management</span>
                <h2 class="fw-bold mt-2 mb-4 fs-1" style="color: var(--navy);">Commercial Pier-to-Warehouse Hauling Specialists</h2>
                <p class="text-muted mb-4" style="line-height:1.7;">
                    REGC Fleet Management & Shipment Tracking connects commercial seaports and sea cargo discharge terminals directly to inland distribution centers and provincial warehouses. We transport bulk commodities including agricultural grain (corn, rice, soya) and industrial supplies (cement, steel) with precision scheduling.
                </p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border">
                            <i class="bi bi-shield-check fs-2 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Cargo Custody</strong>
                                <small class="text-muted">Inspected &amp; secured</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border">
                            <i class="bi bi-broadcast-pin fs-2 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Live Telematics</strong>
                                <small class="text-muted">30s GPS beacon tracking</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border">
                            <i class="bi bi-clock-history fs-2 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Turnaround Time</strong>
                                <small class="text-muted">Direct vessel transfer</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border">
                            <i class="bi bi-patch-check-fill fs-2 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Digital POD</strong>
                                <small class="text-muted">Photo &amp; sign receipts</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 reveal reveal-delay-2">
                <div class="d-flex flex-column gap-3">
                    <div class="position-relative rounded-4 overflow-hidden shadow-sm" style="height: 250px;">
                        <img src="https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?w=700&q=80" alt="Sea container loading onto truck" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 16px 20px; background: linear-gradient(transparent, rgba(14,23,38,0.92)); color: #fff;">
                            <strong class="d-block fs-6">Pier Offloading &amp; Direct Transfer</strong>
                            <small class="opacity-75">Port Area &middot; Tagoloan, Misamis Oriental</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="position-relative rounded-3 overflow-hidden shadow-sm" style="height: 140px;">
                                <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=600&q=80" alt="Freight convoy on highway" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                                <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 start-0 m-2" style="font-size: 10px;">Highway Transit</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="position-relative rounded-3 overflow-hidden shadow-sm" style="height: 140px;">
                                <img src="https://images.unsplash.com/photo-1553413077-190dd305871c?w=600&q=80" alt="Warehouse logistics storage" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                                <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 start-0 m-2" style="font-size: 10px;">Warehouse Hub</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section with FULL-BLEED Picture Cards -->
<section id="services" class="py-5" style="background: var(--background);">
    <div class="container py-4">
        <div class="text-center mx-auto mb-5 reveal" style="max-width:700px;">
            <span class="eyebrow">Comprehensive Capabilities</span>
            <h2 class="fw-bold mt-2 fs-1" style="color: var(--navy);">Tailored Commercial Logistics Services</h2>
            <p class="text-muted">Engineered for freight forwarders, agricultural mills, building suppliers, and distribution hubs.</p>
        </div>

        <div class="row g-4">
            <!-- 1. Pier Bulk Hauling -->
            <div class="col-md-4 reveal reveal-delay-1">
                <div class="img-fill-card">
                    <div class="img-fill-wrap">
                        <img src="https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=600&q=80" alt="Sea port gantry cranes loading bulk cargo" loading="lazy">
                        <span class="img-fill-badge"><i class="bi bi-water me-1"></i> Pier Operations</span>
                    </div>
                    <div class="img-fill-body">
                        <h4 class="fw-bold mb-2 text-dark">Pier Bulk Hauling</h4>
                        <p class="text-muted small mb-0">Discharging ocean freight directly at commercial berths and rapid container chassis transfer into long-haul multi-axle trucks.</p>
                    </div>
                </div>
            </div>

            <!-- 2. Warehouse Redistribution -->
            <div class="col-md-4 reveal reveal-delay-2">
                <div class="img-fill-card">
                    <div class="img-fill-wrap">
                        <img src="https://images.unsplash.com/photo-1587293852726-70cdb56c2866?w=600&q=80" alt="Modern automated distribution warehouse" loading="lazy">
                        <span class="img-fill-badge"><i class="bi bi-buildings me-1"></i> Warehousing</span>
                    </div>
                    <div class="img-fill-body">
                        <h4 class="fw-bold mb-2 text-dark">Warehouse Redistribution</h4>
                        <p class="text-muted small mb-0">Scheduled bulk transit between regional consolidation warehouses, inland siloing facilities, and distribution hubs.</p>
                    </div>
                </div>
            </div>

            <!-- 3. GPS Real-Time Telematics -->
            <div class="col-md-4 reveal reveal-delay-3">
                <div class="img-fill-card">
                    <div class="img-fill-wrap">
                        <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?w=600&q=80" alt="GPS navigation and live digital fleet map" loading="lazy">
                        <span class="img-fill-badge"><i class="bi bi-broadcast me-1"></i> Telematics</span>
                    </div>
                    <div class="img-fill-body">
                        <h4 class="fw-bold mb-2 text-dark">GPS Live Tracking</h4>
                        <p class="text-muted small mb-0">Sub-minute driver GPS telemetry with automated checkpoint logging, route path mapping, and instant ETA calculations.</p>
                    </div>
                </div>
            </div>

            <!-- 4. Fleet & Diesel Management -->
            <div class="col-md-4 reveal reveal-delay-1">
                <div class="img-fill-card">
                    <div class="img-fill-wrap">
                        <img src="https://images.unsplash.com/photo-1508974239320-0a029497e820?w=600&q=80" alt="Fleet maintenance inspection in depot" loading="lazy">
                        <span class="img-fill-badge"><i class="bi bi-fuel-pump me-1"></i> Efficiency</span>
                    </div>
                    <div class="img-fill-body">
                        <h4 class="fw-bold mb-2 text-dark">Fuel &amp; Fleet Analytics</h4>
                        <p class="text-muted small mb-0">Comprehensive trip fuel consumption logging, maintenance schedule alerts, and vehicle weight capacity enforcement.</p>
                    </div>
                </div>
            </div>

            <!-- 5. Digital Proof of Delivery -->
            <div class="col-md-4 reveal reveal-delay-2">
                <div class="img-fill-card">
                    <div class="img-fill-wrap">
                        <img src="https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=600&q=80" alt="Logistics coordinator inspecting delivery on tablet" loading="lazy">
                        <span class="img-fill-badge"><i class="bi bi-clipboard2-check me-1"></i> Verification</span>
                    </div>
                    <div class="img-fill-body">
                        <h4 class="fw-bold mb-2 text-dark">Digital Proof of Delivery</h4>
                        <p class="text-muted small mb-0">Instant paperless sign-off with photographic evidence, receiver signatures, weighbridge tickets, and tamper-proof timestamps.</p>
                    </div>
                </div>
            </div>

            <!-- 6. Inter-Provincial Corridors -->
            <div class="col-md-4 reveal reveal-delay-3">
                <div class="img-fill-card">
                    <div class="img-fill-wrap">
                        <img src="https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?w=600&q=80" alt="Heavy freight truck driving across highway" loading="lazy">
                        <span class="img-fill-badge"><i class="bi bi-signpost-split me-1"></i> Freight Route</span>
                    </div>
                    <div class="img-fill-body">
                        <h4 class="fw-bold mb-2 text-dark">Provincial Corridors</h4>
                        <p class="text-muted small mb-0">High-capacity freight transport linking Sarangani, South Cotabato, Sultan Kudarat, Davao Region, and Northern Mindanao.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Fleet Showcase Section with Picture Gallery -->
<section id="fleet" class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-lg-6 reveal">
                <span class="eyebrow">Our Modern Fleet</span>
                <h2 class="fw-bold mt-2 mb-3 fs-1" style="color: var(--navy);">Heavy-Duty Multi-Axle Hauling Capacity</h2>
                <p class="text-muted" style="line-height:1.7;">
                    Our fleet consists of well-maintained heavy cargo haulers, wing vans, and tractor heads calibrated for bulk cargo up to 40 metric tons. Every truck is equipped with GPS telemetry and subject to mandatory pre-dispatch safety inspections.
                </p>
            </div>
            <div class="col-lg-6 reveal reveal-delay-2 text-lg-end">
                <div class="d-inline-flex gap-2 flex-wrap">
                    <span class="badge bg-light text-dark border p-2 px-3"><i class="bi bi-shield-check text-success me-1"></i> ISO Safety Standards</span>
                    <span class="badge bg-light text-dark border p-2 px-3"><i class="bi bi-broadcast text-primary me-1"></i> Telematics Enabled</span>
                    <span class="badge bg-light text-dark border p-2 px-3"><i class="bi bi-speedometer text-warning me-1"></i> Scheduled Maintenance</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3 reveal reveal-delay-1">
                <div class="img-fill-card">
                    <div class="img-fill-wrap" style="height: 180px;">
                        <img src="https://images.unsplash.com/photo-1519003722824-194d4455a60c?w=500&q=80" alt="10-Wheeler Heavy Freight Carrier" loading="lazy">
                        <span class="img-fill-badge">10-Wheeler</span>
                    </div>
                    <div class="img-fill-body">
                        <strong class="text-dark d-block">Heavy Freight Carrier</strong>
                        <small class="text-muted d-block mb-2">25,000 kg Payload Capacity</small>
                        <p class="text-secondary small mb-0">Ideal for agricultural grains (corn, rice) and bulk dry goods.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 reveal reveal-delay-2">
                <div class="img-fill-card">
                    <div class="img-fill-wrap" style="height: 180px;">
                        <img src="https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?w=500&q=80" alt="Tractor Head with Lowbed Trailer" loading="lazy">
                        <span class="img-fill-badge">Tractor Head</span>
                    </div>
                    <div class="img-fill-body">
                        <strong class="text-dark d-block">Prime Mover &amp; Chassis</strong>
                        <small class="text-muted d-block mb-2">40,000 kg Payload Capacity</small>
                        <p class="text-secondary small mb-0">Direct 20ft &amp; 40ft container discharging from seaport vessel cranes.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 reveal reveal-delay-3">
                <div class="img-fill-card">
                    <div class="img-fill-wrap" style="height: 180px;">
                        <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=500&q=80" alt="Wing Van Cargo Truck" loading="lazy">
                        <span class="img-fill-badge">Wing Van</span>
                    </div>
                    <div class="img-fill-body">
                        <strong class="text-dark d-block">Secure Wing Van</strong>
                        <small class="text-muted d-block mb-2">28,000 kg Payload Capacity</small>
                        <p class="text-secondary small mb-0">Weatherproof transport for packaged goods, cement, and processed feed.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 reveal reveal-delay-4">
                <div class="img-fill-card">
                    <div class="img-fill-wrap" style="height: 180px;">
                        <img src="https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=500&q=80" alt="Bulk Tipper & Dump Truck" loading="lazy">
                        <span class="img-fill-badge">Bulk Tipper</span>
                    </div>
                    <div class="img-fill-body">
                        <strong class="text-dark d-block">Bulk Tipper / Dump</strong>
                        <small class="text-muted d-block mb-2">30,000 kg Payload Capacity</small>
                        <p class="text-secondary small mb-0">Fast hydraulic discharge for gravel, raw aggregates, and bulk soya.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Operations Hub & Port Photo Gallery -->
<section id="hubs" class="py-5" style="background: var(--background);">
    <div class="container py-4">
        <div class="text-center mx-auto mb-5 reveal" style="max-width:700px;">
            <span class="eyebrow">Strategic Corridors</span>
            <h2 class="fw-bold mt-2 fs-1" style="color: var(--navy);">Operations Hubs &amp; Seaport Terminals</h2>
            <p class="text-muted">Connecting coastal wharves with inland commercial centers across Southern Mindanao.</p>
        </div>

        <div class="photo-showcase-grid reveal">
            <div class="showcase-item col-span-2 row-span-2">
                <img src="https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=900&q=80" alt="Tagoloan Port Misamis Oriental" loading="lazy">
                <div class="showcase-overlay">
                    <span class="badge bg-warning text-dark align-self-start mb-2">Primary Hub</span>
                    <h4 class="fw-bold mb-1">Tagoloan Port &amp; Seaport Discharging</h4>
                    <p class="small text-secondary mb-0">Direct berth access for bulk grain vessels, international container ships, and inter-island barges.</p>
                </div>
            </div>
            <div class="showcase-item col-span-2">
                <img src="https://images.unsplash.com/photo-1587293852726-70cdb56c2866?w=900&q=80" alt="Inland distribution center" loading="lazy">
                <div class="showcase-overlay">
                    <h5 class="fw-bold mb-1">Inland Redistribution Warehouses</h5>
                    <p class="small text-secondary mb-0">High-bay staging facilities in Polomolok and Koronadal City.</p>
                </div>
            </div>
            <div class="showcase-item">
                <img src="https://images.unsplash.com/photo-1508974239320-0a029497e820?w=600&q=80" alt="Fleet maintenance depot" loading="lazy">
                <div class="showcase-overlay">
                    <h6 class="fw-bold mb-0">Depot &amp; Safety Bays</h6>
                </div>
            </div>
            <div class="showcase-item">
                <img src="https://images.unsplash.com/photo-1494412574643-ff11b0a5eb19?w=600&q=80" alt="Highway logistics corridor" loading="lazy">
                <div class="showcase-overlay">
                    <h6 class="fw-bold mb-0">Davao Freight Corridor</h6>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delivery Pipeline with Picture Steps -->
<section id="pipeline" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mx-auto mb-5 reveal" style="max-width:700px;">
            <span class="eyebrow">Delivery Lifecycle</span>
            <h2 class="fw-bold mt-2 fs-1" style="color: var(--navy);">How Live Tracking Works</h2>
            <p class="text-muted">Standardized transparency from booking request to final warehouse weigh-in.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3 reveal reveal-delay-1">
                <div class="step-photo-card">
                    <div class="step-photo-wrap">
                        <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&q=80" alt="Booking and shipment scheduling" loading="lazy">
                        <div class="step-number-badge">1</div>
                    </div>
                    <div class="p-3">
                        <strong class="text-dark d-block mb-1">1. Record &amp; Schedule</strong>
                        <p class="text-muted small mb-0">Contractor books shipment specifying cargo tonnage, origin pier, and delivery time window.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 reveal reveal-delay-2">
                <div class="step-photo-card">
                    <div class="step-photo-wrap">
                        <img src="https://images.unsplash.com/photo-1519003722824-194d4455a60c?w=400&q=80" alt="Dispatching truck and driver" loading="lazy">
                        <div class="step-number-badge">2</div>
                    </div>
                    <div class="p-3">
                        <strong class="text-dark d-block mb-1">2. Assign &amp; Dispatch</strong>
                        <p class="text-muted small mb-0">System matches vehicle capacity, verifies driver license status, and dispatches the unit.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 reveal reveal-delay-3">
                <div class="step-photo-card">
                    <div class="step-photo-wrap">
                        <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?w=400&q=80" alt="Truck in transit with GPS telematics" loading="lazy">
                        <div class="step-number-badge">3</div>
                    </div>
                    <div class="p-3">
                        <strong class="text-dark d-block mb-1">3. In Transit &amp; Telematics</strong>
                        <p class="text-muted small mb-0">Driver moves cargo along monitored highways with 30s GPS telematics and status checkpoints.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 reveal reveal-delay-4">
                <div class="step-photo-card">
                    <div class="step-photo-wrap">
                        <img src="https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=400&q=80" alt="Proof of delivery inspection and signoff" loading="lazy">
                        <div class="step-number-badge">4</div>
                    </div>
                    <div class="p-3">
                        <strong class="text-dark d-block mb-1">4. Proof of Delivery</strong>
                        <p class="text-muted small mb-0">Receiving warehouse inspects seals, verifies cargo weight, and uploads signed digital POD.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Full-Bleed Call to Action Banner -->
<section class="py-5" style="background: var(--background);" id="track">
    <div class="container py-4">
        <div class="cta-banner-filled reveal">
            <img class="cta-banner-img" src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1600&q=80" alt="Container truck terminal at night" loading="lazy">
            <div class="cta-banner-content text-center text-white">
                <div class="d-inline-flex align-items-center gap-2 mb-3 px-3 py-1 rounded-pill" style="background: rgba(245,200,66,0.15); border: 1px solid rgba(245,200,66,0.3); color: var(--gold); font-size: 13px;">
                    <span class="live-dot"></span> Live Telematics System
                </div>
                <h2 class="fw-bold mb-3 fs-1">Track Your Shipment in Real-Time</h2>
                <p class="text-secondary mx-auto mb-4" style="max-width: 600px;">
                    Enter your official tracking code to inspect live GPS coordinates, checkpoint timeline, and electronic proof of delivery receipts.
                </p>
                <form action="index.php" method="GET" class="hero-track-box mx-auto" style="max-width: 540px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
                    <input type="hidden" name="route" value="tracking">
                    <input type="text" name="number" placeholder="Enter tracking # (e.g. REGC-2026-000001)" required maxlength="60">
                    <button type="submit" class="btn btn-regc px-4">
                        <i class="bi bi-geo-alt-fill"></i> Track
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer id="contact" style="background: var(--navy-dark); color: white; padding: 60px 0 30px;">
    <div class="container">
        <div class="row g-5 mb-5">
            <div class="col-lg-5 reveal">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="brand-logo" style="width:42px; height:42px;">
                        <i class="bi bi-truck"></i>
                    </div>
                    <strong class="fs-4">REGC Fleet Management</strong>
                </div>
                <p class="text-secondary small" style="max-width:400px; line-height:1.7;">
                    Track. Deliver. Manage. Efficiently. Standardized cloud logistics management designed for commercial bulk freight and pier-to-warehouse hauling across the Philippine provinces.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,0.08); color:white; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:50%;"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,0.08); color:white; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:50%;"><i class="bi bi-envelope"></i></a>
                    <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,0.08); color:white; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:50%;"><i class="bi bi-telephone"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 reveal reveal-delay-1">
                <h6 class="text-uppercase fw-bold text-warning mb-3" style="letter-spacing:0.1em; font-size:12px;">Navigation</h6>
                <ul class="list-unstyled text-secondary small mb-0 d-flex flex-column gap-2">
                    <li><a href="index.php?route=tracking" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1"></i> Track Delivery</a></li>
                    <li><a href="index.php?route=login" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1"></i> Staff Sign In</a></li>
                    <li><a href="#about" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1"></i> About Us</a></li>
                    <li><a href="#services" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1"></i> Capabilities</a></li>
                    <li><a href="#fleet" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1"></i> Fleet Vehicles</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-sm-6 reveal reveal-delay-2">
                <h6 class="text-uppercase fw-bold text-warning mb-3" style="letter-spacing:0.1em; font-size:12px;">Contact Information</h6>
                <ul class="list-unstyled text-secondary small mb-0 d-flex flex-column gap-2">
                    <li><i class="bi bi-geo-alt text-warning me-2"></i> Tagoloan, Misamis Oriental, PH</li>
                    <li><i class="bi bi-telephone text-warning me-2"></i> (083) 552-0000 / +63 917-000-0000</li>
                    <li><i class="bi bi-envelope text-warning me-2"></i> dispatch@regctrucking.com</li>
                    <li><i class="bi bi-clock text-warning me-2"></i> Dispatch Operations: 24/7 Mon - Sat</li>
                </ul>
            </div>
        </div>
        <div class="border-top border-secondary pt-4 text-center text-secondary small">
            &copy; <?= date('Y'); ?> REGC Fleet Management & Shipment Tracking. All rights reserved. Cloud-Based Fleet Management System.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/app.js"></script>
<script>
// Scroll reveal observer
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.reveal:not(.visible)').forEach(el => observer.observe(el));

// Sticky header transition
window.addEventListener('scroll', () => {
    const header = document.getElementById('landing-header');
    header?.classList.toggle('scrolled', window.scrollY > 50);
}, { passive: true });
</script>
</body>
</html>
