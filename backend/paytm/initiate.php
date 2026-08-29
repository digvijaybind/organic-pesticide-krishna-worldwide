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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$orderId = isset($data['order_id']) ? trim($data['order_id']) : '';

if ($orderId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'order_id required']);
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
