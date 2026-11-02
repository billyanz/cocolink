<?php
function page_accounts(): void {
    $users = rows('SELECT u.*, s.legal_name, c.name AS coop_name, b.company_name 
                   FROM users u 
                   LEFT JOIN suppliers s ON s.id=u.supplier_id 
                   LEFT JOIN cooperatives c ON c.id=u.coop_id 
                   LEFT JOIN buyer_profiles b ON b.id=u.buyer_id 
                   ORDER BY u.id DESC');
                   
    $s = rows('SELECT id, legal_name FROM suppliers ORDER BY legal_name');
    $c = rows('SELECT id, name FROM cooperatives ORDER BY name');
    $b = rows('SELECT id, company_name FROM buyer_profiles ORDER BY company_name');
?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="panel p-4 bg-white border" style="border-radius:16px;">
            <h5 class="fw-bold mb-1">Tambah Akun Mitra / Staf</h5>
            <p class="helper mb-3">Super Admin membuat akun Koperasi Induk, Koperasi Anak, Driver, atau staf tambahan.</p>
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_user">
                <div class="mb-3"><?php field('Nama Lengkap', 'name'); ?></div>
                <div class="mb-3"><?php field('Email Address', 'email', 'email'); ?></div>
                <div class="mb-3"><?php field('Password (minimal 8 karakter)', 'password', 'password', '', 'required minlength="8"'); ?></div>
                <div class="mb-3">
                    <?php select_field('Role / Peran', 'role', [
                        'super_admin' => 'Super Admin',
                        'induk'       => 'Admin Koperasi Induk',
                        'supplier'    => 'PT Pemasok',
                        'coop'        => 'Koperasi Anak',
                        'buyer'       => 'Pembeli Industri',
                        'driver'      => 'Driver Logistik'
                    ], '', 'required onchange="changeRole(this.value)"'); ?>
                </div>
                <div id="supplier-assoc" class="mb-3 d-none">
                    <?php select_field('PT Terkait', 'supplier_id', array_column($s, 'legal_name', 'id'), '', ''); ?>
                </div>
                <div id="coop-assoc" class="mb-3 d-none">
                    <?php select_field('Koperasi Terkait', 'coop_id', array_column($c, 'name', 'id'), '', ''); ?>
                </div>
                <div id="buyer-assoc" class="mb-3 d-none">
                    <?php select_field('Pembeli Terkait', 'buyer_id', array_column($b, 'company_name', 'id'), '', ''); ?>
                </div>
                <button class="btn btn-coco w-100 fw-bold py-2">Buat Akun Pengguna</button>
            </form>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel bg-white border" style="border-radius:16px; overflow:hidden;">
            <div class="p-3 border-bottom">
                <h5 class="fw-bold mb-0">Semua Pengguna &bull; <?= count($users) ?></h5>
                <p class="helper mb-0">Akun dapat dinonaktifkan kapan saja tanpa menghapus histori audit data.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Peran</th>
                            <th>Entitas Associated</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $v): ?>
                        <tr>
                            <td>
                                <b><?= e($v['name']) ?></b>
                                <div class="helper small text-muted"><?= e($v['email']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($v['role']) ?></span></td>
                            <td><?= e($v['legal_name'] ?? $v['coop_name'] ?? $v['company_name'] ?? '—') ?></td>
                            <td>
                                <?= ((int)$v['is_active']) 
                                    ? '<span class="badge text-bg-success">Aktif</span>' 
                                    : '<span class="badge text-bg-secondary">Nonaktif</span>' ?>
                            </td>
                            <td>
                                <?php if ($v['id'] != $_SESSION['uid']) 
                                    action_form('toggle_user', (int)$v['id'], (int)$v['is_active'] ? 'Nonaktifkan' : 'Aktifkan', 'btn-outline-secondary btn-sm'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function changeRole(role) {
    ['supplier', 'coop', 'buyer'].forEach(r => {
        const el = document.getElementById(r + '-assoc');
        if (el) el.classList.toggle('d-none', r !== role);
    });
}
</script>
<?php } ?>