<?php
/**
 * ============================================================
 *  CASHFREE - PAYMENT VERIFICATION CALLBACK (SERVER-SIDE)
 *  Endpoint: /backend/cashfree/verify.php
 *
 *  Called by Cashfree (return_url / notify_url) after a payment.
 *  We re-fetch the payment status from Cashfree to confirm that the
 *  amount actually paid matches our order total, then mark the store
 *  order as PAID. The customer is redirected to thankyou.html.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/cashfree-curl.php';

// Determine which store order this payment belongs to.
$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : (isset($_POST['order_id']) ? trim($_POST['order_id']) : '');
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

// The current store order may have been created via Cashfree with the same id.
$cfOrderId = $orderId; // we used our store OP order id as the Cashfree order id

// Fetch payment(s) for this order from Cashfree to verify server-side.
$result = cf_api_request('GET', '/orders/' . urlencode($cfOrderId) . '/payments', null, CASHFREE_CLIENT_ID, CASHFREE_CLIENT_SECRET);

$paid = false;
$txnId = '';
$paidAmount = 0.0;

if ($result['success']) {
    $payments = $result['data'];
    // $payments is an array of payments; verify the most recent matching one.
    if (is_array($payments)) {
        $last = end($payments);
        if ($last && ($last['payment_status'] ?? '') === 'SUCCESS') {
            $paid = true;
            $txnId = $last['cf_payment_id'] ?? '';
            $paidAmount = (float) ($last['order_amount'] ?? 0);
        }
    }
}

$expectedTotal = (float) $order['total'];
$verified = $paid && abs($paidAmount - $expectedTotal) < 0.01;

if ($verified) {
    // Mark as PAID (only if currently PENDING or FAILED)
    if (($order['payment_status'] ?? '') !== 'PAID') {
        $order['payment_status'] = 'PAID';
        $order['payment_txn_id'] = $txnId;
        $order['paid_at'] = date('Y-m-d H:i:s');
        file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @chmod($orderFile, 0600);
        log_msg("Cashfree payment verified PAID for $orderId (txn $txnId, amount $paidAmount)");
    } else {
        log_msg("Cashfree callback: order $orderId already PAID");
    }

    header("Location: " . (getenv('SITE_URL') ?: ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) . "/thankyou.html?order=" . urlencode($orderId) . "&status=success");
    exit;
}

// Payment failed or not yet confirmed => mark FAILED and redirect.
log_msg("Cashfree payment NOT verified for $orderId (paid=$paid amount=$paidAmount)", 'error');
if (($order['payment_status'] ?? '') === 'PENDING') {
    $order['payment_status'] = 'FAILED';
    file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    @chmod($orderFile, 0600);
}

header("Location: " . (getenv('SITE_URL') ?: ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) . "/thankyou.html?order=" . urlencode($orderId) . "&status=failed");
exit;
