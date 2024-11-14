<?php

namespace Alnovate\Payway\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PaywayServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (! is_plugin_active('payment')) {
            return;
        }

        $this->setNamespace('plugins/payway')
            ->loadHelpers()
            ->loadRoutes()
            ->loadAndPublishViews()
            ->publishAssets();

        $this->app->register(HookServiceProvider::class);

        if ($this->isActivePlugin(PAYWAY_PAYMENT_METHOD_NAME)) {
            Theme::asset()
                ->usePath(false)
                ->add(PAYWAY_PAYMENT_METHOD_NAME, asset('vendor/core/plugins/payway/images/ic_ABA PAY_1x.png'))
                ->add(PAYWAY_PAYMENT_METHOD_NAME, asset('vendor/core/plugins/payway/images/ic_KHQR_1x.png'))
                ->add(PAYWAY_PAYMENT_METHOD_NAME, asset('vendor/core/plugins/payway/images/ic_generic_1x.png'))
                ->add(PAYWAY_PAYMENT_METHOD_NAME, asset('vendor/core/plugins/payway/images/ic_AliPay.png'))
                ->add(PAYWAY_PAYMENT_METHOD_NAME, asset('vendor/core/plugins/payway/images/ic_WeChat.png'))
                ->add(PAYWAY_PAYMENT_METHOD_NAME, asset('vendor/core/plugins/payway/images/ic_4Cards_2x.png'));
        }

        // Check if the styles have already been pushed
        View::composer('*', function ($view) {
            static $stylePushed = false;

            if (!$stylePushed) {
                $view->getFactory()->startPush('header');
                echo '<style>
                    select option[value="payway"],
                    option[value="bakong"] {
                        display: none;
                    }
                </style>';
                $view->getFactory()->stopPush();
                $stylePushed = true;
            }
        });
    }

    private function getValue(array $haystack, $needle): mixed
    {
        return collect($haystack)
            ->first(function ($value) use ($needle) {
                if (is_scalar($value) && $value === $needle) {
                    return true;
                }

                if (is_array($value) && $this->getValue($value, $needle)) {
                    return true;
                }

                return is_object($value) && (string) $value === (string) $needle;
            });
    }

    private function isActivePlugin(string $plugin): bool
    {
        return $this->getValue(get_active_plugins(), $plugin) === $plugin;
    }
}