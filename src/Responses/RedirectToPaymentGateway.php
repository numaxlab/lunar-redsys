<?php

namespace NumaxLab\Lunar\Redsys\Responses;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;

class RedirectToPaymentGateway extends PaymentAuthorize
{
    public function __construct(
        public bool $success = false,
        public ?string $message = null,
        public ?int $orderId = null,
        public ?string $paymentType = null,
    ) {
        parent::__construct($success, $message, $orderId, $paymentType);
    }
}
