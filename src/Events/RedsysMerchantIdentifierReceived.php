<?php

namespace NumaxLab\Lunar\Redsys\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after a successful Redsys COF checkout when Redsys returns a Ds_Merchant_Identifier.
 * Listeners should persist the identifier to the appropriate subscription record.
 */
final class RedsysMerchantIdentifierReceived
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderId,
        public readonly string $merchantIdentifier,
    ) {}
}
