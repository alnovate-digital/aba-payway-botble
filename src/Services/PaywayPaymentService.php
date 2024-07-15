<?php

namespace Botble\Payway\Services;

use Botble\Payway\Services\PaywayPaymentAbstract;
use Illuminate\Http\Request;

class PaywayPaymentService extends PaywayPaymentAbstract
{
    public function supportedCurrencyCodes(): array
    {
        return [
            'USD',
            'KHR',
        ];
    }

    public function makePayment(Request $request)
    {
    }

    public function afterMakePayment(Request $request)
    {
    }
}
