<?php

/*
|--------------------------------------------------------------------------
| Shataj integrations: ShatajPay (payment gateway) & Shataj SMS Gateway
|--------------------------------------------------------------------------
| Values here are only the FALLBACK. The Super Admin panel
| (Admin → Payment & SMS Gateways) stores credentials in the database
| (encrypted) and those always win over .env.
|
| ShatajPay docs:  https://github.com/mspmedisoft/shataj-payment-gateway-integration
| Shataj SMS docs: https://sms.shataj.com  (PHP Integration)
*/

return [

    'sms' => [
        // shataj = send real SMS through sms.shataj.com, log = write SMS to storage/logs/laravel.log (testing)
        'driver' => env('SHATAJ_SMS_DRIVER', 'log'),
        'server' => rtrim(env('SHATAJ_SMS_SERVER', 'https://sms.shataj.com'), '/'),
        'api_key' => env('SHATAJ_SMS_API_KEY'),
        // Device to send from: empty/0 = primary device, "1" = device 1, "1|0" = device 1 SIM slot 1
        'device' => env('SHATAJ_SMS_DEVICE', '0'),
        // 88 = Bangladesh. 017xxxxxxxx -> +88017xxxxxxxx
        'country_code' => env('SHATAJ_SMS_COUNTRY_CODE', '88'),
        'timeout' => 20,
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
