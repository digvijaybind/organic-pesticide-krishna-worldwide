<?php
/**
 * ============================================================
 *  SHIPROCKET - API CURL HELPER (v2 EXTERNAL API)
 *  Dependency-free wrapper around the Shiprocket REST API.
 *
 *  Auth: Bearer token. Two supported methods (prefer #1):
 *    1) SHIPROCKET_TOKEN_KEY - a long-lived token (Application /
 *       Token API key) set via env. Used as the bearer token directly.
 *    2) SHIPROCKET_EMAIL + SHIPROCKET_PASSWORD - obtained via
 *       POST /auth/login, cached to disk, refreshed automatically.
 * ============================================================
 */

/**
 * Get the Shiprocket bearer token (cached + auto-refreshed).
 *
 * @return string|false Bearer token, or false on failure.
 */
function sr_get_token()
{
    // Preferred: a directly-provided token key.
    if (defined('SHIPROCKET_TOKEN_KEY') && SHIPROCKET_TOKEN_KEY !== '') {
        return SHIPROCKET_TOKEN_KEY;
    }

    // Else: email/password flow with on-disk cache.
    if (SHIPROCKET_EMAIL === '' || SHIPROCKET_PASSWORD === '') {
        return false;
    }

    $cacheFile = SHIPROCKET_TOKEN_FILE;
    if (is_file($cacheFile)) {
        $cached = @json_decode(@file_get_contents($cacheFile), true);
        if (is_array($cached) && !empty($cached['token'])) {
            $expires = isset($cached['expires_at']) ? (int) $cached['expires_at'] : 0;
            if ($expires === 0 || $expires > time() + 300) {
                return $cached['token'];
            }
        }
    }

    // Fetch a fresh token.
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => (defined('SHIPROCKET_API_URL') ? SHIPROCKET_API_URL : 'https://apiv2.shiprocket.in/v1/external') . '/auth/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'email'    => SHIPROCKET_EMAIL,
            'password' => SHIPROCKET_PASSWORD
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode < 200 || $httpCode >= 300) {
        return false;
    }

    $data = json_decode($response, true);
    if (empty($data['token'])) {
        return false;
    }

    // Cache it (Shiprocket tokens are typically valid for 24h; cache ~23h).
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
    @file_put_contents($cacheFile, json_encode([
        'token'      => $data['token'],
        'expires_at' => time() + (23 * 3600),
        'cached_at'  => date('c')
    ]));
    @chmod($cacheFile, 0600);

    return $data['token'];
}

/**
 * Perform a Shiprocket API request.
 *
 * @param string $method   'POST' | 'GET' | 'PATCH'
 * @param string $endpoint e.g. '/shipments/create' or '/tracking/awb/'.$awb
 * @param array|null $body JSON payload
 * @return array ['success'=>bool, 'data'=>array|null, 'error'=>string|null]
 */
function sr_api_request($method, $endpoint, $body = null)
{
    $token = sr_get_token();
    if ($token === false || $token === '') {
        return ['success' => false, 'data' => null, 'error' => 'Shiprocket authentication not configured'];
    }

    $url = (defined('SHIPROCKET_API_URL') ? SHIPROCKET_API_URL : 'https://apiv2.shiprocket.in/v1/external') . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $m = strtoupper($method);
    if ($m === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    } elseif ($m === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'data' => null, 'error' => 'Curl error: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $err = isset($data['message']) ? $data['message'] : ('HTTP ' . $httpCode);
        return ['success' => false, 'data' => $data, 'error' => $err];
    }

    return ['success' => true, 'data' => $data, 'error' => null];
}
