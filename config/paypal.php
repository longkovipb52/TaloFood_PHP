<?php
// PayPal Configuration
define('PAYPAL_CLIENT_ID', 'AXWE0QjrhTloExOwcJHet8zMuNM00KF8FJeGih-65gR8-MLQdB8DZgxqUxh5NQ0351woMj9Ry0FR8Eiz');
define('PAYPAL_SECRET', 'EF45wCo1Px20UDqnFLtF6XSm_VR8gqPe3PshpQOTVp-Ryg4ETFjrEqTCGOh2FyPXqX_caoRBugyilze2');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' hoặc 'live'

// PayPal API URLs
define('PAYPAL_API_URL', PAYPAL_MODE === 'sandbox' 
    ? 'https://api-m.sandbox.paypal.com' 
    : 'https://api-m.paypal.com');

// Return URLs - Sửa lại port 8080 cho XAMPP
define('PAYPAL_SUCCESS_URL', 'http://localhost:8080/TaloFood/paypal_success.php');
define('PAYPAL_CANCEL_URL', 'http://localhost:8080/TaloFood/paypal_cancel.php');

// Exchange rate (1 USD = 25,000 VND)
define('VND_TO_USD', 25000);

/**
 * Get PayPal access token
 */
function getPayPalAccessToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return false;
    }

    $response_data = json_decode($response, true);
    return $response_data['access_token'] ?? false;
}

/**
 * Create PayPal order
 */
function createPayPalOrder($amount_vnd) {
    // Chuyển đổi VND sang USD
    $amount_usd = round($amount_vnd / VND_TO_USD, 2);

    $access_token = getPayPalAccessToken();
    if (!$access_token) {
        return false;
    }

    $payload = array(
        'intent' => 'CAPTURE',
        'purchase_units' => array(
            array(
                'amount' => array(
                    'currency_code' => 'USD',
                    'value' => $amount_usd
                )
            )
        ),
        'application_context' => array(
            'return_url' => PAYPAL_SUCCESS_URL,
            'cancel_url' => PAYPAL_CANCEL_URL
        )
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 201) {
        return false;
    }

    return json_decode($response, true);
}

// Hàm capture payment từ PayPal
function capturePayPalOrder($order_id) {
    $access_token = getPayPalAccessToken();
    if (!$access_token) {
        return false;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . "/v2/checkout/orders/" . $order_id . "/capture");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 201) {
        return false;
    }

    return json_decode($response, true);
}