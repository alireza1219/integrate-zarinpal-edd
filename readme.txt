=== Integrate ZarinPal for EDD ===
Contributors: alireza1219
Tags: zarinpal, easy digital downloads, edd, زرین پال, ایزی دیجیتال دانلودز
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable Tag: 1.2.0
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

### ZarinPal

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

== Frequently Asked Questions ==

= How do I get a Zarinpal Merchant ID? =
Register and obtain your Merchant ID by signing up at [Zarinpal’s official website](https://www.zarinpal.com).

= Is this plugin compatible with all Easy Digital Downloads versions? =
This plugin is tested with the latest version of Easy Digital Downloads. However, ensure you are using an updated version for the best compatibility.

= Does this plugin support sandbox mode for testing? =  
Yes! You can enable EDD's test mode setting for testing transactions.

= Does this plugin support recurring payments =
Unfortunately, no. ZarinPal does not support recurring payments.

== Screenshots ==

1. Configure your Zarinpal Merchant ID and options.
2. Customers can choose Zarinpal as their payment method.
3. Successful payment confirmation page.

== Changelog ==

= 1.0.0 =  
- Initial release

= 1.1.0 =
- Prevent payment process when a recurring item is present in the cart

= 1.2.0 =
- Compatibility with WordPress 7.x and EDD 3.7.x
- Updated ZarinPal endpoints to v4 API
- Switched to the official sandbox environment

== Upgrade Notice ==

= 1.0.0 =  
First release. Please configure your settings after installation.

= 1.2.0 =
This version drops support for PHP 7.4. If you're still using PHP 7.4, please upgrade to PHP 8.0 or later.
