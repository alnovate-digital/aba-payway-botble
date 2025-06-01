<script>
    document.addEventListener('DOMContentLoaded', function () {
        const valuesToRemove = ['abapay', 'bakong', 'cards', 'alipay', 'wechat'];

        valuesToRemove.forEach(value => {
            const option = document.querySelector(`option[value="${value}"]`);
            if (option) option.remove();
        });
    });
</script>

<ol>
    <li>
        <p>For ABA PayWay Sandbox registration and documentation, go to
            <a href="https://www.payway.com.kh/developers" target="_blank">
                https://www.payway.com.kh/developers
            </a>
        </p>
        <p>To apply for the payment gateway, go to
            <a href="https://www.payway.com.kh/" target="_blank">
                https://www.payway.com.kh
            </a>
            and then click "Apply Now"
        </p>
    </li>
    <li>
        <p>{{ __('After registration at :name, you will have Merchant ID and API Key', ['name' => 'ABA PayWay']) }}</p>
    </li>
    <li>
        <p>{{ __('Enter Merchant ID and API Key (Sandbox or Production)') }}</p>
    </li>
    <li>
        <p>{{ __('Enter all required API URLs (Sandbox or Production)') }}</p>
    </li>
</ol>