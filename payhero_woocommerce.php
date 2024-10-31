<?php
// Plugin Name: PayHero For WooCommerce
//Description: This plugin allows you to receive and process payments automatically from Payhero MPESA Gateway. You need to have an account with Payhero to use this plugin. To get an account, visit <a href="https://app.payhero.co.ke" target="_blank">https://app.payhero.co.ke</a>
//Version: 2.1.2
//Author: Payhero Kenya Limited
//Author URI: https://payherokenya.com
//License: GPL2
//Text Domain: payhero_woocommerce_plugin
// Exit if accessed directly
if (!defined('ABSPATH'))
{
    exit;
}

add_action('rest_api_init', 'payhero_add_callback_url_endpoint');

function payhero_add_callback_url_endpoint()
{
    register_rest_route('payhero-woocommerce/v2/',
    //Namespace
    'receive-callback',
    //Endpoint
    array(
        'methods' => 'POST',
        'callback' => 'payhero_receive_callback',
    ));
}

function payhero_receive_callback($request_data)
{
    #1. STORE DETAILS
    $store_name = get_bloginfo('name');
    $store_email = get_bloginfo('admin_email');
    $store_address = get_bloginfo('url');
    $Payment_Method = "Pay Hero";
    #2. GET DATA FROM PAYHERO
    $json_data = $request_data->get_json_params();
    if (isset($json_data['response']))
    {
        $amount = $json_data['response']['Amount'];
        $external_reference = $json_data['response']['User_Reference'];
        $mpesa_receipt_number = $json_data['response']['MPESA_Reference'];
        $phone = $json_data['response']['Source'];
        $payment_success_status= strtolower($json_data['response']['woocommerce_payment_status']);
        #3. GET AN INSTANCE OF THE WC_ORDER OBJECT
        $order = wc_get_order($external_reference);
        $order_data = $order->get_data(); // The Order data
        //order total
        $order_total = $order->get_total();
        //get order currecny symbol
        $currency_code = $order->get_currency();
        $currency_symbol = get_woocommerce_currency_symbol($currency_code);
        $order_status = $order->get_status();
        #4.BILLING INFORMATION:
        $order_billing_first_name = $order_data['billing']['first_name'];
        $order_billing_last_name = $order_data['billing']['last_name'];
        $order_billing_email = $order_data['billing']['email'];
        $order_billing_phone = $order_data['billing']['phone'];
        #5. CHECK IF AMOUNT IS GREATER OR EQUAL TO ORDER_TOTAL AND ORDER IS PENING
        if ($order_status === 'pending')
        {
            if ($amount >= $order_total)
            {
                $customerMessage = "Dear $order_billing_first_name $order_billing_last_name, your payment of $currency_symbol $amount via $Payment_Method to $store_name for order #$external_reference Has been successfully received. Payment reference: $mpesa_receipt_number . Thank you for your payment.";
                #6.UPDATE OUR ORDER STATUS TO $PAYMENT_SUCCESS_STATUS:
                $order->set_customer_note($customerMessage);
                $order->update_status($payment_success_status, 'Payment received via ' . $Payment_Method . ' with transaction reference: ' . $mpesa_receipt_number . ' and amount: ' . $currency_symbol . ' ' . $amount . '');
                $order->add_order_note(sprintf("Payment Success: '%s'", $customerMessage));
            }
            else
            {
                #7.UPDATE ORDER STATUS TO FAILED:
                $order->update_status('failed', 'Payment failed');
                //send email to customer
                $to = $order_billing_email;
                $subject = 'Payment Failed';
                $message = 'Dear ' . $order_billing_first_name . ' ' . $order_billing_last_name . '- ' . $order_billing_phone . ', your payment of ' . $currency_symbol . ' ' . $amount . ' to ' . $store_name . ' for order #' . $external_reference . ' via ' . $Payment_Method . ' has failed because the paid amount was less than order total. Contact us at ' . $store_address . ' or ' . $store_email . ' for assistance.';
                //Add order note on order
                $order->add_order_note(sprintf("Payment Failed: '%s'", $message));
                //Send payment failure email to client
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8'
                );
                wp_mail($to, $subject, $message, $headers);
            }

            return new WP_REST_Response(array(
                'message' => 'Payment Processed successfully'
            ) , 200);
        }
        else
        {
            return new WP_Error('invalid_order', 'Invalid Order data or Order already updated.', array(
                'status' => 400
            ));
        }
    }

    return new WP_Error('invalid_json', 'Invalid JSON data', array(
        'status' => 400
    ));
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function custom_gateway_init() {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

        class WC_Custom_Gateway extends WC_Payment_Gateway {

            public function __construct() {
                $this->id = 'custom_gateway';
                $this->method_title = __( 'Pay Hero MPESA Gateway', 'woocommerce' );
                $this->method_description = __( 'Allows payments with Pay Hero MPESA gateway.', 'woocommerce' );

                // Load the settings
                $this->init_form_fields();
                $this->init_settings();

                // Get settings
                $this->title = $this->get_option( 'title' );
                $this->icon = $this->get_option( 'icon' );
                $this->order_status = $this->get_option( 'order_status' );
                $this->redirect_url = $this->get_option( 'redirect_url' );

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
                add_action( 'woocommerce_api_wc_' . $this->id, array( $this, 'check_response' ) );
            }

            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __( 'Enable/Disable', 'woocommerce' ),
                        'type' => 'checkbox',
                        'label' => __( 'Enable Custom Payment Gateway', 'woocommerce' ),
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                        'default' => __( 'MPESA Express', 'woocommerce' ),
                        'desc_tip' => true,
                    ),
                    'icon' => array(
                        'title' => __( 'Icon URL', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'This controls the icon which the user sees during checkout.', 'woocommerce' ),
                        'default' => 'https://www.safaricom.co.ke/images/Lipanampesa.png',
                        'desc_tip' => true,
                    ),
                    'order_status' => array(
                        'title' => __( 'Order Status After Checkout', 'woocommerce' ),
                        'type' => 'select',
                        'description' => __( 'Choose the order status after checkout.', 'woocommerce' ),
                        'default' => 'wc-pending',
                        'options' => wc_get_order_statuses(),
                        'desc_tip' => true,
                    ),
                    'redirect_url' => array(
                        'title' => __( 'Pay Hero Lipwa Link', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'URL to redirect after checkout.', 'woocommerce' ),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'channel_id' => array(
                        'title' => __( 'Payment Channel ID', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'Your Pay Hero payment channel ID', 'woocommerce' ),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'ph_callback_url'=>array(
                        'title' => __( 'Pay Hero Callback URL (Copy And Use On Pay Hero Account)', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'This is the callback URL you should set in your Pay Hero Account', 'woocommerce' ),
                        'default' => get_bloginfo('url').'/wp-json/payhero-woocommerce/v2/receive-callback',
                        'desc_tip' => true,
                    )
                );
            }

            public function admin_options() {
                ?>
                <h2><?php _e( 'Custom Payment Gateway', 'woocommerce' ); ?></h2>
                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                </table>
                <?php
            }

            public function process_payment( $order_id ) {
                global $woocommerce;

                $order = wc_get_order( $order_id );

                // Mark as on-hold (we're awaiting the payment)
                $order->update_status( $this->order_status, __( 'Awaiting custom payment', 'woocommerce' ) );

                // Reduce stock levels
                wc_reduce_stock_levels( $order_id );

                // Empty cart
                $woocommerce->cart->empty_cart();

                // Redirect to custom thank you page
                $redirect_url = $this->get_option( 'redirect_url' );

                $channel_id = $this->get_option( 'channel_id' );
                $reference=$order_id;
                $amount=$order->get_total();
                $phone=$order->billing_phone;
                $order_key = $order->get_order_key();
                $order_received_url = wc_get_endpoint_url('order-received', $order_id, wc_get_checkout_url());
                $order_received_url = add_query_arg('key', $order_key, $order_received_url);
                $return_url = urlencode($order_received_url);
                // urlencode($this->get_return_url($order));
                $redirect_to=$redirect_url."?channel_id=$channel_id&reference=$reference&amount=$amount&phone=$phone&success_url=$return_url";
                return array(
                    'result'   => 'success',
                    'redirect' => $redirect_to
                );
            }

            public function receipt_page( $order ) {
                echo '<p>' . __( 'Thank you for your order.', 'woocommerce' ) . '</p>';
            }

            public function check_response() {
                // Handle the response from the payment gateway here
            }
        }
    }

    add_action( 'plugins_loaded', 'custom_gateway_init', 11 );

    function add_custom_gateway( $methods ) {
        $methods[] = 'WC_Custom_Gateway';
        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway' );
}

