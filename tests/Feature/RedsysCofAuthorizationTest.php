<?php

use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\TaxClass;
use NumaxLab\Lunar\Redsys\RedsysPayment;

beforeEach(function () {
    config([
        'services.redsys.membership.merchant_code' => '123456789',
        'services.redsys.membership.key' => 'base64encodedkey==',
        'services.redsys.membership.currency' => '978',
        'services.redsys.membership.terminal' => '1',
        'services.redsys.membership.environment' => 'test',
        'services.redsys.membership.trade_name' => 'Traficantes de Sueños',
        'services.redsys.membership.owner' => 'Traficantes de Sueños',
        'services.redsys.membership.cof_enabled' => true,
    ]);
});

it('aborts safely with failure when cof_enabled is false and is_cof is requested', function () {
    config(['services.redsys.membership.cof_enabled' => false]);

    $payment = new RedsysPayment();
    $payment->withData([
        'config_key' => 'membership',
        'method' => 'C',
        'product_description' => 'Membresía',
        'url_ok' => 'https://example.com/ok',
        'url_ko' => 'https://example.com/ko',
        'is_cof' => true,
    ]);

    $result = $payment->authorize();

    expect($result)->toBeInstanceOf(\Lunar\Base\DataTransferObjects\PaymentAuthorize::class)
        ->and($result->success)->toBeFalse()
        ->and($result->message)->toContain('COF');
});

it('does not abort when cof_enabled is true and is_cof is requested', function () {
    // This test drives that the COF guard does NOT fire when config is properly set.
    // authorize() will proceed past the guard and then fail on the cart/order side
    // (because no cart is set). We verify the error is NOT a COF config guard failure.

    $payment = new RedsysPayment();
    $payment->withData([
        'config_key' => 'membership',
        'method' => 'C',
        'product_description' => 'Membresía',
        'url_ok' => 'https://example.com/ok',
        'url_ko' => 'https://example.com/ko',
        'is_cof' => true,
    ]);

    // Expect an Error from null cart access — NOT a PaymentAuthorize COF failure
    expect(fn () => $payment->authorize())
        ->toThrow(Error::class);
});
