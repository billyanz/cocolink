<?php
// public/api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../src/cocolinksystem.php';

$user = requireAuth();
$system = new CoCoLinkSystem();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF verification failed.']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create_order':
        if ($user['role'] !== 'buyer') die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        echo json_encode($system->createOrder($user['id'], (int)$_POST['material_id'], (float)$_POST['quantity'], sanitizeInput($_POST['address'])));
        break;

    case 'allocate':
        if ($user['role'] !== 'induk') die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        echo json_encode($system->allocateStock((int)$_POST['order_item_id'], (int)$_POST['cooperative_id'], (float)$_POST['quantity'], $user['id']));
        break;

    case 'record_weighing':
        if ($user['role'] !== 'anak') die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        echo json_encode($system->recordWeighing((int)$_POST['allocation_id'], (float)$_POST['gross_weight'], (float)$_POST['tare_weight'], sanitizeInput($_POST['weighmaster']), sanitizeInput($_POST['notes'] ?? ''), $user['id']));
        break;

    case 'create_shipment':
        if (!in_array($user['role'], ['induk', 'anak'])) die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        echo json_encode($system->createShipment((int)$_POST['allocation_id'], (int)$_POST['driver_id'], (float)$_POST['dispatched_qty'], $user['id']));
        break;

    case 'update_driver_status':
        if ($user['role'] !== 'driver') die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        echo json_encode($system->updateShipmentStatus((int)$_POST['shipment_id'], $user['id'], sanitizeInput($_POST['status']), sanitizeInput($_POST['evidence_note'] ?? '')));
        break;

    case 'confirm_receipt':
        if ($user['role'] !== 'buyer') die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        echo json_encode($system->confirmReceipt((int)$_POST['shipment_id'], (float)$_POST['actual_qty'], sanitizeInput($_POST['notes'] ?? ''), $user['id']));
        break;

    case 'resolve_discrepancy':
        if ($user['role'] !== 'induk') die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        echo json_encode($system->resolveDiscrepancy((int)$_POST['discrepancy_id'], sanitizeInput($_POST['resolution_type']), $user['id']));
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid endpoint requested.']);
        break;
}