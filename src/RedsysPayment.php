<?php

namespace NumaxLab\Lunar\Redsys;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Contracts\Cart;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\Models\Order;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;
use NumaxLab\Lunar\Redsys\Responses\RedirectToPaymentGateway;
use Sermepa\Tpv\Tpv;

class RedsysPayment extends AbstractPayment
{
    public const string DRIVER_NAME = 'redsys';

    protected Tpv $redsys {
        get {
            return $this->redsys;
        }
    }

    public function __construct()
    {
        $this->redsys = new Tpv();
    }

    public function redirect(): ?string
    {
        return Blade::render($this->redsys->executeRedirection(true));
    }

    public function authorize(): ?PaymentAuthorize
    {
        if (! $this->order) {
            $this->order = $this->cart->draftOrder()->first();

            if (! $this->order) {
                $this->order = $this->cart->createOrder();
            }
        }

        if ($this->order->isPlaced()) {
            $failure = new PaymentAuthorize(
                success: false,
                message: 'This order has already been placed',
                orderId: $this->order->id,
                paymentType: static::DRIVER_NAME,
            );

            PaymentAttemptEvent::dispatch($failure);

            return $failure;
        }

        $transaction = Transaction::create([
            'type' => 'intent',
            'order_id' => $this->order->id,
            'success' => 1,
            'driver' => static::DRIVER_NAME,
            'amount' => $this->order->total,
            'reference' => $this->order->reference,
            'card_type' => $this->data['method'],
            'status' => 'awaiting payment',
            'meta' => [
                'config_key' => $this->data['config_key'],
            ],
        ]);

        $reference = str_pad($transaction->id, 12, '0', STR_PAD_LEFT);

        $transaction->update([
            'reference' => $reference,
        ]);

        $this->redsys->setAmount($this->order->total->decimal);
        $this->redsys->setOrder($reference);
        $this->redsys->setMerchantcode(config("services.redsys.{$this->data['config_key']}.merchant_code"));
        $this->redsys->setCurrency(config("services.redsys.{$this->data['config_key']}.currency"));
        $this->redsys->setTransactiontype('0');
        $this->redsys->setTerminal(config("services.redsys.{$this->data['config_key']}.terminal"));
        $this->redsys->setMethod($this->data['method']);
        $this->redsys->setNotification(route('lunar.redsys.webhook'));
        $this->redsys->setUrlOk($this->data['url_ok']);
        $this->redsys->setUrlKo($this->data['url_ko']);
        $this->redsys->setVersion('HMAC_SHA256_V1');
        $this->redsys->setTradeName(config("services.redsys.{$this->data['config_key']}.trade_name"));
        $this->redsys->setTitular(config("services.redsys.{$this->data['config_key']}.owner"));
        $this->redsys->setProductDescription($this->data['product_description']);
        $this->redsys->setEnvironment(config("services.redsys.{$this->data['config_key']}.environment"));

        $this->redsys->setMerchantSignature(
            $this->redsys->generateMerchantSignature(config("services.redsys.{$this->data['config_key']}.key")),
        );

        $response = new RedirectToPaymentGateway(
            success: true,
            message: 'Redirecting to payment gateway',
            orderId: $this->order->id,
            paymentType: static::DRIVER_NAME,
        );

        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    public function refund(TransactionContract $transaction, int $amount, $notes = null): PaymentRefund
    {
        return new PaymentRefund(success: true);
    }

    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        $parameters = $this->getMerchantParameters();
        $response = (int) $parameters['Ds_Response'];

        $this->order(Order::findOrFail($transaction->order_id));

        $key = config("services.redsys.{$transaction->meta['config_key']}.key");

        if (! $this->redsys->check($key, $this->data['request']) || $response > 99) {
            $transaction->update([
                'status' => $parameters['Ds_Response'],
                'notes' => 'Error en la validación de la firma o el pago fue rechazado',
            ]);

            $this->clearCart($this->order->cart);

            return new PaymentCapture(success: false);
        }

        Log::debug(json_encode($parameters));

        $orderMeta = array_merge(
            (array) $this->order->meta,
            $this->data['meta'] ?? [],
        );

        $status = $this->data['authorized'] ?? null;

        DB::beginTransaction();

        $this->order->update([
            'status' => $status ?? ($this->config['authorized'] ?? null),
            'meta' => $orderMeta,
            'placed_at' => now(),
        ]);

        $transaction->update([
            'captured_at' => now(),
        ]);

        $cartType = '--';

        if (array_key_exists('Ds_Card_Brand', $parameters)) {
            $cartType = $parameters['Ds_Card_Brand'];
            if (array_key_exists('Ds_Card_Type', $parameters)) {
                $cartType .= ' - '.$parameters['Ds_Card_Type'];
            }
        } else {
            if (array_key_exists('Ds_Bizum_MobileNumber', $parameters)) {
                $cartType = 'Bizum';
            }
        }

        Transaction::create([
            'success' => true,
            'type' => 'capture',
            'driver' => static::DRIVER_NAME,
            'order_id' => $this->order->id,
            'amount' => $amount,
            'reference' => $transaction->reference,
            'status' => $parameters['Ds_Response'],
            'card_type' => $cartType,
            'parent_transaction_id' => $transaction->id,
        ]);

        DB::commit();

        $this->clearCart($this->order->cart);

        return new PaymentCapture(success: true);
    }

    public function getMerchantParameters(): array
    {
        return $this->redsys->getMerchantParameters($this->data['Ds_MerchantParameters']);
    }

    private function clearCart(?Cart $cart): void
    {
        if ($cart) {
            $cart->clear();
            $cart->delete();
        }
    }
}
