<?php

use NumaxLab\Lunar\Redsys\RedsysPayment;
use Sermepa\Tpv\Tpv;

beforeEach(function () {
    config([
        'services.redsys.membership.key' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
        'services.redsys.membership.environment' => 'test',
    ]);
});

it('extracts Ds_Merchant_Identifier from capture parameters when present', function () {
    $payment = new RedsysPayment();

    // Build a valid Redsys response payload containing Ds_Merchant_Identifier
    $tpv = new Tpv();
    $params = json_encode([
        'Ds_Response' => '0000',
        'Ds_Order' => '000000000001',
        'Ds_Merchant_Identifier' => 'A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6',
        'Ds_Amount' => '1000',
        'Ds_Currency' => '978',
    ]);
    $encoded = base64_encode($params);

    $identifier = $payment->extractMerchantIdentifier($encoded);

    expect($identifier)->toBe('A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6');
});

it('returns null when Ds_Merchant_Identifier is absent from capture parameters', function () {
    $payment = new RedsysPayment();

    $params = json_encode([
        'Ds_Response' => '0000',
        'Ds_Order' => '000000000001',
        'Ds_Amount' => '1000',
        'Ds_Currency' => '978',
    ]);
    $encoded = base64_encode($params);

    $identifier = $payment->extractMerchantIdentifier($encoded);

    expect($identifier)->toBeNull();
});
