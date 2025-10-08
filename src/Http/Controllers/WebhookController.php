<?php

namespace NumaxLab\Lunar\Redsys\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lunar\Facades\Payments;
use Lunar\Models\Transaction;

class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $paymentDriver = Payments::driver('card')
            ->withData([
                'Ds_MerchantParameters' => $request->input('Ds_MerchantParameters'),
                'request' => $request->all(),
            ]);

        $parameters = $paymentDriver->getMerchantParameters();

        $transaction = Transaction::findOrFail((int) ltrim($parameters['Ds_Order'], '0'));

        $paymentDriver->capture($transaction, $transaction->amount);
    }
}
