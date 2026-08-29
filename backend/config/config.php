<?php
/**
 * ============================================================
 *  ORGANIC PESTICIDE - KRISHNA WORLDWIDE
 *  Backend Configuration
 * ============================================================
 *  IMPORTANT SECURITY NOTE:
 *  This file contains sensitive configuration (Paytm keys, DB creds).
 *  NEVER expose this file to the web directly.
 *  Ensure it lives OUTSIDE your public_html web root, OR
 *  protect it with .htaccess (deny all).
 *
 *  For shared hosting (cPanel), the recommended location is
 *  one level ABOVE public_html, e.g.:
 *      home/username/config/config.php
 *  and reference it from public_html/backend via relative path.
 * ============================================================
 */

// --- Error reporting (disable in production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Store settings (used across the site) ---
define('STORE_NAME', 'Organic Pesticide - Krishna Worldwide');
define('STORE_EMAIL', 'info@organicpesticide.in');
define('STORE_PHONE', '919876543210');           // country code + number, no +
define('CURRENCY', 'INR');
define('FREE_SHIPPING_THRESHOLD', 500);          // above this => free shipping
define('SHIPPING_FEE', 49);                       // flat shipping fee below threshold

// --- Paths ---
// config.php lives at <webroot>/backend/config/config.php
// so the website root is TWO levels up.
define('BASE_DIR', __DIR__ . '/../..');           // root of website
define('ORDERS_DIR', BASE_DIR . '/backend/orders');
define('LOG_DIR', BASE_DIR . '/backend/logs');

// Ensure directories exist
if (!is_dir(ORDERS_DIR)) { @mkdir(ORDERS_DIR, 0755, true); }
if (!is_dir(LOG_DIR)) { @mkdir(LOG_DIR, 0755, true); }

/* ============================================================
 *  PAYTM PAYMENT GATEWAY CONFIGURATION
 * ============================================================
 *  !! CRITICAL SECURITY !!
 *  These keys are used ONLY on the SERVER side.
 *  They MUST NEVER appear in any HTML/JS/CSS file served to the
 *  browser, or anyone can steal your merchant key.
 *
 *  For testing, Paytm provides separate TEST credentials.
 *  NEVER put your LIVE merchant key here until you have moved
 *  this file OUTSIDE the public web root.
 *
 *  How to get TEST credentials:
 *  - Log into Paytm dashboard -> "Dashboard" -> "Keys & Certificates"
 *  - Use the TEST Merchant ID & TEST Merchant Key
 *  - Paytm currently offers a "Paytm PG" sandbox at:
 *      https://business.paytm.com/ (developers section)
 *
 *  ----- PAYTM ENV SELECTION -----
 *  Set PAYTM_ENV to 'TEST' while developing, 'PROD' when live.
 *  ============================================================
 */

define('PAYTM_ENV', 'TEST');   // 'TEST' or 'PROD'

if (PAYTM_ENV === 'TEST') {
    // --- TEST / STAGING credentials (safe to show) ---
    // Replace these with your real TEST merchant details from Paytm dashboard.
    define('PAYTM_MERCHANT_ID', 'YOUR_TEST_MERCHANT_ID');
    define('PAYTM_MERCHANT_KEY', 'YOUR_TEST_MERCHANT_KEY');
    define('PAYTM_WEBSITE', 'WEBSTAGING');
    define('PAYTM_TRANSACTION_URL', 'https://securegw-stage.paytm.in/theia/processTransaction');
    define('PAYTM_INITIATE_URL', 'https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=YOUR_TEST_MERCHANT_ID&orderId=ORDERID');
    define('PAYTM_CALLBACK_URL', 'http://localhost/paytm/paytm-response.php'); // update for production
} else {
    // --- PRODUCTION / LIVE credentials (DO NOT commit these) ---
    // When going live, replace these with your LIVE keys.
    // IMPORTANT: fill these in on the server, NOT in your git repo.
    define('PAYTM_MERCHANT_ID', 'PASTE_LIVE_MERCHANT_ID_HERE');
    define('PAYTM_MERCHANT_KEY', 'PASTE_LIVE_MERCHANT_KEY_HERE');
    define('PAYTM_WEBSITE', 'DEFAULT');
    define('PAYTM_TRANSACTION_URL', 'https://securegw.paytm.in/theia/processTransaction');
    define('PAYTM_INITIATE_URL', 'https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=PASTE_LIVE_MERCHANT_ID_HERE&orderId=ORDERID');
    define('PAYTM_CALLBACK_URL', 'https://organicpesticide.yourdomain.com/backend/paytm/paytm-response.php');
}

// CORS / settings
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Simple logger ---
function log_msg($message, $type = 'info') {
    $line = '[' . date('Y-m-d H:i:s') . "] [$type] " . $message . PHP_EOL;
    @file_put_contents(LOG_DIR . '/app.log', $line, FILE_APPEND);
}
