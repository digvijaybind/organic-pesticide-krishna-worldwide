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
 *  SECURE .env LOADER (KEY MANAGEMENT)
 * ============================================================
 *  Real gateway keys MUST live on the server, never in chat or in
 *  tracked source files. On cPanel system environment variables are
 *  often hard to set, so we support an optional .env file placed
 *  OUTSIDE the web root (recommended: UP ONE level above BASE_DIR).
 *
 *  Placement options (checked in order, first existing wins):
 *    1. <BASE_DIR>/../.env         (outside the site, recommended)
 *    2. <BASE_DIR>/.env            (inside site - .htaccess blocks it)
 *
 *  The loader ONLY calls putenv() for keys that are NOT already real
 *  system environment variables, so system env vars always win. It
 *  never outputs any value.
 * ============================================================
 */
(function () {
    $candidates = [realpath(BASE_DIR . '/../.env'), BASE_DIR . '/.env'];
    $envFile = null;
    foreach ($candidates as $c) {
        if (is_file($c)) { $envFile = $c; break; }
    }
    if (!$envFile) { return; }

    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) { return; }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, ';') === 0) {
            continue; // comment
        }
        $eq = strpos($line, '=');
        if ($eq === false) { continue; }
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        // Strip surrounding quotes if present.
        if (strlen($val) >= 2) {
            $q = $val[0];
            if (($q === '"' || $q === "'") && substr($val, -1) === $q) {
                $val = substr($val, 1, -1);
            }
        }
        $key = strtoupper($key);
        if ($key === '') { continue; }
        // Only set if not already a real system env var (system wins).
        if (getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }
})();

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

// Fail fast if in PROD but keys are not really configured.
if (PAYTM_ENV === 'PROD') {
    $bogus = (PAYTM_MERCHANT_ID === 'PASTE_LIVE_MERCHANT_ID_HERE' || PAYTM_MERCHANT_ID === 'YOUR_TEST_MERCHANT_ID' || PAYTM_MERCHANT_KEY === 'PASTE_LIVE_MERCHANT_KEY_HERE' || PAYTM_MERCHANT_KEY === 'YOUR_TEST_MERCHANT_KEY');
    if ($bogus) {
        http_response_code(500);
        exit('PAYTM_ENV=PROD but a valid merchant key is not configured in environment variables');
    }
}

/* ============================================================
 *  CASHFREE PAYMENT GATEWAY (ADDITIONAL ONLINE OPTION)
 * ============================================================
 *  Server-side keys come from environment variables only.
 *   - CASHFREE_ENV           = test | prod
 *   - CASHFREE_CLIENT_ID
 *   - CASHFREE_CLIENT_SECRET
 *  Callback URL (redirect after payment) configured via env.
 * ============================================================
 */
define('CASHFREE_ENV', strtolower(getenv('CASHFREE_ENV') ?: 'test')); // 'test' or 'prod'
if (CASHFREE_ENV === 'prod') {
    define('CASHFREE_API_URL', 'https://api.cashfree.com/pg');
    define('CASHFREE_CHECKOUT_URL', 'https://sdk.cashfree.com/js/v3/cashfree.js');
} else {
    define('CASHFREE_API_URL', 'https://sandbox.cashfree.com/pg');
    define('CASHFREE_CHECKOUT_URL', 'https://sdk.cashfree.com/js/v3/cashfree.js');
}
define('CASHFREE_CLIENT_ID', getenv('CASHFREE_CLIENT_ID') ?: 'YOUR_CASHFREE_CLIENT_ID');
define('CASHFREE_CLIENT_SECRET', getenv('CASHFREE_CLIENT_SECRET') ?: 'YOUR_CASHFREE_CLIENT_SECRET');
define('CASHFREE_CALLBACK_URL', getenv('CASHFREE_CALLBACK_URL') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/backend/cashfree/verify.php');

// Fail fast if in prod but keys are not really configured.
if (CASHFREE_ENV === 'prod') {
    $bogusCf = (CASHFREE_CLIENT_ID === 'YOUR_CASHFREE_CLIENT_ID' || CASHFREE_CLIENT_SECRET === 'YOUR_CASHFREE_CLIENT_SECRET');
    if ($bogusCf) {
        http_response_code(500);
        exit('CASHFREE_ENV=prod but client credentials are not configured in environment variables');
    }
}

/* ============================================================
 *  SHIPROCKET LOGISTICS (SHIPPING + LABEL + TRACKING)
 * ============================================================
 *  Shiprocket authenticates with email + password OR a bearer token.
 *  The token is cached to disk (backend/orders/.shiprocket_token) and
 *  refreshed automatically. All credentials come from env vars.
 *   - SHIPROCKET_EMAIL
 *   - SHIPROCKET_PASSWORD
 *  (Prefer setting a STORED token via SHIPROCKET_TOKEN_KEY for the
 *   production SysOps/Token API; the email/password flow is used if
 *   no token key is provided.)
 * ============================================================
 */
define('SHIPROCKET_API_URL', getenv('SHIPROCKET_API_URL') ?: 'https://apiv2.shiprocket.in/v1/external');
define('SHIPROCKET_EMAIL', getenv('SHIPROCKET_EMAIL') ?: '');
define('SHIPROCKET_PASSWORD', getenv('SHIPROCKET_PASSWORD') ?: '');
define('SHIPROCKET_TOKEN_KEY', getenv('SHIPROCKET_TOKEN_KEY') ?: '');   // optional persistent token
define('SHIPROCKET_TOKEN_FILE', (ORDERS_DIR ?: (BASE_DIR . '/backend/orders')) . '/.shiprocket_token');

// --- Simple logger ---
function log_msg($message, $type = 'info') {
    if (!defined('LOG_DIR')) return;
    $line = '[' . date('Y-m-d H:i:s') . "] [$type] " . $message . PHP_EOL;
    @file_put_contents(LOG_DIR . '/app.log', $line, FILE_APPEND);
}
