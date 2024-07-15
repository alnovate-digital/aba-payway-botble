<?php

namespace Botble\Payway\Services;

use Botble\Payway\Services\Payway;
use Botble\Payment\Models\Payment;
use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

abstract class PaywayPaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected string $paymentCurrency;

    protected Client $client;

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');
    }

    public function setCurrency($currency)
    {
        $this->paymentCurrency = $currency;
        return $this;
    }

    public function getCurrency()
    {
        return $this->paymentCurrency;
    }

    public function execute(Request $request)
    {
        try {
            return $this->makePayment($request);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);
            return false;
        }
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);
}