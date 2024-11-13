<!DOCTYPE html>
<html lang="en">

<head>
    <title>Checkout with ABA PayWay</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="author" content="PayWay">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
</head>

<body>
<div id="aba_main_modal" class="aba-modal">
    <div class="aba-modal-content">
        <form method="POST" target="aba_webservice" action="{{ $action }}" id="aba_merchant_request">
            <input type="hidden" name="hash" value="" id="hash"/>
            <input type="hidden" name="req_time" value="{{ $data['req_time'] }}"/>
            <input type="hidden" name="merchant_id" value="{{ $data['merchant_id'] }}"/>
            <input type="hidden" name="tran_id" value="{{ $data['tran_id'] }}"/>
            <input type="hidden" name="amount" value="{{ $data['amount'] }}"/>
            <input type="hidden" name="items" value="{{ $data['items'] }}"/>
            <input type="hidden" name="shipping" value="{{ $data['shipping_fee'] }}"/>
            <input type="hidden" name="firstname" value="{{ $data['first_name'] }}"/>
            <input type="hidden" name="lastname" value="{{ $data['last_name'] }}"/>
            <input type="hidden" name="email" value="{{ $data['email'] }}"/>
            <input type="hidden" name="phone" value="{{ $data['phone'] }}"/>
            <input type="hidden" name="payment_option" value="" id="hidden_payment_option"/>   
            <input type="hidden" name="return_url" value="{{ $data['return_url'] }}"/>
            <input type="hidden" name="cancel_url" value="{{ $data['cancel_url'] }}"/>
            <input type="hidden" name="continue_success_url" value="{{ $data['continue_success_url'] }}"/>
            <input type="hidden" name="return_params" value="{{ $data['return_params'] }}" id="return_params"/>
        </form>
    </div>
</div>
<!-- Overlay div, initially hidden -->
<div id="loadingOverlay" class="loadingOverlay">
    <div class="loading-overlay-icon"> <img src="https://checkout.payway.com.kh/images/loading.svg"></div>
</div>

        <div class="payment_form">
            <div class="payment_option" data-bs-dismiss="modal">
                <input type="radio" name="payment_option_radio" class="form-check-input" value="abapay" id="abapay">
                <label class="paymentOption" for="abapay">
                    <img class="cardType" src="{{ asset('vendor/core/plugins/payway/images/ic_KHQR_1x.png') }}">
                    <span class="cardName">
                        <strong><span class="titleCard">ABA KHQR</span><br/></strong>
                        <span class="detailCard small text-secondary">Scan to pay with any banking app</span>
                    </span>
                </label>
            </div>
            <div class="payment_option" data-bs-dismiss="modal">
                <input type="radio" name="payment_option_radio" class="form-check-input" value="cards" id="cards">
                <label class="paymentOption" for="cards">
                    <img class="cardType" src="{{ asset('vendor/core/plugins/payway/images/ic_generic_1x.png') }}">
                    <span class="cardName">
                        <strong><span class="titleCard">Credit/Debit Card</span><br/></strong>
                        <span class="detailCard small text-secondary">Visa, Mastercard, UnionPay, JCB</span>
                    </span>
                </label>
            </div>
            <div class="payment_option disabled-checkout-button" data-bs-dismiss="modal">
                <input type="radio" name="payment_option_radio" class="form-check-input" value="alipay" id="alipay">
                <label class="paymentOption" for="alipay">
                    <img class="cardType" src="{{ asset('vendor/core/plugins/payway/images/ic_AliPay.png') }}">
                    <span class="cardName">
                        <strong><span class="titleCard">AliPay</span><br/></strong>
                        <span class="detailCard small text-secondary">Scan to pay with AliPay</span>
                    </span>
                </label>
            </div>
            <div class="payment_option disabled-checkout-button" data-bs-dismiss="modal">
                <input type="radio" name="payment_option_radio" class="form-check-input" value="wechat" id="wechat">
                <label class="paymentOption" for="wechat">
                    <img class="cardType" src="{{ asset('vendor/core/plugins/payway/images/ic_WeChat.png') }}">
                    <span class="cardName">
                        <strong><span class="titleCard">WeChat</span><br/></strong>
                        <span class="detailCard small text-secondary">Scan to pay with WeChat</span>
                    </span>
                </label>
            </div>
        </div>

<script src="https://checkout.payway.com.kh/plugins/checkout2-0.js"></script>

<script>

    $(document).ready(function(){

        // Show the loading overlay on top of the payment form
        $('#loadingOverlay').css({
            'display': 'flex',
            'position': 'absolute',
            'width': $('.payment_form').outerWidth(),
            'height': $('.payment_form').outerHeight()
        });

        // After x seconds (1sec = 1000), hide the loading overlay
        setTimeout(function() {
            $('#loadingOverlay').hide();
        }, 3000);

        $('.payment_option').change(async function(){
            var selectedPaymentOption = $('input[name="payment_option_radio"]:checked').val();
            $('#hidden_payment_option').val(selectedPaymentOption);

            // Collect all necessary values
            var formData = {
                req_time: $('input[name="req_time"]').val(),
                merchant_id: $('input[name="merchant_id"]').val(),
                tran_id: $('input[name="tran_id"]').val(),
                amount: {{ $data['amount'] }},
                items: $('input[name="items"]').val(),
                shipping_fee: $('input[name="shipping"]').val(),
                firstname: $('input[name="firstname"]').val(),
                lastname: $('input[name="lastname"]').val(),
                email: $('input[name="email"]').val(),
                phone: $('input[name="phone"]').val(),
                payment_option: selectedPaymentOption,
                return_url: $('input[name="return_url"]').val(),
                cancel_url: $('input[name="cancel_url"]').val(),
                continue_success_url: $('input[name="continue_success_url"]').val(),
                return_params: $('input[name="return_params"]').val(),
            };

            console.log(formData.amount); // Check the value here

            var gen_time = Date.now(); // Get the current timestamp in milliseconds
            
            try {
            // Call the server-side endpoint to generate the hash synchronously
            const response = await $.ajax({
                url: '/payway/generate/hash?nocache=${gen_time}', // Your endpoint to generate the hash
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), // CSRF token as required by Laravel
                    'Cache-Control': 'no-cache, no-store, must-revalidate', // Add this line for cache control
                },
            });
            
            // Update the hidden input with the hash
            $('#hash').val(response.hash);

            // Proceed with checkout
            AbaPayway.checkout();
            
            } catch (error) {
            console.error('Error:', error);
            }

        });
    });

</script>

</body>
</html>