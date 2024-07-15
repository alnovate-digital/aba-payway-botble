<?php

namespace Botble\Payway\Services;

use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Botble\Payway\Services\Payway;
use Exception;
use Illuminate\Http\Request;

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

    public function getPaymentDetails($payment)
    {
        try {
            $param = (new Payway())->getMerchantId();

            $response = (new Payway())->getTransactionList($param);
            if ($response['status']) {
                return collect($response['data'])->firstWhere('transaction_id', $payment->charge_id);
            }
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return false;
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
