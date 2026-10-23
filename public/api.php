<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/CoCoLinkSystem.php';

$db = (new Database())->getConnection();
$cocolink = new CoCoLinkSystem($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'create_order':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST.']);
                exit;
            }

            if (empty($input['buyer_id']) || empty($input['waste_category_id']) || empty($input['quantity']) || empty($input['unit_price']) || empty($input['address'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Parameter buyer_id, waste_category_id, quantity, unit_price, dan address wajib diisi.']);
                exit;
            }

            $data = $cocolink->createOrder(
                (int)$input['buyer_id'],
                (int)$input['waste_category_id'],
                (float)$input['quantity'],
                (float)$input['unit_price'],
                (string)$input['address']
            );

            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Pesanan berhasil dibuat.', 'data' => $data]);
            break;

        case 'dispatch_order':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST.']);
                exit;
            }

            if (empty($input['order_id']) || empty($input['allocations']) || !is_array($input['allocations'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Parameter order_id dan allocations (array) wajib diisi.']);
                exit;
            }

            $cocolink->dispatchAllocations((int)$input['order_id'], $input['allocations']);

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Alokasi pesanan berhasil disalurkan ke koperasi anak.']);
            break;

        case 'claim_shipment':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST.']);
                exit;
            }

            if (empty($input['shipment_id']) || empty($input['driver_id'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Parameter shipment_id dan driver_id wajib diisi.']);
                exit;
            }

            $claimed = $cocolink->claimShipment((int)$input['shipment_id'], (int)$input['driver_id']);

            if (!$claimed) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Pengiriman gagal diklaim atau sudah diambil driver lain.']);
                exit;
            }

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Pengiriman berhasil diklaim driver.']);
            break;

        case 'get_order':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method harus GET.']);
                exit;
            }

            $orderId = $_GET['order_id'] ?? null;
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Query parameter order_id wajib diisi.']);
                exit;
            }

            $summary = $cocolink->getOrderSummary((int)$orderId);

            if (empty($summary)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Data pesanan tidak ditemukan.']);
                exit;
            }

            http_response_code(200);
            echo json_encode(['status' => 'success', 'data' => $summary]);
            break;

        default:
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Endpoint action tidak valid.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}