@if ($payment)
    <br>
    <p>{{ trans('plugins/payment::payment.payment_id') }}: {{ $payment->id }}</p>
    <p>{{ trans('plugins/payment::payment.amount') }}: {{ $payment->amount / 100 }} {{ $payment->currency }}</p>
    <p>{{ trans('plugins/payment::payment.email') }}: {{ $payment->email }}</p>
    <p>{{ trans('plugins/payment::payment.phone') }}: {{ $payment->contact }}</p>
    <p>{{ trans('core/base::tables.created_at') }}: {{ BaseHelper::formatDate($payment->created_at) }}</p>
    <hr>

    @include('plugins/payment::partials.view-payment-source')
@endif
