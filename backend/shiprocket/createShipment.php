<?php
/**
 * ============================================================
 *  SHIPROCKET - CREATE SHIPMENT (SERVER-SIDE)
 *  Endpoint: /backend/shiprocket/createShipment.php
 *  POST -> { order_id: "OP..." }
 *
 *  Registers a store order with Shiprocket, creates the shipment,
 *  and stores the returned shipment_id + awb back into the order
 *  file. Only PAID (or COD-approved) orders may be shipped.
 *  Returns the shipment id + awb so the caller can generate a label.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/shiprocket-curl.php';
require_once __DIR__ . '/shiprocket-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$orderId = isset($data['order_id']) ? trim($data['order_id']) : '';
if (!preg_match('/^OP\d{17}$/', $orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}

$orderFile = ORDERS_DIR . '/' . basename($orderId) . '.json';
if (!file_exists($orderFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}
$order = json_decode(file_get_contents($orderFile), true);

// Only allow shipping paid orders (or COD orders explicitly marked approved).
$status = $order['payment_status'] ?? 'PENDING';
$isCodApproved = (($order['payment_method'] ?? '') === 'cod') && (($order['cod_confirmed'] ?? false) === true);
if ($status !== 'PAID' && !$isCodApproved) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Order must be PAID before shipping']);
    exit;
}

// Avoid duplicate shipments.
if (!empty($order['shiprocket_id'])) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Shipment already created', 'shipment_id' => $order['shiprocket_id'], 'awb' => $order['awb'] ?? '']);
    exit;
}

$payload = sr_build_shipment_payload($order);
$result = sr_api_request('POST', '/shipments/create', $payload);

if (!$result['success']) {
    log_msg('Shiprocket create shipment failed for ' . $orderId . ': ' . $result['error'], 'error');
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => $result['error']]);
    exit;
}

$shipment = $result['data'];
$shipmentId = $shipment['shipment_id'] ?? null;
$awb = $shipment['awb'] ?? '';

// Persist the shipment details back to the order.
$order['shiprocket_id'] = $shipmentId;
$order['awb'] = $awb;
$order['shiprocket_status'] = $shipment['status'] ?? 'PICKUP_PENDING';
$order['shipped_at'] = date('Y-m-d H:i:s');
file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
@chmod($orderFile, 0600);

log_msg("Shiprocket shipment created for $orderId (shipment_id=$shipmentId, awb=$awb)");

http_response_code(200);
echo json_encode([
    'success'     => true,
    'order_id'    => $orderId,
    'shipment_id' => $shipmentId,
    'awb'         => $awb,
    'status'      => $order['shiprocket_status']
]);
