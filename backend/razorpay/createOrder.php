<?php
/**
 * ============================================================
 *  RAZORPAY - CREATE ORDER (SERVER-SIDE)
 *  Endpoint: /backend/razorpay/createOrder.php
 *  POST -> { order_id: "OP..." }
 *
 *  Creates a Razorpay Order via the Razorpay API using the
 *  server-side Key ID + Key Secret. Returns the Razorpay order_id
 *  and our public Key ID so the client can open Razorpay Checkout.
 *
 *  IMPORTANT: The Key Secret is used ONLY here on the server and
 *  is never sent to the browser.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/razorpay-curl.php';

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Same-origin guard (best effort) - rely on orders being created
// via the checkout flow. Real CSRF protection is handled at order.php.
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$orderId = isset($data['order_id']) ? trim($data['order_id']) : '';
if (!preg_match('/^OP\d{17}$/', $orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}

// Load the saved order to get the authoritative total
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

// Build the Razorpay Order payload
$payload = [
    'amount'          => $amountPaise,
    'currency'        => 'INR',
    'receipt'         => $orderId,
    'payment_capture' => 1,                       // capture automatically
    'notes'           => [
        'name'   => $customer['name'] ?? '',
        'phone'  => $customer['phone'] ?? '',
        'email'  => $customer['email'] ?? ''
    ]
];

$result = rzp_api_request('POST', '/orders', $payload, RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

if (!$result['success']) {
    log_msg('Razorpay create order failed: ' . $result['error'], 'error');
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Could not create payment order']);
    exit;
}

$rzpOrder = $result['data'];
log_msg("Razorpay order created: rzp_obj={$rzpOrder['id']} for $orderId");

http_response_code(200);
echo json_encode([
    'success'      => true,
    'key_id'       => RAZORPAY_KEY_ID,
    'order_id'     => $orderId,
    'razorpay_order_id' => $rzpOrder['id'],
    'amount'       => (int) $order['total'],
    'amount_paise' => $amountPaise,
    'currency'     => 'INR',
    'callback_url' => RAZORPAY_CALLBACK_URL
]);
