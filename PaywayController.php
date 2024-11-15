<?php

namespace Alnovate\Payway\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper;
use Alnovate\Payway\Http\Requests\CallbackRequest;
use Alnovate\Payway\Http\Requests\PaymentRequest;
use Alnovate\Payway\Services\Payway;
use Alnovate\Payway\Services\PaywayPaymentService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PaywayController extends BaseController
{
    public function generateHash(Request $request)
    {
        $paymentOption = $request->input('payment_option');
        Session::put('payment_option', $paymentOption);

        $amount = $request->input('amount');
        $paymentAmount = number_format((float)$amount, 2, '.', '');

        $dataForHash = [
            'req_time' => $request->input('req_time'),
            'merchant_id' => $request->input('merchant_id'),
            'tran_id' => $request->input('tran_id'),
            'amount' => $paymentAmount,
            'items' => $request->input('items'),
            'shipping_fee' => $request->input('shipping_fee'),
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'payment_option' => $paymentOption,
            'return_url' => $request->input('return_url'),
            'cancel_url' => $request->input('cancel_url'),
            'continue_success_url' => $request->input('continue_success_url'),
            'return_params' => $request->input('return_params'),
        ];

        // Concatenate the required fields into a single string
        $hashStr = implode('', $dataForHash);

        // Generate the hash using the concatenated string
        $payway = new Payway();
        $hash = base64_encode(hash_hmac('sha512', $hashStr, $payway->getApiKey(), true));

        return response()->json([
            'hash' => $hash,
        ]);
    }

    public function getCallback(CallbackRequest $request, Payway $payway)
    {
        // Get the pushback from the request
        $tran_id = $request->input('tran_id');
        $auth_code = $request->input('apv');
        $payment_status = $request->input('status');
        
        if (!$tran_id || !$auth_code || $payment_status != 0) {
            return $response
                ->setError()
                ->setMessage(__('Invalid Data!'));
        }
        
        // Check order status in order table against the transaction id or order id.
        $transaction = Payment::query()->where('charge_id', $tran_id)
            ->select(['charge_id', 'status'])->first();

        if (! $transaction) {
            return $response
                ->setError()
                ->setMessage(__('Invalid Transaction!'));
        }

        if ($transaction->status == PaymentStatusEnum::PENDING) {
            $verifyData = $payway->checkTransaction($tran_id);
            $validation = json_decode($verifyData->getContent(), true);

            if ($validation && $validation['status']['code'] === '00') {
                // Validate and update order status in order table as Completed
                Payment::query()
                    ->where('charge_id', $tran_id)
                    ->update(['status' => PaymentStatusEnum::COMPLETED]);

                return $response
                    ->setError()
                    ->setMessage(__('Transaction is successfully completed!'));
            }
            // If transaction validation failed, update order status as Failed
            Payment::query()
                ->where('charge_id', $tran_id)
                ->update(['status' => PaymentStatusEnum::FAILED]);

            return $response
                ->setError()
                ->setMessage(__('Validation Failed!'));
        }

        // That means Order status already updated. No need to update database.
        return $response
            ->setError()
            ->setMessage(__('Transaction is already successfully completed!'));
    }

    public function getSuccess(PaymentRequest $request, Payway $payway, BaseHttpResponse $response)
    {
        $param = $payway->getMerchantId();
        $transactionList = $payway->getTransactionList($param);
        $transactions = json_decode($transactionList->getContent(), true);

        // Check if 'data' key exists and is not empty
        if (!isset($transactions['data']) || empty($transactions['data'])) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage(__('No transactions found.'));
        }
        
        // Get the first transaction
        $transaction = $transactions['data'][0];

        if (! $transaction) {
            $errorMessage = __('Checkout failed with PayWay status: ') . $transaction['payment_status_code'];

            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage($errorMessage);
        }

        $status = match ($transaction['payment_status']) {
            'APPROVED' => PaymentStatusEnum::COMPLETED,
            'DECLINED' => PaymentStatusEnum::FAILED,
            default => PaymentStatusEnum::PENDING,
        };

        if ($status === PaymentStatusEnum::FAILED) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage(__('Payment status returned fail.'));
        }

        $paymentOption = Session::get('payment_option');

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'order_id' => $request->input('order_id'),
            'amount' => $transaction['payment_amount'],
            'charge_id' => $request->input('tran_id'),
            'payment_channel' => $paymentOption,
            'status' => $status,
            'customer_id' => $request->input('customer_id'),
            'customer_type' => $request->input('customer_type'),
            'payment_type' => 'direct',
        ], $request);

        Session::forget('payment_option');

        return $response
        ->setNextUrl(PaymentHelper::getRedirectURL())
        ->setMessage(__('Checkout successfully!'));
    }
}
