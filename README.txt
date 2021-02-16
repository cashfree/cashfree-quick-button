=== Cashfree Quick Button ===
Contributors: Cashfree
Plugin Name: Cashfree Quick Button
Plugin URI: https://www.gocashfree.com
Tags: cashfree, payments, wp, button, simple
Requires at least: 4.2
Tested up to: 5.6
Stable tag: 1.0.0
Version: 1.0.0

Allows you to easily built payment buttons on your WordPress website.

== Description ==

This is the official "Cashfree Quick Button" plugin for Cashfree merchants. This allows
you to do the following:

1. Add a few custom variables and some markup to a page.
2. Specify the amount, title, description and other custom details as page metadata.
3. Write [CFPB] wherever you want on the post and the button to show up.
4. The plugin takes over and completes the payment.

This makes use of the Cashfree Orders API, and the flow is the follows:

1. The plugin parses the page before it is rendered
2. Inserts its javascript/css/html if it finds the relevant data and markup
3. A click on the button creates an "order" using Cashfree API
4. The payment is completed there itself and the customer is informed

== Customization ==

For this plugin to work correctly, please mention the following items as page metadata (using Screen Options for >4.8) -> Custom Fields:

1. 'title' of the product.
2. 'description' of the product.
3. 'orderAmount' with a minimum of 1 rupee.