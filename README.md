## ABA PayWay Payment Gateway Plugin for Botble CMS
This plugin allows you to integrate ABA PayWay Payment Gateway into Botble CMS.

> [!WARNING]  
> This plugin is not working properly and requires fixes.

## Installation

- Download and extract plugin, rename the folder to `payway` and then upload to `platform/plugins/` folder.
- Go to Admin Panel > Plugins, then activate `PayWay by ABA Bank` plugin.
- Go to Admin Panel > Settings > Payment methods, then enter your gateway credentials.

Note: Supported currencies for this plugin are `USD` and `KHR`.

## Supported Features

- [x] Create Transaction
- [x] Check Transaction
- [x] Transaction List
- [ ] Refund Transaction
- [ ] Pre-Authorization
- [ ] Account-On-File (AOF)
- [ ] Card-On-File (COF)
- [ ] Exchange Rate
- [ ] Payment Link

## Sandbox API Endpoints

- Create Transaction: 
  ```shell
  https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase
  ```
- Check Transaction:
  ```shell
  https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction-2
  ```
- Transaction List:
  ```shell
  https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/transaction-list-2
  ```
- For other Test URLs and Production, please refer to its detailed documentation.

## Documentation

Here you'll find detailed documentation and references to help you integrate PayWay APIs on your software solution to take online payments securely on any platform.
PayWay Developer Suite: https://www.payway.com.kh/developers

## License

This plugin is released under the MIT License.
