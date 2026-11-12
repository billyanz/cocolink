<?php
function page_dashboard(array $u): void {
    $roleName = match($u['role']) {
        'super_admin' => 'Super Admin Platform (Master Monitoring)',
        'induk'       => 'Admin Koperasi Induk Metropolitan',
        'supplier'    => 'PT Pemasok Limbah Utama',
        'coop'        => 'Koperasi Anak Olahan Sektor Wilayah',
        'buyer'       => 'Pembeli Industri B2B',
        'driver'      => 'Driver Armada Logistik',
        default       => strtoupper($u['role'])
    };

    $isSuper  = $u['role'] === 'super_admin';
    $isInduk  = $u['role'] === 'induk';
    $isSupplier = in_array($u['role'], ['supplier', 'induk'], true);
    $isCoop   = $u['role'] === 'coop';
    $isBuyer  = $u['role'] === 'buyer';
    $isDriver = $u['role'] === 'driver';

    // Perhitungan KPI Dinamis Terisolasi Per Role
    if ($isSuper || $isInduk) {
        $totSuppliers = val("SELECT COUNT(*) FROM suppliers WHERE status='verified'");
        $totCoops     = val("SELECT COUNT(*) FROM cooperatives WHERE status='verified'");
        $totBatches   = val("SELECT COALESCE(SUM(quantity),0) FROM raw_batches");
        $onGoing      = val("SELECT COUNT(*) FROM orders WHERE status IN ('approved','assigned','in_transit','pickup_pending')");
        $completed    = val("SELECT COUNT(*) FROM orders WHERE status='completed'");
    } elseif ($u['supplier_id']) {
        $sid = (int)$u['supplier_id'];
        $totSuppliers = 1;
        $totCoops     = val("SELECT COUNT(*) FROM cooperatives WHERE supplier_id=? AND status='verified'", [$sid]);
        $totBatches   = val("SELECT COALESCE(SUM(quantity),0) FROM raw_batches WHERE supplier_id=?", [$sid]);
        $onGoing      = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE c.supplier_id=? AND o.status IN ('approved','assigned','in_transit','pickup_pending')", [$sid]);
        $completed    = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE c.supplier_id=? AND o.status='completed'", [$sid]);
    } elseif ($isCoop) {
        $cid = (int)$u['coop_id'];
        $totSuppliers = 1;
        $totCoops     = 1;
        $totBatches   = val("SELECT COALESCE(SUM(quantity),0) FROM allocations WHERE coop_id=? AND status='accepted'", [$cid]);
        $onGoing      = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id WHERE p.coop_id=? AND o.status IN ('approved','assigned','in_transit','pickup_pending')", [$cid]);
        $completed    = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id WHERE p.coop_id=? AND o.status='completed'", [$cid]);
    } else {
        $totSuppliers = val("SELECT COUNT(*) FROM suppliers WHERE status='verified'");
        $totCoops     = val("SELECT COUNT(*) FROM cooperatives WHERE status='verified'");
        $totBatches   = 0;
        $onGoing      = val("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status IN ('approved','assigned','in_transit')", [(int)($u['buyer_id'] ?? 0)]);
        $completed    = val("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status='completed'", [(int)($u['buyer_id'] ?? 0)]);
    }
?>

<!-- ASSETS GOOGLE FONT (PLUS JAKARTA SANS) & CHART.JS -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .dashboard-container {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0F172A;
    }
    .ocean-banner-card {
        background: linear-gradient(135deg, #0A2540 0%, #005691 50%, #0077B6 100%);
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        box-shadow: 0 12px 28px -6px rgba(0, 86, 145, 0.28);
    }
    
    /* VECTOR SILUET OMBAK & KELAPA (OPACITY ~0.12 SAMAR) */
    .banner-silhouette-bg {
        position: absolute;
        top: 0; right: 0; bottom: 0; left: 0;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }
    .banner-silhouette-bg svg {
        position: absolute;
        right: -20px;
        bottom: -20px;
        height: 125%;
        width: auto;
        opacity: 0.12;
        fill: #FFFFFF;
    }

    .role-badge-crisp {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #FFFFFF;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }
    .banner-title {
        color: #FFFFFF;
        font-weight: 800;
        font-size: 1.85rem;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .banner-subtitle {
        color: #E0F2FE;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .btn-banner-action {
        background-color: #FFFFFF;
        color: #0077B6;
        font-weight: 700;
        border-radius: 12px;
        padding: 0.7rem 1.5rem;
        border: none;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        transition: all 0.25s ease;
    }
    .btn-banner-action:hover {
        background-color: #F0F9FF;
        color: #0284C7;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    /* KPI CARD HIGH CONTRAST */
    .kpi-card-modern {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        transition: all 0.25s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .kpi-card-modern:hover {
        border-color: #CBD5E1;
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.06);
        transform: translateY(-2px);
    }
    .kpi-label {
        color: #475569;
        font-weight: 600;
        font-size: 0.825rem;
        letter-spacing: 0.3px;
        margin-bottom: 0.35rem;
    }
    .kpi-number {
        color: #0F172A;
        font-weight: 800;
        font-size: 1.8rem;
        line-height: 1.2;
    }
    .kpi-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
</style>

<div class="dashboard-container">
    <!-- KARTU BANNER OCEAN UTAMA -->
    <div class="card border-0 mb-4 ocean-banner-card">
        <!-- BACKGROUND VECTOR SILUET OMBAK & KELAPA -->
        <div class="banner-silhouette-bg">
            <svg viewBox="0 0 500 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <!-- Vektor Gelombang Laut -->
                <path d="M 0,220 Q 125,180 250,220 T 500,220 L 500,300 L 0,300 Z" />
                <path d="M 0,240 Q 125,210 250,240 T 500,240 L 500,300 L 0,300 Z" opacity="0.6" />
                <!-- Vektor Pohon Kelapa -->
                <g transform="translate(310, 10) scale(0.95)">
                    <path d="M 140 280 Q 125 180 145 100 Q 152 100 148 280 Z" />
                    <path d="M 145 100 Q 65 70 15 90 Q 65 60 145 100 Z" />
                    <path d="M 145 100 Q 85 30 35 20 Q 85 25 145 100 Z" />
                    <path d="M 145 100 Q 165 20 225 15 Q 170 30 145 100 Z" />
                    <path d="M 145 100 Q 215 50 265 80 Q 200 70 145 100 Z" />
                    <path d="M 145 100 Q 225 110 275 150 Q 205 120 145 100 Z" />
                    <path d="M 145 100 Q 75 120 25 160 Q 75 130 145 100 Z" />
                    <circle cx="137" cy="108" r="8" />
                    <circle cx="150" cy="112" r="9" />
                    <circle cx="143" cy="120" r="7.5" />
                </g>
            </svg>
        </div>

        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 1;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
                <div>
                    <!-- BADGE KREDENSIAL DENGAN KONTRAS TINGGI -->
                    <div class="role-badge-crisp mb-3">
                        <i class="bi bi-shield-check"></i> KREDENSIAL: <?= e(strtoupper($roleName)) ?>
                    </div>
                    
                    <h2 class="banner-title mb-2">Selamat Datang, <?= e($u['name']) ?>! 👋</h2>
                    <p class="banner-subtitle mb-0">Pusat Monitoring Ekosistem Sirkular Kelapa CoCoLink Wilayah Jabodetabek.</p>
                </div>
                <div>
                    <a href="<?= url('maps') ?>" class="btn btn-banner-action d-inline-flex align-items-center gap-2">
                        <i class="bi bi-map-fill fs-5"></i>
                        <span>Buka Peta Live Logistik</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- METRIK KPI UTAMA HIGH CONTRAST -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Transaksi On-Going</div>
                    <div class="kpi-number"><?= number((float)$onGoing) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#FEF3C7; color:#D97706;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Transaksi Selesai</div>
                    <div class="kpi-number"><?= number((float)$completed) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#DCFCE7; color:#15803D;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label"><?= $isCoop ? 'Pasokan Diterima (kg)' : 'Total Pasokan (kg)' ?></div>
                    <div class="kpi-number"><?= number((float)$totBatches) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#E0F2FE; color:#0369A1;">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Koperasi Sektor Aktif</div>
                    <div class="kpi-number"><?= number((float)$totCoops) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#E0E7FF; color:#4338CA;">
                    <i class="bi bi-shop"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- GRAFIK ANALITIK CHART.JS (OCEAN PALETTE) -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:18px; border: 1px solid #E2E8F0;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4" style="color:#0F172A; font-size:1.1rem;"><i class="bi bi-pie-chart-fill me-2" style="color:#0077B6"></i>Komposisi Kategori Limbah Sektor</h5>
                    <div style="height: 280px; position: relative;">
                        <canvas id="limbahChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:18px; border: 1px solid #E2E8F0;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4" style="color:#0F172A; font-size:1.1rem;"><i class="bi bi-graph-up-arrow me-2" style="color:#0077B6"></i>Tren Volume Transaksi B2B (Jabodetabek)</h5>
                    <div style="height: 280px; position: relative;">
                        <canvas id="trenChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT INISIALISASI CHART.JS DENGAN WANA DENGAN KONTRAS DARI TEKS -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

        // 1. Chart Donat Komposisi Limbah
        const ctxLimbah = document.getElementById('limbahChart').getContext('2d');
        new Chart(ctxLimbah, {
            type: 'doughnut',
            data: {
                labels: ['Daun & Lidi', 'Tempurung', 'Sabut', 'Batang & Kayu', 'Air & Ampas'],
                datasets: [{
                    data: [40, 25, 20, 10, 5],
                    backgroundColor: ['#0A2540', '#0077B6', '#00B4D8', '#38BDF8', '#7DD3FC'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { color: '#334155', font: { weight: '600', size: 12 }, padding: 15 } 
                    }
                },
                cutout: '68%'
            }
        });

        // 2. Chart Batang Tren Transaksi
        const ctxTren = document.getElementById('trenChart').getContext('2d');
        new Chart(ctxTren, {
            type: 'bar',
            data: {
                labels: ['Jul', 'Agu', 'Sep', 'Okt', 'Nov (Aktif)'],
                datasets: [{
                    label: 'Volume Pasokan (Ton)',
                    data: [12, 19, 25, 32, 45],
                    backgroundColor: '#0077B6',
                    hoverBackgroundColor: '#00B4D8',
                    borderRadius: 8,
                    barThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#F1F5F9' }, 
                        ticks: { color: '#475569', font: { weight: '500' } } 
                    },
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: '#475569', font: { weight: '600' } } 
                    }
                }
            }
        });
    });
    </script>

    <!-- ALUR OPERASIONAL SIRKULAR EKOSISTEM -->
    <div class="card border-0 shadow-sm" style="border-radius:18px; border: 1px solid #E2E8F0;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4" style="color:#0F172A; font-size:1.1rem;"><i class="bi bi-diagram-3-fill me-2" style="color:#0077B6"></i>Alur Operasional Sirkular Ekosistem Jabodetabek</h5>
            
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <div class="p-3.5 border rounded-3 h-100" style="background:#F8FAFC; border-color:#E2E8F0 !important;">
                        <strong class="d-block mb-2" style="color:#0F172A; font-weight:700;"><i class="bi bi-building me-2 fs-5" style="color:#0A2540;"></i> 1. Induk & PT Utama</strong>
                        <p class="small mb-0" style="color:#475569; line-height:1.5;">PT Pemasok Utama mengumpulkan limbah mentah dan didampingi Koperasi Induk Metropolitan untuk legalitas.</p>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="p-3.5 border rounded-3 h-100" style="background:#F8FAFC; border-color:#E2E8F0 !important;">
                        <strong class="d-block mb-2" style="color:#0F172A; font-weight:700;"><i class="bi bi-geo-alt me-2 fs-5" style="color:#0077B6;"></i> 2. Distro 5 Sektor</strong>
                        <p class="small mb-0" style="color:#475569; line-height:1.5;">Pasokan limbah dialokasikan ke 5 Koperasi Anak per sektor wilayah (JakTim, JakSel, JakBar, JakPus, JakUt).</p>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="p-3.5 border rounded-3 h-100" style="background:#F8FAFC; border-color:#E2E8F0 !important;">
                        <strong class="d-block mb-2" style="color:#0F172A; font-weight:700;"><i class="bi bi-tools me-2 fs-5" style="color:#00B4D8;"></i> 3. Olahan Barang B2B</strong>
                        <p class="small mb-0" style="color:#475569; line-height:1.5;">Koperasi Sektor mengolah limbah menjadi produk siap pakai (Sapu Lidi, Briket) dan diposting ke katalog B2B.</p>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="p-3.5 border rounded-3 h-100" style="background:#F8FAFC; border-color:#E2E8F0 !important;">
                        <strong class="d-block mb-2" style="color:#0F172A; font-weight:700;"><i class="bi bi-truck me-2 fs-5" style="color:#38BDF8;"></i> 4. Live Tracking Driver</strong>
                        <p class="small mb-0" style="color:#475569; line-height:1.5;">Driver armada mengambil barang di gudang sektor dan mengantarkan ke Industri Pembeli dengan peta live GPS.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>