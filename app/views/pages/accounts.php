<?php
function page_accounts(array $u): void {
    must_role(['super_admin']);

    $users     = rows("SELECT u.*, s.legal_name AS supplier_name, c.name AS coop_name, b.company_name AS buyer_name 
                      FROM users u 
                      LEFT JOIN suppliers s ON s.id=u.supplier_id 
                      LEFT JOIN cooperatives c ON c.id=u.coop_id 
                      LEFT JOIN buyer_profiles b ON b.id=u.buyer_id 
                      ORDER BY u.id DESC");

    $suppliers = rows("SELECT id, legal_name FROM suppliers WHERE status='verified' ORDER BY legal_name");
    $coops     = rows("SELECT id, name FROM cooperatives WHERE status='verified' ORDER BY name");
    $buyers    = rows("SELECT id, company_name FROM buyer_profiles WHERE status='verified' ORDER BY company_name");
?>

<!-- BANNER MODUL KELOLA AKUN -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1F6348 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-people-fill"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Manajemen Akun Pengguna & Hak Akses System</h5>
                <p class="mb-0 small text-white-50">Tambah akun baru, tautkan ke entitas mitra terverifikasi, serta atur status akses aktif pengguna.</p>
            </div>
        </div>
    </div>
</div>

<!-- FORM TAMBAH AKUN BARU (CREATE) -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-person-plus-fill me-2" style="color:#164B35"></i>Tambah Akun Pengguna Baru (Create)</h5>
        <p class="text-muted small mb-4">Buat akun untuk pengelola PT, Koperasi Anak, Pembeli Industri, atau Driver Armada Logistik.</p>

        <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_user">
            
            <div class="row g-3">
                <div class="col-md-4">
                    <?php field('Nama Lengkap Pengguna', 'name', 'text', '', 'required', 'Contoh: Ahmad Subagja'); ?>
                </div>
                <div class="col-md-4">
                    <?php field('Alamat Email (Login)', 'email', 'email', '', 'required', 'nama@perusahaan.com'); ?>
                </div>
                <div class="col-md-4">
                    <?php field('Kata Sandi (Password)', 'password', 'password', '', 'required', 'Maksimal 255 karakter'); ?>
                </div>

                <div class="col-md-3">
                    <?php select_field('Peran Akses (Role)', 'role', [
                        'super_admin' => 'Super Admin Platform',
                        'induk'       => 'Admin Koperasi Induk',
                        'supplier'    => 'PT Pemasok Limbah',
                        'coop'        => 'Koperasi Anak Olahan',
                        'buyer'       => 'Pembeli Industri B2B',
                        'driver'      => 'Driver Logistik'
                    ], '', 'required id="roleSelect" onchange="toggleRelasi()"'); ?>
                </div>

                <div class="col-md-3 rel-field" id="relSupplier" style="display:none;">
                    <?php select_field('Tautkan PT Pemasok', 'supplier_id', array_column($suppliers, 'legal_name', 'id'), '', 'id="supplierId"'); ?>
                </div>

                <div class="col-md-3 rel-field" id="relCoop" style="display:none;">
                    <?php select_field('Tautkan Koperasi Anak', 'coop_id', array_column($coops, 'name', 'id'), '', 'id="coopId"'); ?>
                </div>

                <div class="col-md-3 rel-field" id="relBuyer" style="display:none;">
                    <?php select_field('Tautkan Pembeli Industri', 'buyer_id', array_column($buyers, 'company_name', 'id'), '', 'id="buyerId"'); ?>
                </div>

                <div class="col-12 text-end mt-3">
                    <button class="btn btn-coco fw-bold px-4 py-2 shadow-sm" style="border-radius:10px;"><i class="bi bi-person-check-fill me-1"></i> Simpan Akun Baru</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRelasi() {
    let r = document.getElementById('roleSelect').value;
    document.querySelectorAll('.rel-field').forEach(el => el.style.display = 'none');
    if (r === 'supplier') document.getElementById('relSupplier').style.display = 'block';
    if (r === 'coop') document.getElementById('relCoop').style.display = 'block';
    if (r === 'buyer') document.getElementById('relBuyer').style.display = 'block';
}
toggleRelasi();
</script>

<!-- TABEL DAFTAR AKUN (READ, UPDATE & DISABLE) -->
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people me-2" style="color:#164B35"></i>Daftar Seluruh Pengguna System &bull; <?= count($users) ?></h5>
                <small class="text-muted">Kelola status aktif akun dan periksa entitas mitra terpaut.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama Pengguna</th>
                        <th>Email Login</th>
                        <th>Peran System</th>
                        <th>Entitas Terpaut</th>
                        <th>Status Akun</th>
                        <th class="text-center">Aksi (Update Status)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $usr): ?>
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark"><?= e($usr['name']) ?></strong>
                            <?php if ((int)$usr['id'] === (int)$u['id']): ?>
                                <span class="badge bg-secondary ms-1">Akun Anda</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="text-muted small"><i class="bi bi-envelope me-1"></i><?= e($usr['email']) ?></span></td>
                        <td><span class="badge bg-light text-dark border fw-semibold"><?= e(strtoupper($usr['role'])) ?></span></td>
                        <td>
                            <small class="fw-medium text-dark">
                                <?= e($usr['supplier_name'] ?: ($usr['coop_name'] ?: ($usr['buyer_name'] ?: '— (Tanpa Relasi Khusus)'))) ?>
                            </small>
                        </td>
                        <td>
                            <?php if ((int)$usr['is_active'] === 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="bi bi-x-circle me-1"></i>Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$usr['id'] !== (int)$u['id']): ?>
                                <?php if ((int)$usr['is_active'] === 1): ?>
                                    <?php action_form('toggle_user_status', (int)$usr['id'], 'Nonaktifkan', 'btn-outline-danger btn-sm', ['is_active' => '0']); ?>
                                <?php else: ?>
                                    <?php action_form('toggle_user_status', (int)$usr['id'], 'Aktifkan', 'btn-outline-success btn-sm', ['is_active' => '1']); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">Utama</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$users) table_empty('Belum ada akun terdaftar.'); ?>
        </div>
    </div>
</div>
<?php } ?>