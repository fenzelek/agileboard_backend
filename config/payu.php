<?php

return [
    'sandbox' => env('PAYU_SANDBOX', true),
    'back_url' => env('PAYU_BACK_URL', ''),

    'pln' => [
        'pos_id' => env('PAYU_PLN_POS_ID', ''),
        'md5' => env('PAYU_PLN_MD5', ''),
        'client_id' => env('PAYU_PLN_CLIENT_ID', ''),
        'client_secret' => env('PAYU_PLN_CLIENT_SECRED', ''),
        'notify_url' => env('PAYU_PLN_NOTIFY_URL', ''),
    ],
    'eur' => [
        'pos_id' => env('PAYU_EUR_POS_ID', ''),
        'md5' => env('PAYU_EUR_MD5', ''),
        'client_id' => env('PAYU_EUR_CLIENT_ID', ''),
        'client_secret' => env('PAYU_EUR_CLIENT_SECRED', ''),
        'notify_url' => env('PAYU_EUR_NOTIFY_URL', ''),
    ],
];
