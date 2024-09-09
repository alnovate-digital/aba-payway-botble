<?php

namespace Alnovate\Payway\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Forms\PaymentMethodForm;

class PaywayPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(PAYWAY_PAYMENT_METHOD_NAME)
            ->paymentName('ABA PayWay')
            ->paymentDescription(__('Customer can buy product and pay directly using ABA Pay, KHQR and Cards via :name', ['name' => 'ABA PayWay']))
            ->paymentLogo(url('vendor/core/plugins/payway/images/payway.png'))
            ->paymentUrl('https://www.payway.com.kh/')
            ->paymentInstructions(view('plugins/payway::instructions')->render())
            ->add(
                sprintf('payment_%s_merchant_id', PAYWAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Merchant ID'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('merchant_id', PAYWAY_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                sprintf('payment_%s_api_key', PAYWAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('API Key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('api_key', PAYWAY_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                sprintf('payment_%s_purchase_url', PAYWAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Purchase API URL'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('purchase_url', PAYWAY_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                sprintf('payment_%s_reconcile_url', PAYWAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Reconcile API URL'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('reconcile_url', PAYWAY_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                sprintf('payment_%s_transaction_list_url', PAYWAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Transaction List API URL'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('transaction_list_url', PAYWAY_PAYMENT_METHOD_NAME))
                    ->toArray()
            );
    }
}
