=== Balanced Payments ===
Contributors: patrickgarman 
Donate link: https://pmgarman.me/donate/
Tags: balanced payments, balanced, payments, credit card, skeuocard
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 2.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Create a simple payment form (optionally with Skeuocard) to take credit card payments on your website.

== Description ==

Create a simple payment form (optionally with Skeuocard) to take credit card payments on your website. It gracefully falls back when javascript is not available and handles the post data normally, otherwise handles the form via AJAX for all other users.

Makes use of Skeuocard 1.0.3 for enhanced styling of the credit card form.

https://github.com/kenkeiter/skeuocard

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Changelog ==

= 2.0.0 - 08/26/2014 =
* Add option for "appears on statement as"
* Add option for default value on form
* Add option for BP dashboard description
* Add CMB as Submodule
* Add Skeuocard as Submodule
* Add helper links to plugin dashboard
* Add option for email notification when payments are made using default wp_mail
* Add check for form processing to ensure forms are not submitted multiple times causing multiple charges
* Update for BP API v1.1
* Fix when making payment customer is not created, causing card to not attach to anyone and not be able to be debited.

= 1.0.0 - 10/11/2013 =
* Intial Release

== Frequently Asked Questions ==

= Do I need a SSL certificate to use this? =

By default credit card data is taken using Balanced.js which does not require a SSL certificate. To ensure customer trust you are better off having one though, a simple SSL certificate can be else than $10/year.

When javascript is not available the form will fall back to normal POST handling, and credit card data WILL be passed through your server and you SHOULD have a SSL certificate.