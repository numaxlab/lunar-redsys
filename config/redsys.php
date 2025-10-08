<?php

return [
    'default' => [
        'trade_name' => 'Traficantes de Sueños',
        'owner' => 'Librería',
        'merchant_code' => env('REDSYS_MERCHANT_CODE'),
        'terminal' => env('REDSYS_TERMINAL'),
        'key' => env('REDSYS_KEY'),
        'currency' => env('REDSYS_CURRENCY'),
        'environment' => env('REDSYS_ENVIRONMENT'),
    ],
];
