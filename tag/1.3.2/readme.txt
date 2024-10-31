=== Payhero SPS Gateway ===
Tags: payhero, sps, mpesa plugin, payments,payhero kenya, online payments
Requires at least: 6.0
Tested up to: 6.1
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Payhero offers a unique and convenient service for users in the Kenyan market called the Swift Payment Service (SPS) which allows them to easily receive payments from MPESA, a popular mobile payment service in Kenya, directly to their bank account. The SPS plugin for WordPress and WooCommerce offers an easy way for users to integrate this service on their website, and process order payments automatically.

With this plugin, users can easily set up and manage their SPS account through the Pay Hero Store menu in WordPress, after installing and activating the plugin. To use the plugin, users must first have an account with Payhero and obtain their credentials, which they can then enter into the plugin's settings. This ensures that the checkout process on their WooCommerce website is streamlined and secure.

The Payhero SPS plugin for WordPress and WooCommerce is an ideal solution for users in the Kenyan market who are looking for a reliable and secure way to receive payments through MPESA. The plugin's seamless integration with WordPress and WooCommerce websites allows users to easily manage and process their transactions, making it an efficient solution for businesses and individuals looking to receive payments online. The plugin also allows for easy paying of bills and pay till numbers which make life easy for the users.

== Description ==
Payhero offers a unique and convenient service for users in the Kenyan market called the Swift Payment Service (SPS) which allows them to easily receive payments from MPESA, a popular mobile payment service in Kenya, directly to their bank account. The SPS plugin for WordPress and WooCommerce offers an easy way for users to integrate this service on their website, and process order payments automatically.

With this plugin, users can easily set up and manage their SPS account through the Pay Hero Store menu in WordPress, after installing and activating the plugin. To use the plugin, users must first have an account with Payhero and obtain their credentials, which they can then enter into the plugin's settings. This ensures that the checkout process on their WooCommerce website is streamlined and secure.

The Payhero SPS plugin for WordPress and WooCommerce is an ideal solution for users in the Kenyan market who are looking for a reliable and secure way to receive payments through MPESA. The plugin's seamless integration with WordPress and WooCommerce websites allows users to easily manage and process their transactions, making it an efficient solution for businesses and individuals looking to receive payments online. The plugin also allows for easy paying of bills and pay till numbers which make life easy for the users.

You need to have an account on SPS: https://payherokenya.com/sps-app
Once you create your account you can now get your credentials to be used under Pay Hero Store menu on wordpress after installig and activating the plugin.

== Installation ==

To install this plugin you can either download it from the wordpress directory by going to your wordpress admin panel, plugins, add new, then search Payhero SPS Gateway and install.
Alternatively you can manually upload the plugin:
follow below instructions:

1. Upload `payhero-callback-url-functions.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

After installing and activating this plugin, you will require to install or setup our custom gateway creator plugin (Payhero Flexible Payment Gateway Creator), that will enable you to create a custom payment gateway that users will interact with on the front end to enable them make payments.

Most importantly you will need to provide your Pay Hero Store Information. From your wordpress admin panel navigate to the menu: Pay Hero Store Information, here you will provide your store details and username plus API key you got after creating an account on SPS.

== Frequently Asked Questions ==

= What is Pay Hero SPS =

Pay Hero SPS is a product of Pay Hero Kenya LTD that enables businesses to receive payments directly from their customers into their preffered payment channels. It is currently actively supported in the Kenyan market.
You can get paid directly to your bank account, paybill number or till number via MPESA.

= What do I need to use it =

You will first of all need an account on SPS by visiting the following URL https://payherokenya.com/sps-app, after creating an account, you will have your KYC documents validated and account approved. Get your credentials eg. username and API key to be used in this plugin.

= What payment channels do you support ? =
Currently you can link your kenyan bank account, MPESA pabill or till number to receive payments directly.

= Is it free ? =
No, the service has service charges applied depending on the payment amount you receive, you will be required to top up your service wallet regularly. Reffer to the services charges in the plugin UI.
You can also charge your users an extra fee on the checkout for the service fee we charge you, you can enable or disable this, within the plugin.

= How Can I contact You For Support ? =
You can contact us via whatsapp/call: +254765344101 or email payherokenya@gmail.com

== Changelog ==
= 1.3.2 =
    -Added ability to name the payment gateway fees to whatever name you preffer. This will be visible to the user during checkout.
= 1.3.1 =
    -Added ability to add gateway fees to cart total when customer selects your created Pay Hero gateway.
        If you select Yes, Pay Hero gateway fees will be added to the user's cart total during checkout, this is a simple way to get "compensated" for the service fees we will charge you.
        You can now enable this by going to "Pay Hero Store Information" menu and at the bottom, select your created Pay Hero Payment method, then allow adding gateway fees to cart total.