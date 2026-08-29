<?php
/**
 * ============================================================
 *  RAZORPAY - VERIFY PAYMENT & CALLBACK
 *  Endpoint: /backend/razorpay/verify.php
 *
 *  This is the callback target configured in the Razorpay
 *  dashboard (payment handler). It:
 *    1. Receives the payment_id from Razorpay.
 *    2. Re-fetches the payment from the Razorpay API to confirm
 *       its true status (does not trust the browser).
 *    3. Verifies the local order total matches the paid amount.
 *    4. Marks the order PAID and stores the Razorpay payment_id.
 *    5. Redirects to the thankyou page.
 *
 *  SECURITY: The browser-side Razorpay success callback can be
 *  tampered with, so the authoritative confirmation is done here
 *  server-side via the Razorpay API using the Key Secret.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/razorpay-curl.php';

// The payment id is passed as a query string by our checkout flow:
// /backend/razorpay/verify.php?order_id=OP...&payment_id=pay_...
$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
$paymentId = isset($_GET['payment_id']) ? trim($_GET['payment_id']) : '';

if (!preg_match('/^OP\d{17}$/', $orderId)) {
    header('Location: ../../thankyou.html?status=failed&reason=invalid_order');
    exit;
}
if (!preg_match('/^pay_[A-Za-z0-9]+$/', $paymentId)) {
    header('Location: ../../thankyou.html?order=' . urlencode($orderId) . '&status=failed&reason=invalid_payment');
    exit;
}

// Load local order
$orderFile = ORDERS_DIR . '/' . $orderId . '.json';
if (!file_exists($orderFile)) {
    header('Location: ../../thankyou.html?status=failed&reason=order_not_found');
    exit;
}
$order = json_decode(file_get_contents($orderFile), true);

// Fetch the payment from Razorpay API to get authoritative status/amount
$result = rzp_api_request('GET', '/payments/' . $paymentId, null, RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

if (!$result['success']) {
    log_msg("Razorpay verify fetch failed for $orderId pay=$paymentId: " . $result['error'], 'error');
    header('Location: ../../thankyou.html?order=' . urlencode($orderId) . '&status=failed&reason=verify_failed');
    exit;
}

$payment = $result['data'];
$isCaptured = ($payment['status'] === 'captured' || $payment['status'] === 'authorized');
$paidPaise = (int) $payment['amount']; // amount in paise from Razorpay
$expectedPaise = (int) round(((float) $order['total']) * 100);

if (!$isCaptured) {
    log_msg("Razorpay payment not captured for $orderId status={$payment['status']}", 'warn');
    $order['payment_status'] = 'FAILED';
    $order['payment_txn_id'] = $paymentId;
    file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: ../../thankyou.html?order=' . urlencode($orderId) . '&status=failed');
    exit;
}

// Verify the paid amount matches the order total (anti-tamper)
if ($paidPaise !== $expectedPaise) {
    log_msg("Razorpay AMOUNT MISMATCH for $orderId expected={$expectedPaise} paid={$paidPaise}", 'error');
    $order['payment_status'] = 'FAILED';
    $order['payment_txn_id'] = $paymentId;
    file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: ../../thankyou.html?order=' . urlencode($orderId) . '&status=failed&reason=amount');
    exit;
}

// Success - mark PAID
$order['payment_status'] = 'PAID';
$order['payment_method'] = 'razorpay';
$order['payment_txn_id'] = $paymentId;
$order['paid_at'] = date('Y-m-d H:i:s');
file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
log_msg("Razorpay PAYMENT SUCCESS for $orderId pay=$paymentId amount=$paidPaise");

header('Location: ../../thankyou.html?order=' . urlencode($orderId) . '&status=success');
exit;
