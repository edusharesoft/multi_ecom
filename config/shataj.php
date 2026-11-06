<?php

/*
|--------------------------------------------------------------------------
| Shataj integrations: ShatajPay (payment gateway) & Shataj SMS Gateway
|--------------------------------------------------------------------------
| Both drivers are generic: endpoint paths, request field names and response
| keys are configurable here / in .env, so they can be matched to the
| official API docs without touching the service classes.
| Response keys support "dot.notation" for nested JSON (e.g. data.payment_url).
*/

return [

    'sms' => [
        // shataj = send real SMS, log = write SMS to storage/logs/laravel.log (testing)
        'driver' => env('SHATAJ_SMS_DRIVER', 'log'),
        'url' => env('SHATAJ_SMS_URL', 'https://sms.shataj.com/api/v1/send'),
        'method' => strtoupper(env('SHATAJ_SMS_METHOD', 'POST')),   // GET | POST
        'format' => env('SHATAJ_SMS_FORMAT', 'form'),                // form | json (POST body)
        'api_key' => env('SHATAJ_SMS_API_KEY'),
        'sender_id' => env('SHATAJ_SMS_SENDER_ID'),
        'auth_header' => env('SHATAJ_SMS_AUTH_HEADER'),              // e.g. "Authorization" => sends "Bearer {api_key}"; leave empty to send key as a param
        'country_code' => env('SHATAJ_SMS_COUNTRY_CODE', '88'),      // 017xxxxxxxx -> 88017xxxxxxxx
        'timeout' => 15,

        // Request parameter names expected by the gateway
        'params' => [
            'api_key' => env('SHATAJ_SMS_PARAM_API_KEY', 'api_key'),
            'sender_id' => env('SHATAJ_SMS_PARAM_SENDER', 'senderid'),
            'to' => env('SHATAJ_SMS_PARAM_TO', 'number'),
            'message' => env('SHATAJ_SMS_PARAM_MESSAGE', 'message'),
        ],
        'extra_params' => [],   // any fixed extra params, e.g. ['type' => 'unicode']

        // How to decide the send succeeded (HTTP 2xx is always required)
        'success_key' => env('SHATAJ_SMS_SUCCESS_KEY', ''),          // empty = HTTP 2xx is enough
        'success_values' => explode(',', env('SHATAJ_SMS_SUCCESS_VALUES', 'success,ok,true,1,200,202')),
    ],

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 5,
        'resend_seconds' => 60,
        'max_attempts' => 5,
        'message' => env('OTP_MESSAGE', 'Your :app verification code is :code. Valid for :minutes minutes. Do not share it.'),
    ],

    'pay' => [
        // live | sandbox = call ShatajPay API, fake = built-in test page (no API needed)
        'mode' => env('SHATAJPAY_MODE', 'fake'),
        // SHATAJPAY_API_URL (old SHATAJPAY_BASE_URL pointed to a host that does not exist and is ignored)
        'base_url' => rtrim(env('SHATAJPAY_API_URL', 'https://pay.shataj.com'), '/'),
        'endpoint' => '/api/v1/user/payment-system',
        'username' => env('SHATAJPAY_USERNAME'),
        'password' => env('SHATAJPAY_PASSWORD'),
        'app_key' => env('SHATAJPAY_APP_KEY'),
        'app_secret' => env('SHATAJPAY_APP_SECRET'),
        'gateway_id' => env('SHATAJPAY_GATEWAY_ID'),
        'currency' => env('SHATAJPAY_CURRENCY', 'BDT'),
        'timeout' => 20,

        // A payment that has not come back within this many hours can no longer be completed by a callback.
        'callback_valid_hours' => 48,

        // Callback "status" values sent by ShatajPay (lower-case)
        'success_values' => ['complete', 'completed', 'success', 'successful', 'paid'],
        'cancel_values' => ['cancel', 'cancelled', 'canceled'],
    ],
    
    ];
