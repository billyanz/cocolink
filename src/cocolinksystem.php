<?php
// src/cocolinksystem.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

class CoCoLinkSystem {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? getDBConnection();
    }

    /**
     * Buyer creates a new industrial material order.
     */
    public function createOrder(int $buyerId, int $materialId, float $quantity, string $address): array {
        if ($quantity <= 0) return ['status' => 'error', 'message' => 'Quantity must be greater than zero.'];

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT price_per_unit, name FROM materials WHERE id = ?");
            $stmt->execute([$materialId]);
            $material = $stmt->fetch();
            if (!$material) throw new Exception("Material specified does not exist.");

            $price = (float)$material['price_per_unit'];
            $totalAmount = $price * $quantity;
            $orderNum = "ORD-" . date('Ymd') . "-" . rand(1000, 9999);

            $stmt = $this->pdo->prepare("INSERT INTO orders (order_number, buyer_id, total_amount, shipping_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderNum, $buyerId, $totalAmount, $address]);
            $orderId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("INSERT INTO order_items (order_id, material_id, requested_qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $materialId, $quantity, $price, $totalAmount]);

            logAudit($this->pdo, $buyerId, 'ORDER_CREATE', "Created Order {$orderNum} for {$quantity} units of {$material['name']}");
            $this->pdo->commit();
            return ['status' => 'success', 'order_id' => $orderId, 'order_number' => $orderNum];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Central Cooperative allocates required quantities across child coops.
     */
    public function allocateStock(int $orderItemId, int $coopId, float $qty, int $adminUserId): array {
        if ($qty <= 0) return ['status' => 'error', 'message' => 'Allocation quantity must be greater than zero.'];

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'induk' AND status = 'active'");
            $stmt->execute([$adminUserId]);
            if (!$stmt->fetch()) throw new Exception("Only an Induk user can allocate stock.");

            // Lock inventory row to prevent race condition
            $stmt = $this->pdo->prepare("SELECT material_id FROM order_items WHERE id = ?");
            $stmt->execute([$orderItemId]);
            $item = $stmt->fetch();
            if (!$item) throw new Exception("Order item not found.");

            $stmt = $this->pdo->prepare("SELECT available_qty FROM inventory WHERE cooperative_id = ? AND material_id = ? FOR UPDATE");
            $stmt->execute([$coopId, $item['material_id']]);
            $inv = $stmt->fetch();

            if (!$inv || (float)$inv['available_qty'] < $qty) {
                throw new Exception("Target cooperative does not have enough available stock (Available: " . ($inv['available_qty'] ?? 0) . " Ton).");
            }

            // Reserve stock
            $stmt = $this->pdo->prepare("UPDATE inventory SET available_qty = available_qty - ?, reserved_qty = reserved_qty + ? WHERE cooperative_id = ? AND material_id = ?");
            $stmt->execute([$qty, $qty, $coopId, $item['material_id']]);

            // Create Allocation Record
            $stmt = $this->pdo->prepare("INSERT INTO allocations (order_item_id, cooperative_id, allocated_qty) VALUES (?, ?, ?)");
            $stmt->execute([$orderItemId, $coopId, $qty]);
            $allocationId = (int)$this->pdo->lastInsertId();

            // Update order status to allocated/in_fulfillment
            $stmt = $this->pdo->prepare("UPDATE orders SET order_status = 'allocated' WHERE id = (SELECT order_id FROM order_items WHERE id = ?)");
            $stmt->execute([$orderItemId]);

            logAudit($this->pdo, $adminUserId, 'ALLOCATE_STOCK', "Allocated {$qty} units to Cooperative #{$coopId} for item #{$orderItemId}");
            $this->pdo->commit();
            return ['status' => 'success', 'allocation_id' => $allocationId];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Child Cooperative performs scale weighing before dispatch.
     */
    public function recordWeighing(int $allocationId, float $gross, float $tare, string $weighmaster, string $notes, int $coopUserId): array {
        $net = $gross - $tare;
        if ($net <= 0) return ['status' => 'error', 'message' => 'Net weight must be positive (Gross > Tare).'];

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT a.id, a.cooperative_id
                                         FROM allocations a
                                         JOIN users u ON u.id=? AND u.role='anak' AND u.cooperative_id=a.cooperative_id
                                         WHERE a.id=? FOR UPDATE");
            $stmt->execute([$coopUserId, $allocationId]);
            $allocation = $stmt->fetch();
            if (!$allocation) throw new Exception("Allocation not found or not assigned to this cooperative.");

            $stmt = $this->pdo->prepare("INSERT INTO weighing_records (allocation_id, gross_weight, tare_weight, net_weight, weighmaster_name, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$allocationId, $gross, $tare, $net, $weighmaster, $notes]);

            $stmt = $this->pdo->prepare("UPDATE allocations SET prepared_qty = ?, status = 'weighed' WHERE id = ? AND cooperative_id = ?");
            $stmt->execute([$net, $allocationId, $allocation['cooperative_id']]);

            logAudit($this->pdo, $coopUserId, 'WEIGHING_RECORD', "Recorded weighing for Allocation #{$allocationId}: Gross {$gross}T, Tare {$tare}T, Net {$net}T");
            $this->pdo->commit();
            return ['status' => 'success', 'net_weight' => $net];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Assign Driver and Dispatch Shipment.
     */
    public function createShipment(int $allocationId, int $driverId, float $dispatchedQty, int $adminUserId): array {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT a.cooperative_id
                                         FROM allocations a
                                         JOIN users u ON u.id=?
                                         WHERE a.id=?
                                           AND (u.role='induk' OR (u.role='anak' AND u.cooperative_id=a.cooperative_id))
                                         FOR UPDATE");
            $stmt->execute([$adminUserId, $allocationId]);
            $allocation = $stmt->fetch();
            if (!$allocation) throw new Exception("Allocation not found or outside the caller's cooperative scope.");

            $tracking = "TRK-" . date('Ymd') . "-" . rand(10000, 99999);

            $stmt = $this->pdo->prepare("INSERT INTO shipments (tracking_number, allocation_id, driver_id, dispatched_qty, status, dispatched_at) VALUES (?, ?, ?, ?, 'assigned', NOW())");
            $stmt->execute([$tracking, $allocationId, $driverId, $dispatchedQty]);
            $shipmentId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("UPDATE allocations SET status = 'in_transit' WHERE id = ? AND cooperative_id = ?");
            $stmt->execute([$allocationId, $allocation['cooperative_id']]);

            logAudit($this->pdo, $adminUserId, 'SHIPMENT_DISPATCH', "Assigned Driver #{$driverId} to Shipment {$tracking}");
            $this->pdo->commit();
            return ['status' => 'success', 'tracking_number' => $tracking, 'shipment_id' => $shipmentId];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Driver updates shipment milestones (picked_up -> in_transit -> delivered).
     */
    public function updateShipmentStatus(int $shipmentId, int $driverId, string $status, ?string $evidenceNote = null): array {
        $validStatuses = ['picked_up', 'in_transit', 'delivered', 'failed'];
        if (!in_array($status, $validStatuses)) return ['status' => 'error', 'message' => 'Invalid shipment status.'];

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT driver_id, dispatched_qty FROM shipments WHERE id = ?");
            $stmt->execute([$shipmentId]);
            $shipment = $stmt->fetch();

            if (!$shipment || (int)$shipment['driver_id'] !== $driverId) {
                throw new Exception("You are not authorized to update this shipment.");
            }

            $delTime = ($status === 'delivered') ? "NOW()" : "NULL";
            $deliveredQty = ($status === 'delivered') ? $shipment['dispatched_qty'] : 0.00;

            $stmt = $this->pdo->prepare("UPDATE shipments SET status = ?, delivery_evidence = ?, delivered_qty = ?, delivered_at = " . ($status === 'delivered' ? "NOW()" : "delivered_at") . " WHERE id = ?");
            $stmt->execute([$status, $evidenceNote, $deliveredQty, $shipmentId]);

            logAudit($this->pdo, $driverId, 'DRIVER_STATUS_UPDATE', "Driver updated Shipment #{$shipmentId} status to '{$status}'");
            $this->pdo->commit();
            return ['status' => 'success', 'message' => "Shipment updated to {$status}."];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Buyer confirms delivery or files a shortfall dispute.
     */
    public function confirmReceipt(int $shipmentId, float $actualReceivedQty, string $notes, int $buyerUserId): array {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT s.*, a.allocated_qty, a.order_item_id
                                         FROM shipments s
                                         JOIN allocations a ON s.allocation_id=a.id
                                         JOIN order_items oi ON oi.id=a.order_item_id
                                         JOIN orders o ON o.id=oi.order_id
                                         WHERE s.id=? AND o.buyer_id=?
                                         FOR UPDATE");
            $stmt->execute([$shipmentId, $buyerUserId]);
            $shipment = $stmt->fetch();

            if (!$shipment) throw new Exception("Shipment record not found or not assigned to this buyer.");
            if ($shipment['status'] !== 'delivered') {
                throw new Exception("Shipment has not been marked delivered by the driver.");
            }

            $dispatched = (float)$shipment['dispatched_qty'];
            if ($actualReceivedQty < $dispatched) {
                $shortfall = $dispatched - $actualReceivedQty;
                $stmt = $this->pdo->prepare("INSERT INTO discrepancies (shipment_id, reported_by, expected_qty, actual_qty, shortfall_qty, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shipmentId, $buyerUserId, $dispatched, $actualReceivedQty, $shortfall, $notes]);

                $stmt = $this->pdo->prepare("UPDATE orders SET order_status = 'partially_fulfilled' WHERE id = (SELECT order_id FROM order_items WHERE id = ?)");
                $stmt->execute([$shipment['order_item_id']]);

                logAudit($this->pdo, $buyerUserId, 'DISCREPANCY_REPORTED', "Buyer reported shortfall of {$shortfall}T on Shipment #{$shipmentId}");
            } else {
                $stmt = $this->pdo->prepare("UPDATE shipments SET status = 'delivered', delivered_qty = ? WHERE id = ?");
                $stmt->execute([$actualReceivedQty, $shipmentId]);

                $stmt = $this->pdo->prepare("UPDATE allocations SET status = 'delivered' WHERE id = ?");
                $stmt->execute([$shipment['allocation_id']]);

                logAudit($this->pdo, $buyerUserId, 'DELIVERY_CONFIRMED', "Buyer confirmed full receipt of {$actualReceivedQty}T on Shipment #{$shipmentId}");
            }

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Receipt response recorded.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Central Induk resolves shortages (Reallocate Shortfall OR Credit Note).
     */
    public function resolveDiscrepancy(int $discrepancyId, string $resolution, int $resolvedByUserId): array {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'induk' AND status = 'active'");
            $stmt->execute([$resolvedByUserId]);
            if (!$stmt->fetch()) throw new Exception("Only an Induk user can resolve discrepancies.");

            $stmt = $this->pdo->prepare("SELECT d.*, s.allocation_id, a.order_item_id FROM discrepancies d JOIN shipments s ON d.shipment_id = s.id JOIN allocations a ON s.allocation_id = a.id WHERE d.id = ?");
            $stmt->execute([$discrepancyId]);
            $disc = $stmt->fetch();

            if (!$disc) throw new Exception("Discrepancy record not found.");

            $stmt = $this->pdo->prepare("SELECT unit_price, order_id FROM order_items WHERE id = ?");
            $stmt->execute([$disc['order_item_id']]);
            $item = $stmt->fetch();

            if ($resolution === 'credit_note') {
                $shortfall = (float)$disc['shortfall_qty'];
                $refundAmount = $shortfall * (float)$item['unit_price'];
                $refNo = "CN-" . time();

                $stmt = $this->pdo->prepare("INSERT INTO payments (order_id, amount, type, reference_no, notes) VALUES (?, ?, 'credit_note', ?, ?)");
                $stmt->execute([$item['order_id'], $refundAmount, $refNo, "Credit Note issued for shortage of {$shortfall} Tons."]);

                $stmt = $this->pdo->prepare("UPDATE orders SET payment_status = 'credit_note_issued', order_status = 'completed' WHERE id = ?");
                $stmt->execute([$item['order_id']]);
            }

            $stmt = $this->pdo->prepare("UPDATE discrepancies SET status = 'resolved', resolution_type = ? WHERE id = ?");
            $stmt->execute([$resolution, $discrepancyId]);

            logAudit($this->pdo, $resolvedByUserId, 'DISCREPANCY_RESOLVED', "Resolved discrepancy #{$discrepancyId} via strategy: {$resolution}");
            $this->pdo->commit();
            return ['status' => 'success', 'message' => "Discrepancy successfully resolved via {$resolution}."];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}