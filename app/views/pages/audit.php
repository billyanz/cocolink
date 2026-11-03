<?php
function page_audit(array $u = []): void {
    $logs = rows('SELECT l.*, u.name AS user_name FROM audit_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 250');
    $totalLogs = count($logs);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .audit-page-container {
        font-family: 'Plus Jakarta Sans', sans-serif; color: #0F172A;
        animation: fadeIn 0.4s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ocean-banner-card {
        background: linear-gradient(135deg, #0A2540 0%, #005691 50%, #0077B6 100%);
        position: relative; overflow: hidden; border-radius: 20px;
        box-shadow: 0 12px 28px -6px rgba(0, 86, 145, 0.28);
    }
    .banner-silhouette-bg {
        position: absolute; top: 0; right: 0; bottom: 0; left: 0;
        pointer-events: none; z-index: 0; overflow: hidden;
    }
    .banner-silhouette-bg svg {
        position: absolute; right: -20px; bottom: -20px; height: 125%; width: auto; opacity: 0.10; fill: #FFFFFF;
    }

    .role-badge-crisp {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255, 255, 255, 0.16); backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35); color: #FFFFFF;
        padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    }
    .banner-title { color: #FFFFFF; font-weight: 800; font-size: 1.85rem; letter-spacing: -0.5px; }
    .banner-subtitle { color: #E0F2FE; font-size: 0.95rem; font-weight: 500; }

    .section-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: all 0.25s ease;
    }
    .ip-chip {
        font-family: monospace; background: #F1F5F9; border: 1px solid #CBD5E1;
        padding: 2px 8px; border-radius: 6px; font-size: 0.78rem; color: #475569;
    }
</style>

<div class="audit-page-container">
    <div class="card border-0 mb-4 ocean-banner-card">
        <div class="banner-silhouette-bg">
            <svg viewBox="0 0 500 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <path d="M 0,220 Q 125,180 250,220 T 500,220 L 500,300 L 0,300 Z" />
                <path d="M 0,240 Q 125,210 250,240 T 500,240 L 500,300 L 0,300 Z" opacity="0.6" />
                <g transform="translate(310, 10) scale(0.95)">
                    <path d="M 140 280 Q 125 180 145 100 Q 152 100 148 280 Z" />
                    <path d="M 145 100 Q 65 70 15 90 Q 65 60 145 100 Z" />
                    <path d="M 145 100 Q 85 30 35 20 Q 85 25 145 100 Z" />
                    <path d="M 145 100 Q 165 20 225 15 Q 170 30 145 100 Z" />
                    <path d="M 145 100 Q 215 50 265 80 Q 200 70 145 100 Z" />
                    <path d="M 145 100 Q 225 110 275 150 Q 205 120 145 100 Z" />
                    <path d="M 145 100 Q 75 120 25 160 Q 75 130 145 100 Z" />
                    <circle cx="137" cy="108" r="8" /><circle cx="150" cy="112" r="9" /><circle cx="143" cy="120" r="7.5" />
                </g>
            </svg>
        </div>

        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 1;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="role-badge-crisp mb-3"><i class="bi bi-journal-text"></i> SYSTEM AUDIT TRAIL</div>
                    <h2 class="banner-title mb-2">Audit Log Aktivitas Sistem 📜</h2>
                    <p class="banner-subtitle mb-0">Audit trail menyimpan jenis tindakan, pelaku, entitas terkait, IP address, dan timestamp aktivitas real-time.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 section-card overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check me-2" style="color:#0077B6"></i>Catatan Audit Aktivitas &bull; <?= $totalLogs ?> Terakhir</h5>
                <span class="badge bg-white text-secondary border px-3 py-1.5 rounded-pill small">System Security Audit</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#F1F5F9; font-size:0.8rem; color:#475569; text-transform:uppercase;">
                        <tr>
                            <th class="ps-4 py-3">Timestamp Waktu</th>
                            <th>Pengguna (Pelaku)</th>
                            <th>Tindakan System</th>
                            <th>Entitas Terkait</th>
                            <th>Detail Keterangan</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="ps-4 small text-muted"><i class="bi bi-clock me-1"></i><?= e($l['created_at']) ?></td>
                            <td><strong class="text-dark"><?= e($l['user_name'] ?? 'Sistem Automatic') ?></strong></td>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2.5 py-1 rounded-pill"><?= e($l['action']) ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= e($l['entity_type'] . ' #' . $l['entity_id']) ?></span></td>
                            <td><small class="text-secondary"><?= e($l['details']) ?></small></td>
                            <td><span class="ip-chip"><?= e($l['ip']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$logs): ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Catatan Audit Logs</h6>
                        <p class="text-secondary small mb-0">Aktivitas pengelola akan otomatis dicatat di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>