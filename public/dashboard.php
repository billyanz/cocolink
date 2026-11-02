<?php
// public/dashboard.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$user = requireAuth();
$pdo  = getDBConnection();

// Role-specific database data retrieval
$role = $user['role'];
$coopId = $user['cooperative_id'];

// Global Metrics
$orders = [];
$allocations = [];
$shipments = [];
$discrepancies = [];
$inventory = [];

if ($role === 'induk') {
    $orders = $pdo->query("SELECT o.*, u.name as buyer_name, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o JOIN users u ON o.buyer_id = u.id ORDER BY o.id DESC")->fetchAll();
    $inventory = $pdo->query("SELECT i.*, c.name as coop_name, m.name as material_name, m.unit FROM inventory i JOIN cooperatives c ON i.cooperative_id = c.id JOIN materials m ON i.material_id = m.id")->fetchAll();
    $discrepancies = $pdo->query("SELECT d.*, s.tracking_number, u.name as reporter_name FROM discrepancies d JOIN shipments s ON d.shipment_id = s.id JOIN users u ON d.reported_by = u.id WHERE d.status = 'reported'")->fetchAll();
} elseif ($role === 'anak') {
    $allocations = $pdo->prepare("SELECT a.*, o.order_number, m.name as material_name, m.unit FROM allocations a JOIN order_items oi ON a.order_item_id = oi.id JOIN orders o ON oi.order_id = o.id JOIN materials m ON oi.material_id = m.id WHERE a.cooperative_id = ? ORDER BY a.id DESC");
    $allocations->execute([$coopId]);
    $allocations = $allocations->fetchAll();

    $inventory = $pdo->prepare("SELECT i.*, m.name as material_name, m.unit FROM inventory i JOIN materials m ON i.material_id = m.id WHERE i.cooperative_id = ?");
    $inventory->execute([$coopId]);
    $inventory = $inventory->fetchAll();
} elseif ($role === 'buyer') {
    $orders = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY id DESC");
    $orders->execute([$user['id']]);
    $orders = $orders->fetchAll();

    $shipments = $pdo->prepare("SELECT s.*, o.order_number, m.name as material_name FROM shipments s JOIN allocations a ON s.allocation_id = a.id JOIN order_items oi ON a.order_item_id = oi.id JOIN orders o ON oi.order_id = o.id JOIN materials m ON oi.material_id = m.id WHERE o.buyer_id = ? ORDER BY s.id DESC");
    $shipments->execute([$user['id']]);
    $shipments = $shipments->fetchAll();
} elseif ($role === 'driver') {
    $shipments = $pdo->prepare("SELECT s.*, c.name as coop_name, o.shipping_address, m.name as material_name FROM shipments s JOIN allocations a ON s.allocation_id = a.id JOIN cooperatives c ON a.cooperative_id = c.id JOIN order_items oi ON a.order_item_id = oi.id JOIN orders o ON oi.order_id = o.id JOIN materials m ON oi.material_id = m.id WHERE s.driver_id = ? ORDER BY s.id DESC");
    $shipments->execute([$user['id']]);
    $shipments = $shipments->fetchAll();
}

$materials = $pdo->query("SELECT * FROM materials")->fetchAll();
$cooperatives = $pdo->query("SELECT * FROM cooperatives WHERE type = 'anak'")->fetchAll();
$drivers = $pdo->query("SELECT * FROM users WHERE role = 'driver'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CoCoLink - Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans">
    <nav class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center shadow-md">
        <div class="flex items-center space-x-3">
            <span class="text-2xl">🌴</span>
            <div>
                <span class="font-bold text-lg text-emerald-400">CoCoLink</span>
                <span class="text-xs text-slate-400 ml-2 uppercase font-semibold border-l border-slate-700 pl-2">Role: <?= strtoupper($role); ?></span>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <div class="text-right">
                <div class="text-sm font-semibold"><?= htmlspecialchars($user['name']); ?></div>
                <div class="text-xs text-slate-400"><?= htmlspecialchars($user['email']); ?></div>
            </div>
            <a href="logout.php" class="bg-slate-800 hover:bg-red-600 text-slate-200 hover:text-white px-3 py-1.5 rounded text-xs font-bold transition">Logout</a>
        </div>
    </nav>

    <div class="p-6 max-w-7xl mx-auto w-full flex-grow">
        <?php if ($role === 'induk'): ?>
            <!-- Central Induk Dashboard -->
            <h2 class="text-xl font-bold mb-4 text-slate-800">Central Operations Dashboard</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-xs uppercase font-bold text-slate-400">Total Orders</div>
                    <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= count($orders); ?></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-xs uppercase font-bold text-slate-400">Pending Discrepancies</div>
                    <div class="text-3xl font-extrabold text-amber-600 mt-1"><?= count($discrepancies); ?></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-xs uppercase font-bold text-slate-400">Active Cooperatives</div>
                    <div class="text-3xl font-extrabold text-emerald-600 mt-1"><?= count($cooperatives); ?></div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
                <div class="p-4 bg-slate-50 border-b font-bold text-slate-700">All Client Orders</div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="p-3">Order #</th>
                            <th class="p-3">Buyer</th>
                            <th class="p-3">Total Value</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="p-3 font-mono font-bold"><?= $o['order_number']; ?></td>
                                <td class="p-3"><?= htmlspecialchars($o['buyer_name']); ?></td>
                                <td class="p-3 font-semibold">Rp <?= number_format($o['total_amount'], 2); ?></td>
                                <td class="p-3">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $o['order_status'] === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'; ?>">
                                        <?= strtoupper($o['order_status']); ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <button onclick="openAllocateModal(<?= $o['id']; ?>)" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-1.5 rounded transition">Allocate Stock</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($role === 'buyer'): ?>
            <!-- Buyer Dashboard -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-slate-800">Industrial Buyer Portal</h2>
                <button onclick="document.getElementById('createOrderModal').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-2 rounded-lg transition">+ Create New Order</button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b font-bold text-slate-700">Order History</div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="p-3">Order #</th>
                            <th class="p-3">Total Amount</th>
                            <th class="p-3">Order Status</th>
                            <th class="p-3">Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="p-3 font-mono font-bold"><?= $o['order_number']; ?></td>
                                <td class="p-3 font-semibold">Rp <?= number_format($o['total_amount'], 2); ?></td>
                                <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100"><?= strtoupper($o['order_status']); ?></span></td>
                                <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700"><?= strtoupper($o['payment_status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Create Order Modal -->
            <div id="createOrderModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4">
                <div class="bg-white p-6 rounded-xl max-w-md w-full border shadow-lg">
                    <h3 class="text-lg font-bold mb-4">Create Industrial Supply Order</h3>
                    <form onsubmit="submitOrder(event)" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Select Material</label>
                            <select name="material_id" required class="w-full border p-2 rounded text-sm outline-none">
                                <?php foreach ($materials as $m): ?>
                                    <option value="<?= $m['id']; ?>"><?= $m['name']; ?> (Rp <?= number_format($m['price_per_unit']); ?>/<?= $m['unit']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Quantity Required</label>
                            <input type="number" step="0.1" name="quantity" required class="w-full border p-2 rounded text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Shipping Address</label>
                            <textarea name="address" required class="w-full border p-2 rounded text-sm outline-none"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('createOrderModal').classList.add('hidden')" class="px-4 py-2 border rounded text-xs font-bold">Cancel</button>
                            <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded text-xs font-bold">Submit Order</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($role === 'driver'): ?>
            <!-- Driver Workspace -->
            <h2 class="text-xl font-bold mb-4 text-slate-800">Assigned Delivery Jobs</h2>
            <div class="space-y-4">
                <?php foreach ($shipments as $s): ?>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between md:items-center gap-4">
                        <div>
                            <div class="text-xs font-mono font-bold text-emerald-600"><?= $s['tracking_number']; ?></div>
                            <div class="text-lg font-bold text-slate-800 mt-0.5"><?= $s['material_name']; ?> (<?= $s['dispatched_qty']; ?> Ton)</div>
                            <div class="text-xs text-slate-500 mt-1">Pickup: <?= $s['coop_name']; ?> | Dest: <?= $s['shipping_address']; ?></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded bg-amber-50 text-amber-700 text-xs font-bold uppercase"><?= $s['status']; ?></span>
                            <?php if ($s['status'] !== 'delivered'): ?>
                                <button onclick="updateDriverStatus(<?= $s['id']; ?>, 'delivered')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded transition">Mark Delivered</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function submitOrder(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'create_order');
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                alert('Order successfully submitted! Order No: ' + data.order_number);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        }

        async function updateDriverStatus(shipmentId, status) {
            const formData = new FormData();
            formData.append('action', 'update_driver_status');
            formData.append('shipment_id', shipmentId);
            formData.append('status', status);
            formData.append('csrf_token', '<?= generateCSRFToken(); ?>');
            
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                alert('Status updated!');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        }
    </script>
</body>
</html>