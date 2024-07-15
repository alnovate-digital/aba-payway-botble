<?php

namespace Botble\Payway\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Botble\Payment\Supports\PaymentHelper;
use Botble\Payway\Http\Requests\CallbackRequest;
use Botble\Payway\Http\Requests\PaymentRequest;
use Botble\Payway\Services\Payway;
use Botble\Payway\Services\PaywayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaywayController extends BaseController
{
    public function getCallback(CallbackRequest $request, PaymentInterface $paymentRepository, Payway $payway): void
    {
        // Get the pushback from the request
        $tran_id = $request->input('tran_id');
        $auth_code = $request->input('apv');
        $payment_status = $request->input('status');

        if (! $tran_id && $auth_code && $payment_status == 0) {
            abort(404);
        }

        $verifyData = $payway->checkTransaction($tran_id);
        $data = json_decode($verifyData->getContent(), true);
        Log::info('Payment data from PayWay when verify', ['payment' => $data]);

        if (! $data) {
            return;
        }

        $payment = $paymentRepository->getFirstBy([
            'charge_id' => $tran_id,
        ]);
        Log::info('Payment data from Repository', ['payment' => $payment]);

        if (! $payment) {
            return;
        }

        $status = match ($data['payment_status']) {
            'APPROVED' => PaymentStatusEnum::COMPLETED,
            'DECLINED' => PaymentStatusEnum::FAILED,
            default => PaymentStatusEnum::PENDING,
        };

        if (! in_array($payment->status, [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::FAILED, $status])) {
            $payment->status = $status;
            $payment->save();
        }
    }

    public function getSuccess(PaymentRequest $request, Payway $payway, BaseHttpResponse $response)
    {
        $tran_id = $request->input('tran_id');
        Log::info('Tran ID in getSuccess', ['tran_id' => $tran_id]);

        if (! $tran_id) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage(__('Transaction ID not provided.'));
        }

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
        Log::info('Transaction list from PayWay', ['payment' => $transaction]);

        if (! $transaction) {
            $errorMessage = __('Checkout failed with PayWay status: ') . $transaction['status'];

            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage($errorMessage);
        }

        $status = match ($transaction['status']) {
            'APPROVED' => PaymentStatusEnum::COMPLETED,
            'DECLINED' => PaymentStatusEnum::FAILED,
            default => PaymentStatusEnum::PENDING,
        };

        if ($status === PaymentStatusEnum::FAILED) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL());
        }

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'order_id' => $request->input('order_id'),
            'amount' => $transaction['payment_amount'],
            'charge_id' => $tran_id,
            'payment_channel' => $transaction['payment_type'],
            'status' => $status,
            'customer_id' => $request->input('customer_id'),
            'customer_type' => $request->input('customer_type'),
            'payment_type' => 'direct',
        ], $request);

        return $response
        ->setNextUrl(PaymentHelper::getRedirectURL())
        ->setMessage(__('Checkout successfully!'));
    }

    public function generateHash(Request $request)
    {
        $dataForHash = [
            'req_time' => $request->input('req_time'),
            'merchant_id' => $request->input('merchant_id'),
            'tran_id' => $request->input('tran_id'),
            'amount' => $request->input('amount'),
            'items' => $request->input('items'),
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'payment_option' => $request->input('payment_option'),
            'return_url' => $request->input('return_url'),
            'cancel_url' => $request->input('cancel_url'),
            'continue_success_url' => $request->input('continue_success_url'),
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
}
