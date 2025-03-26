=== Integrate ZarinPal for EDD ===
Contributors: alireza1219
Tags: zarinpal, easy digital downloads, payment gateway, edd, ecommerce  
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable Tag: 1.0.0
License: GPLv2 or later

A payment gateway integration for Easy Digital Downloads using Zarinpal, a leading Iranian payment processor.

== Description ==

This plugin integrates Zarinpal, a popular Iranian payment gateway, with Easy Digital Downloads (EDD). It allows users to make secure transactions using Zarinpal while purchasing digital products through EDD.

**Features:**  
- Seamless integration with Easy Digital Downloads  
- Secure and reliable payment processing through Zarinpal  
- Customizable settings for Merchant ID
- Compatible with the latest WordPress versions  

**Disclaimer**: This plugin is not associated with, endorsed by, or officially supported by Easy Digital Downloads (EDD) or ZarinPal. All trademarks and logos mentioned are the property of their respective owners.

== External services ==

This plugin relies on the following external services to ensure proper functionality:

### 1. ZarinPal (Payment Gateway)

- **Purpose**: A service used for securely processing payments and handling financial transactions on your website.
- **Data sent**:
  - The order amount.
  - The user's full name (if provided).
  - Callback URL (redirects the user back to the website after payment).
  - Merchant ID (provided by ZarinPal to the website owner).
- **When the data is sent**:
  - When the user initiates the EDD (Easy Digital Downloads) payment process and selects ZarinPal as the payment gateway.
  - When the ZarinPal transaction is completed, and the user is redirected back to the website.
- **Service Information**:
  - Official website: [ZarinPal](https://www.zarinpal.com)
  - Terms of Service: [ZarinPal Terms of Service](https://www.zarinpal.com/terms.html)
  - Privacy Policy: [ZarinPal Privacy Policy](https://www.zarinpal.com/policy.html)

### 2. ZarinPal Sandbox (Custom Gateway)

- **Disclaimer**: This sandbox environment is not provided by ZarinPal and was developed and hosted by the plugin developer for testing purposes.
- **Purpose**: A custom sandbox environment created to simulate the ZarinPal payment gateway for testing and development purposes, following ZarinPal's discontinuation of their official sandbox environment.
- **Data sent**:
  - The order amount.
  - The user's full name (if provided).
  - Callback URL (redirects the user back to the website after payment).
  - Merchant ID (any valid 36-character string).
- **When the data is sent**:
  - When the user initiates the EDD payment process and selects ZarinPal (Sandbox) as the payment gateway.
  - When the transaction is completed, and the user is redirected back to the website.
- **Additional Information**:
  - This service is only being used when the payment test mode is enabled from EDD settings.
  - Data stored by the sandbox environment service will be cleared daily at 00:00 (midnight) IRST (Iran Standard Time) from the database.
  - This custom sandbox environment is hosted by the plugin developer.
  - The source code of the sandbox environment is available at [GitHub](https://github.com/alireza1219/zarinpal-sandbox).
- **Service Information**:
  - [Terms of Service and Privacy Policy](https://sandbox.alireza1219.ir/tos/zarinpal-sandbox-tos-pivacy.html)

== Frequently Asked Questions ==

= How do I get a Zarinpal Merchant ID? =  
Register and obtain your Merchant ID by signing up at [Zarinpal’s official website](https://www.zarinpal.com).  

= Is this plugin compatible with all Easy Digital Downloads versions? =  
This plugin is tested with the latest version of Easy Digital Downloads. However, ensure you are using an updated version for the best compatibility.  

= Does this plugin support sandbox mode for testing? =  
Yes! You can enable EDD's test mode setting for testing transactions.  

== Screenshots ==

1. Configure your Zarinpal Merchant ID and options.  
2. Customers can choose Zarinpal as their payment method.  
3. Successful payment confirmation page.  

== Changelog ==

= 1.0.0 =  
- Initial release  

== Upgrade Notice ==

= 1.0.0 =  
First release. Please configure your settings after installation.
