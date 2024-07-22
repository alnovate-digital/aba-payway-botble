<?php

namespace Alnovate\Payway\Services;

use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Alnovate\Payway\Services\Payway;
use Exception;
use Illuminate\Http\Request;

abstract class PaywayPaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected string $paymentCurrency;

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

            $transactionList = (new Payway())->getTransactionList($param);
            $response = json_decode($transactionList->getContent(), true);
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
