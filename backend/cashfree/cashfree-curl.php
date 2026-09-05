<?php
/**
 * ============================================================
 *  CASHFREE - API CURL HELPER
 *  Minimal dependency-free wrapper around the Cashfree PG REST API.
 *  Auth: Bearer token (client_id + client_secret) / x-api-version.
 * ============================================================
 */

/**
 * Perform a Cashfree API request.
 *
 * @param string $method   'POST' | 'GET'
 * @param string $endpoint e.g. '/orders' or '/orders/{order_id}/payments'
 * @param array|null $body JSON payload for POST
 * @param string $clientId  Cashfree Client ID
 * @param string $clientSecret Cashfree Client Secret
 * @return array ['success'=>bool, 'data'=>array|null, 'error'=>string|null]
 */
function cf_api_request($method, $endpoint, $body, $clientId, $clientSecret)
{
    $url = CASHFREE_API_URL . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'x-api-version: 2023-08-01',
        'x-client-id: ' . $clientId,
        'x-client-secret: ' . $clientSecret
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
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
