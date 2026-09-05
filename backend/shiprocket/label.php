<?php
/**
 * ============================================================
 *  SHIPROCKET - SHIPPING LABEL / AWB LABEL (SERVER-SIDE)
 *  Endpoint: /backend/shiprocket/label.php
 *  GET/POST -> { order_id: "OP..." }  (also supports awb=)
 *
 *  Returns the Shiprocket shipping label for a shipment. If an AWB
 *  is provided directly it is used; otherwise the AWB saved on the
 *  store order is used. The label is a PDF streamed to the browser.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/shiprocket-curl.php';

// Accept either a JSON body (order_id) or query param awb.
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

// Fetch the label PDF from Shiprocket directly.
$token = sr_get_token();
if ($token === false || $token === '') {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Shiprocket authentication not configured']);
    exit;
}

$url = (defined('SHIPROCKET_API_URL') ? SHIPROCKET_API_URL : 'https://apiv2.shiprocket.in/v1/external') . '/shipments/generate/label?awbs=' . urlencode($awb);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 40,
    CURLOPT_CONNECTTIMEOUT => 10
]);
$labelResponse = curl_exec($ch);
$labelInfo = curl_getinfo($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || $labelInfo['http_code'] < 200 || $labelInfo['http_code'] >= 300) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Could not generate label']);
    exit;
}

// Stream the PDF.
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="label_' . $awb . '.pdf"');
header('Content-Length: ' . strlen($labelResponse));
echo $labelResponse;
exit;
