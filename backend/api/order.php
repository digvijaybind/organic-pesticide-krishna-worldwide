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

// Same-origin guard (best effort CSRF protection).
// Compare bare hostnames (port stripped) so requests via localhost with a
// custom port, or standard 80/443 production, are treated as same-origin.
$reqOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$reqHost = (string)($_SERVER['HTTP_HOST'] ?? '');
$reqHostName = strtolower(parse_url('//' . $reqHost, PHP_URL_HOST) ?: $reqHost);
$allowedHost = strtolower((string)(getenv('APP_HOST') ?: $reqHost));
$allowedHostName = strtolower(parse_url('//' . $allowedHost, PHP_URL_HOST) ?: $allowedHost);
if ($reqOrigin !== '') {
    $originHost = strtolower((string)parse_url($reqOrigin, PHP_URL_HOST));
    if ($originHost && $originHost !== $reqHostName && $originHost !== $allowedHostName) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Origin not allowed']);
        exit;
    }
}

// --- Validation ---
$customer = isset($data['customer']) ? $data['customer'] : null;
$items = isset($data['items']) ? $data['items'] : [];

if (!is_array($customer) || !is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Customer and items are required']);
    exit;
}

$names = ['name','phone','email','address','city','state','pincode'];
foreach ($names as $field) {
    if (!isset($customer[$field]) || trim((string)$customer[$field]) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit;
    }
}

// Validate phone (10 digits)
$phone = preg_replace('/\D/', '', (string)$customer['phone']);
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

// Validate email
$email = strtolower(trim((string)$customer['email']));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate pincode (6 digits)
if (!preg_match('/^\d{6}$/', trim((string)$customer['pincode']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid pincode']);
    exit;
}

// Cap field lengths
$maxLen = ['name' => 120, 'address' => 300, 'city' => 60, 'state' => 60];
foreach ($maxLen as $f => $len) {
    if (mb_strlen((string)$customer[$f]) > $len) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($f) . ' is too long']);
        exit;
    }
}

// --- Compute & verify totals server-side (never trust the client) ---
$products = require __DIR__ . '/../config/products.php';
$priceMap = [];
foreach ($products as $p) {
    $priceMap[$p['id']] = $p; // keep full product (price + canonical name)
}

$subtotal = 0;
$lineItems = [];
$paymentMethod = ($data['payment_method'] ?? '') === 'paytm' ? 'paytm' : (($data['payment_method'] ?? '') === 'razorpay' ? 'razorpay' : 'cod');
foreach ($items as $item) {
    if (!is_array($item) || !isset($item['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid item data']);
        exit;
    }
    $pid = (string)$item['id'];
    $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
    $qty = max(1, min(50, $qty)); // sanity cap
    if (!isset($priceMap[$pid])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Unknown product: $pid"]);
        exit;
    }
    $product = $priceMap[$pid];
    $price = (int) $product['price'];
    $subtotal += $price * $qty;
    // Use the SERVER's canonical product name (never trust client "name").
    $lineItems[] = [
        'id' => $pid,
        'name' => $product['name'],
        'qty' => $qty,
        'price' => $price,
        'line_total' => $price * $qty
    ];
}

$shipping = ($subtotal >= FREE_SHIPPING_THRESHOLD) ? 0 : SHIPPING_FEE;
$total = $subtotal + $shipping;

// --- Create order (format: OP + 14-digit timestamp + 3-digit suffix = 17 digits) ---
$timestamp = date('YmdHis');
do {
    $orderId = 'OP' . $timestamp . str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
} while (file_exists(ORDERS_DIR . '/' . $orderId . '.json'));

$order = [
    'order_id' => $orderId,
    'created_at' => date('Y-m-d H:i:s'),
    'customer' => [
        'name' => trim((string)$customer['name']),
        'phone' => $phone,
        'email' => $email,
        'address' => trim((string)$customer['address']),
        'city' => trim((string)$customer['city']),
        'state' => trim((string)$customer['state']),
        'pincode' => trim((string)$customer['pincode'])
    ],
    'items' => $lineItems,
    'subtotal' => $subtotal,
    'shipping' => $shipping,
    'total' => $total,
    'payment_method' => $paymentMethod,        // 'razorpay' | 'paytm' | 'cod'
    'payment_status' => 'PENDING',           // 'PENDING' | 'PAID' | 'FAILED'
    'payment_txn_id' => ''
];

// Save order as JSON file (simple file storage; use MySQL in production)
$file = ORDERS_DIR . '/' . $orderId . '.json';
$saved = file_put_contents($file, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($saved !== false) { @chmod($file, 0600); }

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
