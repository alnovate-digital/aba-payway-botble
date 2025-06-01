<?php

namespace Alnovate\Payway\Providers;

use Botble\Base\Facades\Html;
use Botble\Ecommerce\Models\ShippingRule;
use Botble\Ecommerce\Models\Currency;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Supports\PaymentHelper;
use Alnovate\Payway\Forms\PaywayPaymentMethodForm;
use Alnovate\Payway\Services\Payway;
use Alnovate\Payway\Services\PaywayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerPaywayMethod'], 19, 2);

        $this->app->booted(function () {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithPayway'], 19, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 99, 1);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($value, $class) {
            if ($class == PaymentMethodEnum::class) {
                $value['PAYWAY'] = PAYWAY_PAYMENT_METHOD_NAME;
            }
            if ($class == PaymentMethodEnum::class) {
                $value['ABAPAY'] = 'abapay';
                $value['KHQR'] = 'bakong';
                $value['CARDS'] = 'cards';
                $value['ALIPAY'] = 'alipay';
                $value['WECHAT'] = 'wechat';
            }

            return $value;
        }, 24, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == PAYWAY_PAYMENT_METHOD_NAME) {
                $value = 'ABA PayWay';
            }

            if ($class == PaymentMethodEnum::class && in_array($value, ['abapay', 'bakong', 'cards', 'alipay', 'wechat'])) {
                switch ($value) {
                    case 'abapay':
                        $value = 'ABA KHQR';
                        break;
                    case 'bakong':
                        $value = 'KHQR';
                        break;
                    case 'cards':
                        $value = 'Credit/Debit Card';
                        break;
                    case 'alipay':
                        $value = 'AliPay';
                        break;
                    case 'wechat':
                        $value = 'WeChat';
                        break;
                }
            }

            return $value;
        }, 24, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == PAYWAY_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )
                    ->toHtml();
            }

            return $value;
        }, 24, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == PAYWAY_PAYMENT_METHOD_NAME) {
                $data = PaywayPaymentService::class;
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if (
                $payment->payment_channel == 'abapay' || 
                $payment->payment_channel == 'bakong' || 
                $payment->payment_channel == 'cards' || 
                $payment->payment_channel == 'alipay' || 
                $payment->payment_channel == 'wechat'
            ) {
                $paymentService = (new PaywayPaymentService());
                $paymentDetail = $paymentService->getPaymentDetails($payment);
                if ($paymentDetail) {
                    $data = view(
                        'plugins/payway::detail',
                        ['payment' => $paymentDetail, 'paymentModel' => $payment]
                    )->render();
                }
            }
        
            return $data;
        }, 20, 2);        
    }

    public function addPaymentSettings(?string $settings): string
    {
        return $settings . PaywayPaymentMethodForm::create()->renderForm();
    }

    public function registerPaywayMethod(?string $html, array $data): ?string
    {
        if (! get_payment_setting('status', PAYWAY_PAYMENT_METHOD_NAME)) {
            return $html;
        }

        $data = [
            ...$data,
            'paymentId' => PAYWAY_PAYMENT_METHOD_NAME,
            'paymentDisplayName' => 'ABA PayWay',
            'supportedCurrencies' => (new PaywayPaymentService())->supportedCurrencyCodes(),
        ];

        PaymentMethods::method(PAYWAY_PAYMENT_METHOD_NAME, [
            'html' => view('plugins/payway::methods', $data)->render(),
        ]);

        return $html;
    }

    public function checkoutWithPayway(array $data, Request $request): array
    {
        if ($data['type'] !== PAYWAY_PAYMENT_METHOD_NAME) {
            return $data;
        }

        $currentCurrency = get_application_currency();

        $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

        if (strtoupper($currentCurrency->title) !== 'USD') {
            $supportedCurrency = Currency::query()->where('title', 'USD')->first();

            if ($supportedCurrency) {
                $paymentData['currency'] = strtoupper($supportedCurrency->title);
                if ($currentCurrency->is_default) {
                    $paymentData['amount'] = $paymentData['amount'] * $supportedCurrency->exchange_rate;
                } else {
                    $paymentData['amount'] = format_price(
                        $paymentData['amount'] / $currentCurrency->exchange_rate,
                        $currentCurrency,
                        true
                    );
                }
            }
        }

        try {
            $payway = new Payway();
            $paymentHelper = new PaymentHelper();

            $merchant_id = $payway->getMerchantId();
            $api_key = $payway->getApiKey();
            $req_time = date('YmdHis');
            $transactionId = $payway->getTransactionId();

            $firstName = Str::of($paymentData['address']['name'])->before(' ')->toString();
            $lastName = Str::of($paymentData['address']['name'])->after(' ')->toString();
            $email = $paymentData['address']['email'];
            $phone = $paymentData['address']['phone'];
            $items = [];
            foreach ($paymentData['products'] as $product) {
                $items[] = [
                    'name' => (string) $product['name'],
                    'quantity' => (int) $product['qty'],
                    'price' => number_format((float) $product['price'], 2),
                ];
            }
            $hashedItems = base64_encode(json_encode($items));
            $shipping_fee = $paymentData['shipping_amount'];
            $payment_amount = $paymentData['amount'] - $shipping_fee;  
            $amount = number_format((float) $payment_amount, 2, '.', '');
            $callback_url = route('payway.payment.callback', [
                'tran_id' => $transactionId,
                'order_id' => $paymentData['order_id'],
                'customer_id' => $paymentData['customer_id'],
                'customer_type' => $paymentData['customer_type'],
                'token' => $paymentData['checkout_token'],
            ]);
            $return_url = base64_encode($callback_url);
            $cancel_url = $paymentHelper->getCancelURL();
            $continue_success_url = route('payway.payment.success', [
                'tran_id' => $transactionId,
                'order_id' => $paymentData['order_id'],
                'customer_id' => $paymentData['customer_id'],
                'customer_type' => $paymentData['customer_type'],
                'token' => $paymentData['checkout_token'],
            ]);
            $return_params = json_encode($paymentData['description']);

            $dataForPayment = [
                'merchant_id' => $merchant_id,
                'api_key' => $api_key,
                'req_time' => $req_time,
                'tran_id' => $transactionId,
                'amount' => $amount,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'items' => $hashedItems,
                'shipping_fee' => $shipping_fee,
                'return_url' => $return_url,
                'cancel_url' => $cancel_url,
                'continue_success_url' => $continue_success_url,
                'return_params' => $return_params,
            ];

            $payway->withPaymentData($dataForPayment);
            $payway->getPaymentForm();
        } catch (Throwable $exception) {
            $data['error'] = true;
            $data['message'] = json_encode($exception->getMessage());
        }

        return $data;
    }
}
