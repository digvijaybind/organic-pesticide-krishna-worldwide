<?php
/**
 * ============================================================
 *  SHIPROCKET - TRACK SHIPMENT (SERVER-SIDE)
 *  Endpoint: /backend/shiprocket/track.php
 *  GET/POST -> { order_id: "OP..." }  (also supports awb=)
 *
 *  Returns real-time tracking info for a shipment by AWB.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/shiprocket-curl.php';

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$orderId = isset($body['order_id']) ? trim($body['order_id']) : (isset($_GET['order_id']) ? trim($_GET['order_id']) : '');
$awb = isset($body['awb']) ? trim($body['awb']) : (isset($_GET['awb']) ? trim($_GET['awb']) : '');

if ($orderId !== '') {
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
    $awb = $awb !== '' ? $awb : ($order['awb'] ?? '');
}

if ($awb === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No AWB available. Create a shipment first.']);
    exit;
}

$result = sr_api_request('GET', '/courier/track/awb/' . urlencode($awb));

if (!$result['success']) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => $result['error']]);
    exit;
}

$tracking = $result['data'];
log_msg("Shiprocket tracking fetched for awb=$awb");

http_response_code(200);
echo json_encode([
    'success'       => true,
    'awb'           => $awb,
    'tracking_data' => $tracking
]);
