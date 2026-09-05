<?php
/**
 * ============================================================
 *  CASHFREE - CREATE ORDER / PAYMENT SESSION (SERVER-SIDE)
 *  Endpoint: /backend/cashfree/createOrder.php
 *  POST -> { order_id: "OP..." }
 *
 *  Creates a Cashfree Order via the Cashfree PG API using the
 *  server-side Client ID + Client Secret. Returns the payment
 *  session id + checkout details so the client can open the
 *  Cashfree JS Checkout SDK.
 *
 *  IMPORTANT: The Client Secret is used ONLY here on the server
 *  and is never sent to the browser.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/cashfree-curl.php';

// POST only
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

// Load the saved store order to get the authoritative total
$orderFile = ORDERS_DIR . '/' . basename($orderId) . '.json';
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

$amountPaise = (int) round(((float) $order['total']) * 100); // paise
$customer = $order['customer'];

// Build the Cashfree Order payload
$orderItems = [];
foreach (($order['items'] ?? []) as $it) {
    $orderItems[] = [
        'name'        => $it['name'],
        'quantity'    => (int) $it['qty'],
        'unit_amount' => (int) round(((float) $it['price']) * 100), // paise
        'total_amount'=> (int) round(((float) $it['line_total']) * 100)
    ];
}

$payload = [
    'order_id'          => $orderId,
    'order_amount'      => (float) $order['total'],   // in rupees for Cashfree
    'order_currency'    => 'INR',
    'order_note'        => 'Organic Pesticide order ' . $orderId,
    'customer_details'  => [
        'customer_id'    => (string) $customer['phone'],
        'customer_name'  => $customer['name'] ?? '',
        'customer_email' => $customer['email'] ?? '',
        'customer_phone' => $customer['phone'] ?? ''
    ],
    'order_meta'        => [
        'return_url' => CASHFREE_CALLBACK_URL . '?order_id=' . $orderId . '&payment_status={payment_status}&order_amount={order_amount}&order_id_cf={order_id}',
        'notify_url' => CASHFREE_CALLBACK_URL . '?order_id=' . $orderId
    ],
    'order_items'       => $orderItems
];

$result = cf_api_request('POST', '/orders', $payload, CASHFREE_CLIENT_ID, CASHFREE_CLIENT_SECRET);

if (!$result['success']) {
    log_msg('Cashfree create order failed: ' . $result['error'], 'error');
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Could not create payment order']);
    exit;
}

$cfOrder = $result['data'];
log_msg("Cashfree order created: cfObj={$cfOrder['order_id']} for $orderId");

http_response_code(200);
echo json_encode([
    'success'            => true,
    'order_id'           => $orderId,
    'cf_order_id'        => $cfOrder['order_id'] ?? '',
    'payment_session_id' => $cfOrder['payment_session_id'] ?? '',
    'order_amount'       => (float) $order['total'],
    'order_currency'     => 'INR',
    'checkout_url'       => CASHFREE_CHECKOUT_URL,
    'callback_url'       => CASHFREE_CALLBACK_URL
]);
