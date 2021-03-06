=== WooCommerce Pakettikauppa ===
Contributors: ottok, leotoikka, serter
Donate link: https://seravo.com/
Tags: woocommerce, shipping, toimitustavat, pakettikauppa, posti, smartpost, prinetti, matkahuolto, schenker, seravo
Requires at least: 4.6
Tested up to: 4.9
Requires PHP: 5.6.0
Stable tag: trunk
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin enables WooCommerce orders to ship using pretty much any shipping method available in Finland via Pakettikauppa.

== Description ==

[Pakettikauppa](https://www.pakettikauppa.fi/) is a shipping service provider in Finland. This plugin integrates their service into WooCommerce. To start shipping, all your WooCommerce needs is this plugin and a merchant ID of your account registered with Pakettikauppa.

> *Note!* If you already have shipping contracts with Posti, Matkahuolto or DB Schenker with reduced prices, you can contact the customer support of Pakettikauppa to get those contracts via Pakettikauppa so you can use the WooCommerce Pakettikauppa plugin with your current shipping contracts.

# Features

*   Integrates Pakettikauppa with WooCommerce
*   Based on the official [Pakettikauppa API library](https://github.com/Pakettikauppa/api-library)
*   Supports WooCommerce shipping zones (though Pakettikauppa is currently only available in Finland)
*   Store owners can specify themselves any fixed rate for a shipping or have free shipping if the order value is above a certain limit
*   Customers can choose to ship products to an address or to any pickup point available from the Pakettikauppa shipping methods
*   Store owner can generate the shipping label in one click
*   Store owners and customers get tracking code links and status information
*   Test mode available that uses the testing API

== Installation ==


1. Upload the plugin files to the `/wp-content/plugins/woocommerce-pakettikauppa` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->WooCommerce Pakettikauppa screen to configure the plugin
1. The plugin requires WooCommerce to be installed, with shipping zones configured and this plugin activated and settings set.

This plugin can also be installed directly from Github or using `composer require seravo/woocommerce-pakettikauppa`.

== Frequently Asked Questions ==

= Is this ready for production use? =

Yes! If you encounter any issues related to this plugin, please report at https://github.com/Seravo/woocommerce-pakettikauppa/issues

== Screenshots ==

1. Examples of settings screens

== Changelog ==

= 1.0 =
* Initial release for General Availability.

== Upgrade Notice ==

This plugin follows [semantic versioning](https://semver.org). Take it into account when updating.
