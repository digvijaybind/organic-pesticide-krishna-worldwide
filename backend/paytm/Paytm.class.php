<?php
/**
 * ============================================================
 *   PAYTM PAYMENT GATEWAY - SERVER-SIDE LIBRARY
 * ============================================================
 *   IMPORTANT: This file MUST only run on the SERVER.
 *   It never outputs merchant keys to the browser.
 *
 *   This is a minimal, dependency-free Paytm integration
 *   using the current Paytm PG (Paytm Gateway) APIs.
 *
 *   REFERENCE (official Paytm docs):
 *   https://developer.paytm.com/docs/payment-gateway/
 *
 *   Workflow:
 *   1. Client submits order -> server creates order in DB
 *   2. Server generates a checksum (signature) for the order
 *   3. Server requests a transaction token from Paytm
 *   4. Client is redirected to Paytm payment page (or JS checkout)
 *   5. Paytm calls back to paytm-response.php -> verify checksum
 *   6. Order marked PAID / FAILED
 * ============================================================
 */

class Paytm
{
    private $merchantId;
    private $merchantKey;
    private $website;
    private $callbackUrl;
    private $initiateUrl;

    public function __construct()
    {
        $this->merchantId  = PAYTM_MERCHANT_ID;
        $this->merchantKey = PAYTM_MERCHANT_KEY;
        $this->website     = PAYTM_WEBSITE;
        $this->callbackUrl = PAYTM_CALLBACK_URL;
        $this->initiateUrl = PAYTM_INITIATE_URL;
    }

    /**
     * Generate the checksum/signature for request parameters.
     * This is the same algorithm Paytm uses (HMAC-SHA256).
     */
    public function generateChecksum(array $params, $orderId = null)
    {
        // Paytm algorithm: sort params, build string, HMAC-SHA256
        $paramsString = $this->buildStringFromParams($params);
        $checksum = hash_hmac('sha256', $paramsString, $this->merchantKey);
        return $checksum;
    }

    /**
     * Verify checksum returned by Paytm in the callback.
     */
    public function verifyChecksum(array $params, $checksum)
    {
        // Remove CHECKSUMHASH from params for verification
        $paramsWithoutChecksum = $params;
        if (isset($paramsWithoutChecksum['CHECKSUMHASH'])) {
            unset($paramsWithoutChecksum['CHECKSUMHASH']);
        }
        $paramsString = $this->buildStringFromParams($paramsWithoutChecksum);
        $generated = hash_hmac('sha256', $paramsString, $this->merchantKey);
        return hash_equals($generated, $checksum);
    }

    /**
     * Build the string used for checksum generation.
     * Paytm's algorithm: concatenate values with '|' after sort and filter.
     */
    private function buildStringFromParams(array $params)
    {
        // Sort keys
        ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $str .= $value . '|';
        }
        return rtrim($str, '|');
    }

    /**
     * Request a transaction token from Paytm for a specific order.
     * Returns the token on success (used for JS Checkout / POPUP).
     */
    public function getTransactionToken($orderId, $amount, array $customer)
    {
        $requestParams = [
            'requestType'   => 'Payment',
            'mid'           => $this->merchantId,
            'orderId'       => $orderId,
            'websiteName'   => $this->website,
            'txnAmount'     => json_encode(['value' => number_format($amount, 2, '.', ''), 'currency' => 'INR']),
            'userInfo'      => json_encode([
                'custId'    => $customer['phone'],
                'email'     => $customer['email'],
                'firstName' => $customer['name'],
                'lastName'  => '',
                'mobile'    => $customer['phone']
            ]),
            'callbackUrl'   => $this->callbackUrl
        ];

        $payload = $requestParams;
        $payload['signature'] = $this->generateChecksum($requestParams);

        $url = $this->initiateUrl;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'Curl error: ' . $curlError];
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['body']['txnToken'])) {
            log_msg('Paytm initiate failed: ' . $response, 'error');
            return ['success' => false, 'error' => 'Could not get token from Paytm'];
        }

        return [
            'success' => true,
            'txnToken' => $data['body']['txnToken'],
            'orderId' => $orderId
        ];
    }

    /**
     * Redirect-based checkout: build the HTML auto-post form
     * that submits parameters (with signature) to Paytm.
     */
    public function buildCheckoutForm($orderId, $amount, array $customer)
    {
        $params = [
            'MID'           => $this->merchantId,
            'ORDER_ID'      => $orderId,
            'CUST_ID'       => $customer['phone'],
            'TXN_AMOUNT'    => number_format($amount, 2, '.', ''),
            'CURRENCY'      => 'INR',
            'INDUSTRY_TYPE_ID' => 'Retail',
            'WEBSITE'       => $this->website,
            'CHANNEL_ID'    => 'WEB',
            'CALLBACK_URL'  => $this->callbackUrl,
            'EMAIL'         => $customer['email'],
            'MOBILE_NO'     => $customer['phone']
        ];
        // Note: When using initiateTransaction API, the new flow differs.
        // This form approach is the legacy flow; the JS Checkout is recommended.
        // See Paytm v2 checkout.
        ksort($params);
        $params['CHECKSUMHASH'] = $this->generateChecksum($params, $orderId);

        $html = '<form id="paytm_form" name="paytm_form" action="' . PAYTM_TRANSACTION_URL . '" method="post" style="display:none;">';
        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
        }
        $html .= '</form><script type="text/javascript">document.getElementById("paytm_form").submit();</script>';
        return $html;
    }
}
