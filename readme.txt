=== Paid Memberships Pro - Gift Aid Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, membership, uk, gift aid
Requires at least: 5.4
Tested up to: 6.6
Stable tag: 0.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Add a checkbox to Opt In to the UK Gift Aid Tax-Incentive on Membership Checkout.

== Description ==
Gift Aid allows individuals who are subject to UK income tax to complete a simple, short declaration that they are a UK taxpayer. If you don’t know what Gift Aid is – you probably don't need this.

This Add On allows you to add a checkbox to membership checkout so customers can opt in to Gift Aid.

This plugin requires Paid Memberships Pro. 

== Installation ==

1. Upload the `pmpro-gift-aid` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit the levels you want to add donations to and set the "Donation" settings.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-gift-aid/issues

== Changelog ==

= 0.2 - 2024-10-19 =
* ENHANCEMENT: Updated the frontend UI for compatibility with PMPro v3.1. #18 (@MaximilianoRicoTabo, @kimcoleman)
* ENHANCEMENT: Added compatibility for the Donations Add On for Paid Memberships Pro. #18 (@kimcoleman)
* ENHANCEMENT: Added filter `pmproga_show_gift_aid_at_checkout` to show or hide the gift aid checkbox. #18 (@kimcoleman)
* BUG FIX: Fixed compatibility with admin checkout email templates updated in PMPro v3.0. #19 (@MaximilianoRicoTabo)
* BUG FIX: Fixed bug where Gift Aid user meta was not set if unchecked. #17 (@MaximilianoRicoTabo)

= .1.2 =
* BUG FIX: Fixed issue where gift aid data wasn't being saved into user meta after checkout with PayPal Standard.

= .1.1 =
* BUG FIX: Fixed issue where gift aid data wasn't being saved into user meta after checkout with some gateways.
* BUG FIX: Fixed a warning.

= .1 =
* This is the initial version of the plugin.