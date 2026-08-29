<?php
/**
 * ============================================================
 *  ORGANIC PESTICIDE - KRISHNA WORLDWIDE
 *  Backend Configuration
 * ============================================================
 *  IMPORTANT SECURITY NOTE:
 *  This file holds SENSITIVE configuration (payment keys).
 *  NEVER expose this file to the web directly.
 *
 *  RECOMMENDED LAYOUT (cPanel):
 *   - Keep this file OUTSIDE the public web root (e.g. one level
 *     above public_html) and include it via an absolute path, OR
 *   - Keep it where it is but rely on the backend/.htaccess to
 *     block direct access, AND
 *   - Store the REAL LIVE keys as environment variables, never
 *     hardcoded in this file. See "payment keys" below.
 *
 *  Payment keys are read from environment variables so that LIVE
 *  keys never live in source code or in any web-served file.
 * ============================================================
 */

// --- Error reporting (disable display in production) ---
error_reporting(E_ALL);
ini_set('log_errors', 1);
$isDev = (getenv('APP_ENV') === 'dev');
ini_set('display_errors', $isDev ? '1' : '0');

// --- Store settings (used across the site) ---
define('STORE_NAME', 'Organic Pesticide - Krishna Worldwide');
define('STORE_EMAIL', 'info@organicpesticide.in');
define('STORE_PHONE', '919876543210');           // country code + number, no +
define('CURRENCY', 'INR');
define('FREE_SHIPPING_THRESHOLD', 500);          // above this => free shipping
define('SHIPPING_FEE', 49);                       // flat shipping fee below threshold

// --- Paths ---
// config.php lives at <webroot>/backend/config/config.php
// so the website root is TWO levels up. If you MOVE this file outside
// public_html, adjust BASE_DIR accordingly (e.g. __DIR__ when placed
// at /home/user/config/config.php).
define('BASE_DIR', __DIR__ . '/../..');           // root of website

// Orders/logs can be placed ABOVE the web root via env for extra safety,
// defaulting to inside the site when not set.
$ordersOverride = getenv('ORDERS_DIR');
$logsOverride = getenv('LOGS_DIR');
define('ORDERS_DIR', $ordersOverride ?: (BASE_DIR . '/backend/orders'));
define('LOG_DIR', $logsOverride ?: (BASE_DIR . '/backend/logs'));

// Ensure directories exist
foreach ([ORDERS_DIR, LOG_DIR] as $d) {
    if (!is_dir($d)) { @mkdir($d, 0755, true); }
}

/* ============================================================
 *  RAZORPAY PAYMENT GATEWAY (PRIMARY)
 * ============================================================
 *  Keys are read from environment variables so they are never
 *  committed or exposed to the browser.
 *
 *  cPanel: set these in the account/domain "Environment Variables"
 *   - RAZORPAY_ENV       = test | live
 *   - RAZORPAY_KEY_ID    = your Key ID
 *   - RAZORPAY_KEY_SECRET= your Key Secret
 *
 *  Modes:
 *   test => https://api.razorpay.com  (test keys)
 *   live => https://api.razorpay.com  (live keys)
 * ============================================================
 */
define('RAZORPAY_ENV', strtolower(getenv('RAZORPAY_ENV') ?: 'test')); // 'test' or 'live'
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'YOUR_RAZORPAY_KEY_ID');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'YOUR_RAZORPAY_KEY_SECRET');
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1');
define('RAZORPAY_CALLBACK_URL', getenv('RAZORPAY_CALLBACK_URL') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/backend/razorpay/verify.php');

// Fail fast if we are in LIVE mode but no real keys are set.
if (RAZORPAY_ENV === 'live') {
    $bogusLive = (RAZORPAY_KEY_ID === 'YOUR_RAZORPAY_KEY_ID' || RAZORPAY_KEY_SECRET === 'YOUR_RAZORPAY_KEY_SECRET');
    if ($bogusLive) {
        http_response_code(500);
        exit('RAZORPAY_ENV=live but keys are not configured in environment variables');
    }
}

/* ============================================================
 *  PAYTM PAYMENT GATEWAY (FALLBACK / SECONDARY)
 * ============================================================
 *  ALSO read from environment variables (server-side only).
 *   - PAYTM_ENV         = TEST | PROD
 *   - PAYTM_MERCHANT_ID
 *   - PAYTM_MERCHANT_KEY
 * ============================================================
 */
define('PAYTM_ENV', strtoupper(getenv('PAYTM_ENV') ?: 'TEST'));   // 'TEST' or 'PROD'

if (PAYTM_ENV === 'TEST') {
    define('PAYTM_MERCHANT_ID', getenv('PAYTM_MERCHANT_ID') ?: 'YOUR_TEST_MERCHANT_ID');
    define('PAYTM_MERCHANT_KEY', getenv('PAYTM_MERCHANT_KEY') ?: 'YOUR_TEST_MERCHANT_KEY');
    define('PAYTM_WEBSITE', 'WEBSTAGING');
    define('PAYTM_TRANSACTION_URL', 'https://securegw-stage.paytm.in/theia/processTransaction');
    define('PAYTM_INITIATE_URL', 'https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=' . (getenv('PAYTM_MERCHANT_ID') ?: 'YOUR_TEST_MERCHANT_ID') . '&orderId=ORDERID');
    define('PAYTM_CALLBACK_URL', getenv('PAYTM_CALLBACK_URL') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/backend/paytm/paytm-response.php');
} else {
    define('PAYTM_MERCHANT_ID', getenv('PAYTM_MERCHANT_ID') ?: 'PASTE_LIVE_MERCHANT_ID_HERE');
    define('PAYTM_MERCHANT_KEY', getenv('PAYTM_MERCHANT_KEY') ?: 'PASTE_LIVE_MERCHANT_KEY_HERE');
    define('PAYTM_WEBSITE', 'DEFAULT');
    define('PAYTM_TRANSACTION_URL', 'https://securegw.paytm.in/theia/processTransaction');
    define('PAYTM_INITIATE_URL', 'https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=' . (getenv('PAYTM_MERCHANT_ID') ?: 'PASTE_LIVE_MERCHANT_ID_HERE') . '&orderId=ORDERID');
    define('PAYTM_CALLBACK_URL', getenv('PAYTM_CALLBACK_URL') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/backend/paytm/paytm-response.php');
}

// --- Simple logger ---
function log_msg($message, $type = 'info') {
    if (!defined('LOG_DIR')) return;
    $line = '[' . date('Y-m-d H:i:s') . "] [$type] " . $message . PHP_EOL;
    @file_put_contents(LOG_DIR . '/app.log', $line, FILE_APPEND);
}
