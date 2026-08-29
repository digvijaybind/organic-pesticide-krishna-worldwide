<?php
/**
 * ============================================================
 *  PAYTM RESPONSE / CALLBACK
 *  Endpoint: /backend/paytm/paytm-response.php
 *  Paytm redirects (or calls back) here with the payment result.
 *
 *  IMPORTANT:
 *  - This file runs on the SERVER
 *  - It VERIFIES the Paytm checksum before trusting the result
 *  - On success it marks the order PAID
 *  - Then redirects the customer to the success page
 *
 *  NOTE: In production you must verify the callback server-side
 *  and must NOT rely solely on the browser redirect.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Paytm.class.php';

$paytmParams = $_POST;
$checksum = isset($paytmParams['CHECKSUMHASH']) ? $paytmParams['CHECKSUMHASH'] : '';
$orderId = isset($paytmParams['ORDERID']) ? $paytmParams['ORDERID'] : '';
$txnStatus = isset($paytmParams['STATUS']) ? $paytmParams['STATUS'] : '';
$txnId = isset($paytmParams['TXNID']) ? $paytmParams['TXNID'] : '';
$txnAmount = isset($paytmParams['TXNAMOUNT']) ? (float)$paytmParams['TXNAMOUNT'] : 0.0;

// Strict format validation to prevent path traversal.
if (!preg_match('/^OP\d{17}$/', $orderId) || basename($orderId) !== $orderId) {
    log_msg('Paytm callback with invalid order id suppressed', 'error');
    header('Location: ../../checkout.html?status=failed&reason=invalid_order');
    exit;
}

$paytm = new Paytm();
$verified = $paytm->verifyChecksum($paytmParams, $checksum);

if (!$verified) {
    log_msg("Paytm checksum VERIFICATION FAILED for $orderId", 'error');
    header('Location: ../../checkout.html?status=failed&reason=checksum');
    exit;
}

// Update the saved order
$orderFile = ORDERS_DIR . '/' . $orderId . '.json';
if (file_exists($orderFile)) {
    $order = json_decode(file_get_contents($orderFile), true);
    if ($txnStatus === 'TXN_SUCCESS') {
        // Anti-tamper: verify the paid amount matches the order total.
        $expected = (float) ($order['total'] ?? 0);
        if (abs($txnAmount - $expected) > 0.01) {
            log_msg("Paytm AMOUNT MISMATCH for $orderId expected=$expected paid=$txnAmount", 'error');
            $order['payment_status'] = 'FAILED';
            $order['payment_txn_id'] = $txnId;
            file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ../../thankyou.html?order=' . urlencode($orderId) . '&status=failed&reason=amount');
            exit;
        }
        $order['payment_status'] = 'PAID';
        $order['payment_method'] = 'paytm';
        $order['payment_txn_id'] = $txnId;
        log_msg("Payment SUCCESS for $orderId txn=$txnId");
    } else {
        $order['payment_status'] = 'FAILED';
        $order['payment_txn_id'] = $txnId;
        log_msg("Payment FAILED for $orderId status=$txnStatus");
    }
    file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    @chmod($orderFile, 0600);
}

// Redirect customer to order status page
$status = ($txnStatus === 'TXN_SUCCESS') ? 'success' : 'failed';
header('Location: ../../thankyou.html?order=' . urlencode($orderId) . '&status=' . $status);
exit;
