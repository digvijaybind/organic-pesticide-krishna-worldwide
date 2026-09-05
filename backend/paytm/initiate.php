<?php
/**
 * ============================================================
 *  PAYTM CHECKOUT - INITIATE
 *  Endpoint: /backend/paytm/initiate.php
 *  POST -> { order_id: "..." }
 *  Creates the Paytm token & returns JS checkout config.
 *  Called from the client AFTER submitting checkout.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Paytm.class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Same-origin guard (best effort). Compare bare hostnames so requests via
// a custom port (localhost) or standard 80/443 are treated as same-origin.
$reqOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$reqHost = (string)($_SERVER['HTTP_HOST'] ?? '');
$reqHostName = strtolower(parse_url('//' . $reqHost, PHP_URL_HOST) ?: $reqHost);
if ($reqOrigin !== '') {
    $originHost = strtolower((string)parse_url($reqOrigin, PHP_URL_HOST));
    if ($originHost && $originHost !== $reqHostName) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Origin not allowed']);
        exit;
    }
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$orderId = isset($data['order_id']) ? trim($data['order_id']) : '';

// Strict format validation to prevent path traversal
if (!preg_match('/^OP\d{17}$/', $orderId) || basename($orderId) !== $orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}

// Load the saved order
$orderFile = ORDERS_DIR . '/' . $orderId . '.json';
if (!file_exists($orderFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}
$order = json_decode(file_get_contents($orderFile), true);

if (($order['payment_status'] ?? '') !== 'PENDING') {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Order is not pending']);
    exit;
}

$paytm = new Paytm();
$amount = $order['total'];
$customer = $order['customer'];

$result = $paytm->getTransactionToken($orderId, $amount, $customer);

if (!$result['success']) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $result['error']]);
    exit;
}

log_msg("Paytm token fetched for $orderId");
http_response_code(200);
echo json_encode([
    'success' => true,
    'mid' => PAYTM_MERCHANT_ID,
    'order_id' => $orderId,
    'txn_token' => $result['txnToken'],
    'amount' => $amount,
    'transaction_url' => PAYTM_TRANSACTION_URL,
    'callback_url' => PAYTM_CALLBACK_URL
]);
