<?php

namespace Alnovate\Payway\Providers;

use Botble\Base\Facades\Html;
use Botble\Ecommerce\Models\Currency;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Supports\PaymentHelper;
use Alnovate\Payway\Forms\PaywayPaymentMethodForm;
use Alnovate\Payway\Services\Payway;
use Alnovate\Payway\Services\PaywayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
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

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['PAYWAY'] = PAYWAY_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 24, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == PAYWAY_PAYMENT_METHOD_NAME) {
                $value = 'PayWay by ABA Bank';
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
            if ($payment->payment_channel == PAYWAY_PAYMENT_METHOD_NAME) {
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

        $supportedCurrencies = (new PaywayPaymentService())->supportedCurrencyCodes();

        if (! in_array($data['currency'], $supportedCurrencies)) {
            $data['error'] = true;
            $data['message'] = __(
                ":name doesn't support :currency. List of currencies supported by :name: :currencies.",
                [
                    'name' => 'PayWay by ABA Bank',
                    'currency' => $data['currency'],
                    'currencies' => implode(', ', $supportedCurrencies),
                ]
            );

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
            $orderAddress = $paymentData['address'];
            $name = explode(' ', $orderAddress['name']);
            $firstName = $name[0];
            $lastName = $name[1];
            $email = $orderAddress['email'];
            $phone = $orderAddress['phone'];
            $amount = $paymentData['amount'];
            $items = [
                'items' => [
                    'name' => (string) $paymentData['products'][0]['name'],
                    'quantity' => (int) $paymentData['products'][0]['qty'],
                    'price' => number_format((float) $paymentData['products'][0]['price'], 2),
                ],
            ];
            $hashedItems = base64_encode(json_encode($items));
            $callback_url = route('payway.payment.callback');
            $return_url = base64_encode($callback_url);
            $cancel_url = $paymentHelper->getCancelURL();
            $continue_success_url = route('payway.payment.success', [
                'tran_id' => $transactionId,
                'order_id' => $paymentData['order_id'],
                'customer_id' => $paymentData['customer_id'],
                'customer_type' => $paymentData['customer_type'],
                'token' => $paymentData['checkout_token'],
            ]);

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
                'return_url' => $return_url,
                'cancel_url' => $cancel_url,
                'continue_success_url' => $continue_success_url,
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
