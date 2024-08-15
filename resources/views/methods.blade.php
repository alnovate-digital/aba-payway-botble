@if (get_payment_setting('status', PAYWAY_PAYMENT_METHOD_NAME) == 1)
    <x-plugins-payment::payment-method
        :$selecting
        :name="PAYWAY_PAYMENT_METHOD_NAME"
        :paymentName="$paymentDisplayName"
        :supportedCurrencies="$supportedCurrencies"
    >

    <x-slot name="currencyNotSupportedMessage">
        <p class="mt-1 mb-0">
            {{ __('Learn more') }}:
            {{ Html::link('https://www.payway.com.kh/developers', attributes: ['target' => '_blank', 'rel' => 'nofollow']) }}.
        </p>
    </x-slot>

    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .modal#paymentModal {
            display: block; /* Hidden by default */
            position: fixed; /* Stay in place */
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: auto; /* Could be more or less, depending on screen size */
            min-width: 391px;
            height: auto; /* Value depends on content's size */
            overflow-y: hidden;
        }

        .modal#paymentModal.fade {
            opacity: 1; /* Prevent fading */
            transform: translate(-50%, -50%); /* Keep the centering effect */
        }

        .modal#paymentModal .modal-header {
            border-bottom: none;
        }

        .modal#paymentModal .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 0 1rem 1.5rem 1rem;
        }

        .modal#paymentModal .modal-content {
            border: none;
            border-radius: .8rem;
        }

        .modal#paymentModal .modal-backdrop {
            background-color: #888;
        }

        /* Customize the modal transition */
        .modal#paymentModal.fade-from-bottom .modal-dialog {
            transform: translateY(100%);
            opacity: 0; /* Initially hidden */
            transition: transform 0.6s ease-in-out, opacity 0.6s ease-in-out;
            overflow: hidden;
        }

        .modal#paymentModal.fade-from-bottom.show .modal-dialog {
            transform: translateY(0);
            opacity: 1; /* Fully visible when shown */
        }

            /* CSS for full-width modal on mobile devices */
            @media (max-width: 575px) {
                .modal#paymentModal .modal-dialog {
                    margin: 0;
                    width: 100vw;
                }
                .modal#paymentModal {
                    position: fixed;
                    left: 0;
                    top: auto;
                    transform: none;
                    bottom: 0; /* Align to bottom */
                    overflow: hidden;
                    animation-name: slideUp;
                    animation-duration: 0.3s;
                }
                .modal#paymentModal.fade {
                    opacity: 1;
                    transform: none;
                }
                .modal#paymentModal .modal-content {
                    border: none;
                    border-radius: 1.5rem 1.5em 0 0;
                }
            }

            /* Keyframes for slide-up animation */
            @keyframes slideUp {
                from {
                    bottom: -100%;
                }
                to {
                    bottom: 0;
                }
            }

        button.close{
            border: none;
            background: none;
            line-height: 14px;
            color: #0bbcd4;
        }

        .checkout_button {
            background: #fff;
            border-radius: 10px;
        }

        /* CSS for disabled buttons */
        .disabled-checkout-button {
            pointer-events: none; /* Prevents clicking */
            opacity: 0.5; /* Grays out the button */
        }

        .form-check-input[type=radio] {
            display: none;
        }

        label.paymentOption{
            padding: 10px;
            margin-top: 10px;
            box-shadow: 0px .5px 0px 1px rgb(0 0 0 / 10%);
            display: flex;
            align-items: center;
            cursor: pointer;
            border-radius: 10px;
            position: relative;
        }

        .cardType{
            height: 35px;
        }

        .cardName{
            margin-left: 16px; 
            float: right; 
        }

        .detailCard{
            margin-top: 5px;
        }

        .loadingOverlay{
            display: none; 
            position: absolute; 
            width: 100%; 
            height: 100%; 
            background: rgba(255, 255, 255, 0.7); 
            z-index: 1200; 
            justify-content: center; 
            align-items: center;
        }

        .loadingOverlay .loading-overlay-icon img {
            width: 40px;
            left: 0;
            right: 0;
            position: absolute;
            margin: 0 auto;
            top: 40%;
            -webkit-animation: spin 1s linear infinite;
            animation: spin 1s linear infinite;
        }
    </style>

    <div class="modal fade fade-from-bottom" id="paymentModal" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="paymentModalLabel">Choose way to pay</h5>
              <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">
                    <svg fill-rule="evenodd" viewBox="64 64 896 896" focusable="false" data-icon="close" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M799.86 166.31c.02 0 .04.02.08.06l57.69 57.7c.04.03.05.05.06.08a.12.12 0 010 .06c0 .03-.02.05-.06.09L569.93 512l287.7 287.7c.04.04.05.06.06.09a.12.12 0 010 .07c0 .02-.02.04-.06.08l-57.7 57.69c-.03.04-.05.05-.07.06a.12.12 0 01-.07 0c-.03 0-.05-.02-.09-.06L512 569.93l-287.7 287.7c-.04.04-.06.05-.09.06a.12.12 0 01-.07 0c-.02 0-.04-.02-.08-.06l-57.69-57.7c-.04-.03-.05-.05-.06-.07a.12.12 0 010-.07c0-.03.02-.05.06-.09L454.07 512l-287.7-287.7c-.04-.04-.05-.06-.06-.09a.12.12 0 010-.07c0-.02.02-.04.06-.08l57.7-57.69c.03-.04.05-.05.07-.06a.12.12 0 01.07 0c.03 0 .05.02.09.06L512 454.07l287.7-287.7c.04-.04.06-.05.09-.06a.12.12 0 01.07 0z"></path></svg>
                </span>
              </button>
            </div>
            <div class="modal-body">
                <!-- Loading form.blade.php -->
            </div>
          </div>
        </div>
    </div>

    @if (EcommerceHelper::isValidToProcessCheckout())
        <script>
            $(document).ready(function(){
                // Check if PayWay is selected
                if ($('input[name="payment_method"]:checked').val() === 'payway') {
                    $('#checkout-form').off('submit').on('submit', function(event) {
                        event.preventDefault(); // Prevent the default form submission

                        var form = $(this);
                        var actionUrl = form.attr('action'); // Get the form action URL

                        // Disable the submit button to prevent multiple submissions
                        $('.payment-checkout-btn').prop('disabled', true);

                        // Perform an AJAX request to the server
                        $.ajax({
                            type: form.attr('method'), // GET or POST
                            url: actionUrl,
                            data: form.serialize(), // Send form data
                            success: function(response) {
                                $('#paymentModal .modal-body').html(response);
                                $('#paymentModal').modal('show');
                            },
                            error: function(xhr, status, error) {
                                // Handle any errors here
                                console.error('Error occurred: ' + error);
                                // Re-enable the submit button in case of error
                                $('.payment-checkout-btn').prop('disabled', false);
                            }
                        });
                    });

                    // Directly bind reload to the close button of Modal
                    $('.close').on('click', function() {
                        window.location.reload(true);
                    });

                    // Reload the ABA PayWay iFrame Modal loading on mobile
                    document.body.addEventListener('click', function(event) {
                        if (event.target.classList.contains('aba_checkout_overlay')) {
                            location.reload();
                        }
                    });
                }
            });
        </script>
    @endif
    </x-plugins-payment::payment-method>
@endif