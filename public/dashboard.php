<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/CoCoLinkSystem.php';

$user     = checkAuth();
$db       = (new Database())->getConnection();
$cocolink = new CoCoLinkSystem($db);

$message = '';
$error   = '';

// --- HANDLING AKSI FORM BERDASARKAN ROLE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    try {
        // 1. Buyer: Buat Pesanan Baru
        if ($action === 'create_order' && $user['role'] === 'buyer') {
            $cocolink->createOrder(
                buyerId: $user['id'],
                wasteCategoryId: (int)$_POST['waste_category_id'],
                quantity: (float)$_POST['quantity'],
                unitPrice: (float)$_POST['unit_price'],
                address: $_POST['address']
            );
            $message = 'Pesanan baru berhasil dibuat dan menunggu alokasi Koperasi Induk.';
        }

        // 2. Koperasi Induk: Dispatch Kuota ke Koperasi Anak
        if ($action === 'dispatch_order' && $user['role'] === 'parent_coop') {
            $orderId = (int)$_POST['order_id'];
            $allocations = [];
            foreach ($_POST['child_coop_id'] as $index => $childId) {
                if (!empty($childId) && !empty($_POST['quantity'][$index])) {
                    $allocations[] = [
                        'child_coop_id' => (int)$childId,
                        'quantity' => (float)$_POST['quantity'][$index]
                    ];
                }
            }
            $cocolink->dispatchAllocations($orderId, $allocations);
            $message = 'Target alokasi pasokan berhasil disalurkan ke Koperasi Anak.';
        }

        // 3. Driver: Klaim Logistik
        if ($action === 'claim_shipment' && $user['role'] === 'driver') {
            $shipmentId = (int)$_POST['shipment_id'];
            if ($cocolink->claimShipment($shipmentId, $user['id'])) {
                $message = 'Armada pengiriman berhasil diambil!';
            } else {
                $error = 'Gagal mengambil pengiriman. Mungkin sudah diambil driver lain.';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoCoLink - Dashboard <?= ucfirst(str_replace('_', ' ', $user['role'])) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <!-- Header Navigation -->
    <nav class="bg-emerald-800 text-white shadow-md px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <a href="index.php" class="text-2xl font-black tracking-wider">CoCoLink</a>
            <span class="bg-emerald-600 text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wide">
                <?= str_replace('_', ' ', $user['role']) ?>
            </span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm">Halo, <b><?= htmlspecialchars($user['name']) ?></b></span>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded-lg text-xs font-bold transition">Keluar</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6">
        
        <!-- Alert Notification -->
        <?php if (!empty($message)): ?>
            <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded mb-6 text-sm font-medium">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded mb-6 text-sm font-medium">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- ================================================================= -->
        <!-- DASHBOARD ROLE 1: BUYER (PENGEPUL INDUSTRI)                      -->
        <!-- ================================================================= -->
        <?php if ($user['role'] === 'buyer'): ?>
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Form Buat Order -->
                <div class="bg-white p-6 rounded-2xl shadow border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">+ Buat Pesanan Limbah Baru</h3>
                    <form action="dashboard.php" method="POST" class="space-y-4">
                        <input type="hidden" name="form_action" value="create_order">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori Limbah</label>
                            <select name="waste_category_id" required class="w-full p-2.5 border rounded-lg text-sm">
                                <?php
                                $categories = $db->query("SELECT * FROM waste_categories")->fetchAll();
                                foreach ($categories as $cat):
                                ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?> (per <?= $cat['unit'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Pesanan (Ton)</label>
                            <input type="number" step="0.1" name="quantity" required placeholder="50.0" class="w-full p-2.5 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Harga Penawaran per Ton (Rp)</label>
                            <input type="number" name="unit_price" required placeholder="2000000" class="w-full p-2.5 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Pengiriman/Pabrik</label>
                            <textarea name="address" required rows="2" class="w-full p-2.5 border rounded-lg text-sm" placeholder="Kawasan Industri..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 text-white font-bold py-2.5 rounded-lg hover:bg-emerald-700 text-sm">Kirim Pesanan</button>
                    </form>
                </div>

                <!-- Daftar Riwayat Pesanan Saya -->
                <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Riwayat Pesanan Saya</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                                <tr>
                                    <th class="p-3">No. Order</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3">Jumlah</th>
                                    <th class="p-3">Total Harga</th>
                                    <th class="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php
                                $myOrders = $db->prepare("SELECT o.*, w.name as waste_name FROM orders o JOIN waste_categories w ON o.waste_category_id = w.id WHERE o.buyer_id = ? ORDER BY o.id DESC");
                                $myOrders->execute([$user['id']]);
                                foreach ($myOrders->fetchAll() as $row):
                                ?>
                                    <tr>
                                        <td class="p-3 font-bold text-emerald-700"><?= $row['order_number'] ?></td>
                                        <td class="p-3"><?= $row['waste_name'] ?></td>
                                        <td class="p-3"><?= number_format($row['total_quantity'], 1) ?> Ton</td>
                                        <td class="p-3">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                                        <td class="p-3"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs uppercase font-bold"><?= $row['status'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ================================================================= -->
        <!-- DASHBOARD ROLE 2: KOPERASI INDUK (CENTRAL OPERATIONS)             -->
        <!-- ================================================================= -->
        <?php elseif ($user['role'] === 'parent_coop'): ?>
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Pusat Operasi: Alokasi Pesanan Pengepul ke Koperasi Anak</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                                <tr>
                                    <th class="p-3">No. Order</th>
                                    <th class="p-3">Pengepul</th>
                                    <th class="p-3">Limbah</th>
                                    <th class="p-3">Jumlah (Ton)</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Aksi Dispatch</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php
                                $allOrders = $db->query("SELECT o.*, u.name as buyer_name, w.name as waste_name FROM orders o JOIN users u ON o.buyer_id = u.id JOIN waste_categories w ON o.waste_category_id = w.id ORDER BY o.id DESC")->fetchAll();
                                $childCoops = $db->query("SELECT id, name FROM users WHERE role = 'child_coop'")->fetchAll();
                                foreach ($allOrders as $ord):
                                ?>
                                    <tr>
                                        <td class="p-3 font-bold text-emerald-700"><?= $ord['order_number'] ?></td>
                                        <td class="p-3"><?= $ord['buyer_name'] ?></td>
                                        <td class="p-3"><?= $ord['waste_name'] ?></td>
                                        <td class="p-3 font-bold"><?= $ord['total_quantity'] ?> Ton</td>
                                        <td class="p-3"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-bold uppercase"><?= $ord['status'] ?></span></td>
                                        <td class="p-3">
                                            <?php if ($ord['status'] === 'pending'): ?>
                                                <form action="dashboard.php" method="POST" class="space-y-2 bg-gray-50 p-3 rounded border">
                                                    <input type="hidden" name="form_action" value="dispatch_order">
                                                    <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                    <p class="text-xs font-bold text-gray-700">Bagi Kuota Ke Koperasi Anak:</p>
                                                    <div class="flex gap-2">
                                                        <select name="child_coop_id[]" class="p-1 border text-xs rounded">
                                                            <option value="">-- Pilih Koperasi --</option>
                                                            <?php foreach ($childCoops as $cc): ?>
                                                                <option value="<?= $cc['id'] ?>"><?= $cc['name'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <input type="number" step="0.1" name="quantity[]" placeholder="Qty (Ton)" class="p-1 border text-xs rounded w-24">
                                                    </div>
                                                    <button type="submit" class="bg-emerald-600 text-white text-xs px-3 py-1 rounded font-bold hover:bg-emerald-700">Salurkan Alokasi</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">Sudah Dialokasikan</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ================================================================= -->
        <!-- DASHBOARD ROLE 3: KOPERASI ANAK (SUPPLIER PASOKAN)               -->
        <!-- ================================================================= -->
        <?php elseif ($user['role'] === 'child_coop'): ?>
            <div class="bg-white p-6 rounded-2xl shadow border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Target Alokasi Pasokan Kelapa Diterima</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="p-3">No. Order</th>
                                <th class="p-3">Jenis Limbah</th>
                                <th class="p-3">Kuota Target</th>
                                <th class="p-3">Status Pengadaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php
                            $allocs = $db->prepare("
                                SELECT a.*, o.order_number, w.name as waste_name 
                                FROM order_allocations a 
                                JOIN orders o ON a.order_id = o.id 
                                JOIN waste_categories w ON o.waste_category_id = w.id 
                                WHERE a.child_coop_id = ? 
                                ORDER BY a.id DESC
                            ");
                            $allocs->execute([$user['id']]);
                            foreach ($allocs->fetchAll() as $al):
                            ?>
                                <tr>
                                    <td class="p-3 font-bold text-emerald-700"><?= $al['order_number'] ?></td>
                                    <td class="p-3"><?= $al['waste_name'] ?></td>
                                    <td class="p-3 font-bold"><?= $al['allocated_quantity'] ?> Ton</td>
                                    <td class="p-3"><span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded text-xs font-bold uppercase"><?= $al['status'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- ================================================================= -->
        <!-- DASHBOARD ROLE 4: DRIVER LOGISTIK                                 -->
        <!-- ================================================================= -->
        <?php elseif ($user['role'] === 'driver'): ?>
            <div class="bg-white p-6 rounded-2xl shadow border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Papan Penugasan Pengiriman Logistik Tersedia</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="p-3">No. Tracking</th>
                                <th class="p-3">Koperasi Asal</th>
                                <th class="p-3">Jumlah Muatan</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php
                            $shipments = $db->query("
                                SELECT s.*, a.allocated_quantity, u.name as child_coop_name 
                                FROM shipments s 
                                JOIN order_allocations a ON s.allocation_id = a.id 
                                JOIN users u ON a.child_coop_id = u.id 
                                ORDER BY s.id DESC
                            ")->fetchAll();
                            foreach ($shipments as $shp):
                            ?>
                                <tr>
                                    <td class="p-3 font-bold text-emerald-700"><?= $shp['tracking_number'] ?></td>
                                    <td class="p-3"><?= $shp['child_coop_name'] ?></td>
                                    <td class="p-3 font-bold"><?= $shp['allocated_quantity'] ?> Ton</td>
                                    <td class="p-3"><span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-bold uppercase"><?= $shp['status'] ?></span></td>
                                    <td class="p-3">
                                        <?php if ($shp['status'] === 'waiting_driver'): ?>
                                            <form action="dashboard.php" method="POST">
                                                <input type="hidden" name="form_action" value="claim_shipment">
                                                <input type="hidden" name="shipment_id" value="<?= $shp['id'] ?>">
                                                <button type="submit" class="bg-emerald-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-emerald-700">Ambil Pengiriman</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">Diambil oleh Driver ID: <?= $shp['driver_id'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>