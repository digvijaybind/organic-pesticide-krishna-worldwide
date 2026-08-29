<?php
/**
 * ============================================================
 *  ORDER API
 *  Endpoint: /backend/api/order.php
 *  Creates and stores customer orders.
 *
 *  POST JSON body example:
 *  {
 *    "customer": {
 *      "name": "Ramesh",
 *      "phone": "9876543210",
 *      "email": "ramesh@example.com",
 *      "address": "Village ABC, Taluka XYZ",
 *      "city": "Pune",
 *      "state": "Maharashtra",
 *      "pincode": "411001"
 *    },
 *    "items": [
 *      {"id": "cow-dung", "name": "Cow Dung Compost", "qty": 2, "price": 299}
 *    ],
 *    "subtotal": 598,
 *    "shipping": 49,
 *    "total": 647
 *  }
 *
 *  Response includes the assigned ORDER_ID used for Paytm.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

// --- Validation ---
$customer = isset($data['customer']) ? $data['customer'] : null;
$items = isset($data['items']) ? $data['items'] : [];

if (!$customer || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Customer and items are required']);
    exit;
}

$names = ['name','phone','email','address','city','state','pincode'];
foreach ($names as $field) {
    if (empty($customer[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit;
    }
}

// Validate phone (10 digits)
if (!preg_match('/^[0-9]{10}$/', preg_replace('/\D/', '', $customer['phone']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

// --- Compute & verify totals server-side (never trust the client) ---
$products = require __DIR__ . '/../config/products.php';
$priceMap = [];
foreach ($products as $p) {
    $priceMap[$p['id']] = $p['price'];
}

$subtotal = 0;
$lineItems = [];
foreach ($items as $item) {
    $pid = $item['id'];
    $qty = max(1, (int)$item['qty']);
    if (!isset($priceMap[$pid])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Unknown product: $pid"]);
        exit;
    }
    $price = $priceMap[$pid];
    $subtotal += $price * $qty;
    $lineItems[] = [
        'id' => $pid,
        'name' => $item['name'],
        'qty' => $qty,
        'price' => $price,
        'line_total' => $price * $qty
    ];
}

$shipping = ($subtotal >= FREE_SHIPPING_THRESHOLD) ? 0 : SHIPPING_FEE;
$total = $subtotal + $shipping;

// --- Create order ---
$orderId = 'OP' . date('YmdHis') . rand(100, 999);
$order = [
    'order_id' => $orderId,
    'created_at' => date('Y-m-d H:i:s'),
    'customer' => [
        'name' => $customer['name'],
        'phone' => $customer['phone'],
        'email' => $customer['email'],
        'address' => $customer['address'],
        'city' => $customer['city'],
        'state' => $customer['state'],
        'pincode' => $customer['pincode']
    ],
    'items' => $lineItems,
    'subtotal' => $subtotal,
    'shipping' => $shipping,
    'total' => $total,
    'payment_status' => 'PENDING',   // 'PENDING' | 'PAID' | 'FAILED'
    'payment_txn_id' => ''
];

// Save order as JSON file (simple file storage; use MySQL in production)
$file = ORDERS_DIR . '/' . $orderId . '.json';
$saved = file_put_contents($file, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($saved === false) {
    http_response_code(500);
    log_msg("Failed to save order $orderId", 'error');
    echo json_encode(['success' => false, 'message' => 'Could not save order']);
    exit;
}

log_msg("Order created: $orderId total=$total");
http_response_code(201);
echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'total' => $total,
    'message' => 'Order created successfully'
]);
