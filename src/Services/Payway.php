<?php

namespace Alnovate\Payway\Services;

use Illuminate\Support\Facades\Http;

class Payway
{
    protected string $merchantId;

    protected string $apiKey;

    protected string $purchaseUrl;

    protected string $reconcileUrl;

    protected string $transactionListUrl;

    protected array $data = [];

    public function __construct()
    {
        $this->merchantId = get_payment_setting('merchant_id', PAYWAY_PAYMENT_METHOD_NAME);
        $this->apiKey = get_payment_setting('api_key', PAYWAY_PAYMENT_METHOD_NAME);
        $this->purchaseUrl = get_payment_setting('purchase_url', PAYWAY_PAYMENT_METHOD_NAME);
        $this->reconcileUrl = get_payment_setting('reconcile_url', PAYWAY_PAYMENT_METHOD_NAME);
        $this->transactionListUrl = get_payment_setting('transaction_list_url', PAYWAY_PAYMENT_METHOD_NAME);
    }

    public function withPaymentData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getPaymentForm(): void
    {
        echo view('plugins/payway::form', [
            'data' => $this->data,
            'action' => $this->getPurchaseUrl(),
        ]);

        exit();
    }

    public function checkTransaction(string $tran_id)
    {
        $merchant_id = $this->getMerchantId();
        $api_key = $this->getApiKey();
        $reconcileUrl = $this->getReconcileUrl();

        // Generate the hash
        $req_time = date('YmdHis');
        $hash = base64_encode(hash_hmac('sha512', "{$req_time}{$merchant_id}{$tran_id}", $api_key, true));

        // Prepare the request data
        $requestData = [
            'req_time' => $req_time,
            'merchant_id' => $merchant_id,
            'tran_id' => $tran_id,
            'hash' => $hash,
        ];

        // Make the API request
        $response = Http::post($reconcileUrl, $requestData);

        // Handle the response
        if ($response->successful()) {
            $responseData = $response->json();

            // Process the response data as needed
            return response()
                ->json($responseData);
        }

        return response()
            ->json(['error' => 'Error checking transaction'], 500);
    }

    public function getTransactionList(string $param)
    {
        $merchant_id = $this->getMerchantId();
        $api_key = $this->getApiKey();
        $transactionListUrl = $this->getTransactionListUrl();

        // Generate the hash
        $req_time = date('YmdHis');
        $hash = base64_encode(hash_hmac('sha512', "{$req_time}{$merchant_id}", $api_key, true));

        // Prepare the request data
        $requestData = [
            'req_time' => $req_time,
            'merchant_id' => $merchant_id,
            'hash' => $hash,
        ];

        // Make the API request
        $response = Http::post($transactionListUrl, $requestData);

        // Handle the response
        if ($response->successful()) {
            $responseData = $response->json();

            // Process the response data as needed
            return response()
                ->json($responseData);
        }

        return response()
            ->json(['error' => 'Failed to get the transaction list'], 500);
    }

    public function getTransactionId(): string
    {
        $this->transactionId = (string) random_int(10000000, 99999999);

        return $this->transactionId;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getPurchaseUrl(): string
    {
        return $this->purchaseUrl;
    }

    public function getReconcileUrl(): string
    {
        return $this->reconcileUrl;
    }

    public function getTransactionListUrl(): string
    {
        return $this->transactionListUrl;
    }
}
