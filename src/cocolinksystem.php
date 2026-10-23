<?php

class CoCoLinkSystem {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    // 1. Pengepul / Buyer membuat order baru
    public function createOrder(int $buyerId, int $wasteCategoryId, float $quantity, float $unitPrice, string $address): array {
        $orderNumber = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $totalPrice = $quantity * $unitPrice;

        $stmt = $this->db->prepare("
            INSERT INTO orders (order_number, buyer_id, waste_category_id, total_quantity, unit_price, total_price, delivery_address, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$orderNumber, $buyerId, $wasteCategoryId, $quantity, $unitPrice, $totalPrice, $address]);

        $orderId = (int)$this->db->lastInsertId();

        return [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_quantity' => $quantity,
            'total_price' => $totalPrice
        ];
    }

    // 2. Koperasi Induk membagikan alokasi kuota ke Koperasi Anak
    public function dispatchAllocations(int $orderId, array $allocations): bool {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT total_quantity FROM orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Pesanan dengan ID {$orderId} tidak ditemukan.");
            }

            $sumQuantity = array_sum(array_column($allocations, 'quantity'));
            if (abs((float)$sumQuantity - (float)$order['total_quantity']) > 0.0001) {
                throw new Exception("Total alokasi ({$sumQuantity}) harus persis sama dengan jumlah pesanan ({$order['total_quantity']}).");
            }

            $stmtAlloc = $this->db->prepare("
                INSERT INTO order_allocations (order_id, child_coop_id, allocated_quantity, status)
                VALUES (?, ?, ?, 'assigned')
            ");

            $stmtShipment = $this->db->prepare("
                INSERT INTO shipments (tracking_number, allocation_id, status)
                VALUES (?, ?, 'waiting_driver')
            ");

            foreach ($allocations as $alloc) {
                $stmtAlloc->execute([$orderId, $alloc['child_coop_id'], $alloc['quantity']]);
                $allocId = (int)$this->db->lastInsertId();

                $trackingNum = 'TRK-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $stmtShipment->execute([$trackingNum, $allocId]);
            }

            $stmtUpdateOrder = $this->db->prepare("UPDATE orders SET status = 'allocated' WHERE id = ?");
            $stmtUpdateOrder->execute([$orderId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // 3. Driver memicu klaim pengiriman
    public function claimShipment(int $shipmentId, int $driverId): bool {
        $stmt = $this->db->prepare("
            UPDATE shipments 
            SET driver_id = ?, status = 'picked_up', picked_up_at = NOW()
            WHERE id = ? AND status = 'waiting_driver'
        ");
        $stmt->execute([$driverId, $shipmentId]);

        return $stmt->rowCount() > 0;
    }

    // 4. Monitoring ringkasan transparan
    public function getOrderSummary(int $orderId): array {
        $stmtOrder = $this->db->prepare("
            SELECT o.*, u.name as buyer_name, w.name as waste_name 
            FROM orders o
            JOIN users u ON o.buyer_id = u.id
            JOIN waste_categories w ON o.waste_category_id = w.id
            WHERE o.id = ?
        ");
        $stmtOrder->execute([$orderId]);
        $order = $stmtOrder->fetch();

        if (!$order) {
            return [];
        }

        $stmtAlloc = $this->db->prepare("
            SELECT a.*, u.name as child_coop_name, s.id as shipment_id, s.tracking_number, s.status as shipment_status, d.name as driver_name
            FROM order_allocations a
            JOIN users u ON a.child_coop_id = u.id
            LEFT JOIN shipments s ON s.allocation_id = a.id
            LEFT JOIN users d ON s.driver_id = d.id
            WHERE a.order_id = ?
        ");
        $stmtAlloc->execute([$orderId]);
        $allocations = $stmtAlloc->fetchAll();

        return [
            'order' => $order,
            'allocations' => $allocations
        ];
    }
}