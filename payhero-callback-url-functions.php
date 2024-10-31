<?php

// Plugin Name: Payhero SPS Gateway
//Description: This plugin allows you to receive and process payments automatically from Payhero SPS Gateway. You need to have an account with Payhero SPS Gateway to use this plugin. To get an account, visit <a href="https://payherokenya.com/sps-app" target="_blank">https://payherokenya.com/sps-app</a>
//Version: 1.3.3
//Author: Payhero Kenya Limited
//Author URI: https://payherokenya.com
//License: GPL2
//Text Domain: payhero-sps-gateway

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'payhero_add_callback_url_endpoint');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'payhero_add_action_links');
add_action('woocommerce_cart_calculate_fees', 'payhero_wc_add_cart_fees_by_payment_gateway');

function payhero_add_action_links($links)
{
    $mylinks = array(
        '<a href="' . admin_url('admin.php?page=ph_store-info') . '">Add/Edit Store Details</a>',
        '<a href="' . admin_url('admin.php?page=ph_sps-info') . '">Manage SPS Account</a>',
    );
    return array_merge($links, $mylinks);
}

function payhero_add_callback_url_endpoint()
{
    register_rest_route(
        'payhero/v1/',
        //Namespace
        'receive-callback',
        //Endpoint
        array(
            'methods' => 'POST',
            'callback' => 'payhero_receive_callback',
        )
    );
}

function payhero_receive_callback($request_data)
{
    #1.STORE DETAILS VARIABLES: STORE_NAME,STORE_EMAIL,$STORE_PHONE,$STORE_ADDRESS:
    //DO NOT EDIT THIS SECTION: Go to your Wp dashboard and click on Pay Hero Store Information to add your store details
    $store_name = get_option('ph_store_name');
    $store_email = get_option('ph_store_email');
    $store_phone = get_option('ph_store_phone');
    $store_address = get_option('ph_store_address');
    $payment_success_status = get_option('ph_payment_success_status');
    #2.GET DATA FROM PAYHERO:
    $parameters = $request_data->get_params();
    $Transaction_Type = $parameters['Transaction_Type'];
    $Source = $parameters['Source'];
    $Amount = $parameters['Amount'];
    $Transaction_Reference = $parameters['Transaction_Reference'];
    $Payment_Method = $parameters['Payment_Method'];
    $User_Reference = $parameters['User_Reference'];
    #3.GET AN INSTANCE OF THE WC_ORDER OBJECT:
    $order = wc_get_order($User_Reference);
    $order_data = $order->get_data(); // The Order data
    //order total
    $order_total = $order->get_total();
    //get order currecny symbol
    $currency_code = $order->get_currency();
    $currency_symbol = get_woocommerce_currency_symbol($currency_code);
    #4.BILLING INFORMATION:
    $order_billing_first_name = $order_data['billing']['first_name'];
    $order_billing_last_name = $order_data['billing']['last_name'];
    $order_billing_email = $order_data['billing']['email'];
    $order_billing_phone = $order_data['billing']['phone'];
    #5.CHECK IF AMOUNT IS GREATER OR EQUAL TO ORDER_TOTAL:
    if ($Amount >= $order_total) {
        $customerMessage = "Dear $order_billing_first_name $order_billing_last_name, your payment of $currency_symbol $Amount via $Payment_Method to $store_name for order #$User_Reference Has been successfully received. Payment reference: $Transaction_Reference . Thank you for your payment.";
        #6.UPDATE OUR ORDER STATUS TO $PAYMENT_SUCCESS_STATUS:
        $order->set_customer_note($customerMessage);
        $order->update_status($payment_success_status, 'Payment received via ' . $Payment_Method . ' with transaction reference: ' . $Transaction_Reference . ' and amount: ' . $currency_symbol . ' ' . $Amount . '');
        $order->add_order_note(sprintf("Payment Success: '%s'", $customerMessage));
    } else {
        #7.UPDATE ORDER STATUS TO FAILED:
        $order->update_status('failed', 'Payment failed');
        //send email to customer
        $to = $order_billing_email;
        $subject = 'Payment Failed';
        $message = 'Dear ' . $order_billing_first_name . ' ' . $order_billing_last_name . '- ' . $order_billing_phone . ', your payment of ' . $currency_symbol . ' ' . $Amount . ' to ' . $store_name . ' for order #' . $User_Reference . ' via ' . $Payment_Method . ' has failed because the paid amount was less than order total. Contact us at ' . $store_phone . ' or ' . $store_email . ' for assistance.';
        //Add order note on order
        $order->add_order_note(sprintf("Payment Failed: '%s'", $message));
        //Send payment failure email to client
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $message, $headers);
    }

    return 'Payment Processed';
}

//Pay hero store information page.
function payhero_store_info_menu()
{
    add_menu_page('Pay Hero Store Information', 'Pay Hero Store Information', 'manage_options', 'ph_store-info', 'payhero_store_info_page', 'dashicons-store', 20);
}
function payhero_sps_details_menu()
{
    add_menu_page('Manage SPS Account', 'Manage SPS Account', 'manage_options', 'ph_sps-info', 'payhero_sps_info_page', 'dashicons-money', 20);
}
//Action hooks
add_action('admin_menu', 'payhero_store_info_menu');
add_action('admin_menu', 'payhero_sps_details_menu');

//Pay hero store information page
function payhero_store_info_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
    <div class="wrap">
        <h1>Pay Hero Store Information</h1>
        <!-- show an info notice with description -->
        <div class="notice notice-info is-dismissible">
            <p><span class="dashicons dashicons-store"></span> Provide your store information below. This information will
                be used in the Pay Hero payment gateway plugin. You can edit this information any time you want. Fields
                marked with an asteric * are required. You neeed an account on Payhero SPS <a
                    href="https://payherokenya.com/sps-app" target="_blank">Login/Sign Up</a> To get your Username and API
                Key</p>
        </div>
        <!-- create a form to update or create options -->
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="store_name">Store Name *</label></th>
                    <td><input type="text" name="store_name" value="<?php echo esc_html(get_option('ph_store_name')); ?>"
                            id="store_name" class="regular-text" placeholder="Enter your store name" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="store_phone">Store Phone *</label></th>
                    <td><input type="tel" name="store_phone" value="<?php echo esc_html(get_option('ph_store_phone')); ?>"
                            id="store_phone" class="regular-text" placeholder="Enter your store phone" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="store_email">Store Email *</label></th>
                    <td><input type="email" name="store_email" value="<?php echo esc_html(get_option('ph_store_email')); ?>"
                            id="store_email" class="regular-text" placeholder="Enter your store email" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="store_address">Store Address</label></th>
                    <td><textarea name="store_address" id="store_address" rows="5" cols="30"
                            placeholder="Enter your store adress"><?php echo esc_html(get_option('ph_store_address')); ?></textarea>
                    </td>
                </tr>
                <!-- show payment success status option list: processing,completed -->
                <tr>
                    <th scope="row"><label for="payment_success_status">Payment Success Status *</label></th>
                    <td>
                        <select name="payment_success_status" id="payment_success_status">
                            <option value="processing" <?php if (get_option('ph_payment_success_status') == "processsing") {
                                "selected";
                            } ?>>Processing</option>
                            <option value="completed" <?php if (get_option('ph_payment_success_status') == "completed") {
                                echo
                                    "selected";
                            } ?>>Completed</option>
                        </select>
                        <p class="description">The order status to be set when a payment is successfully made.</p>
                    </td>
                </tr>
                <!-- hr -->
                <tr>
                    <th scope="row">
                        <hr>
                    </th>
                    <td>
                        <hr>
                    </td>
                </tr>
                <!-- SPS Username form -->
                <tr>
                    <th scope="row"><label for="sps_username">SPS Username *</label></th>
                    <td><input type="text" name="sps_username"
                            value="<?php echo esc_html(get_option('ph_sps_username')); ?>" id="sps_username"
                            class="regular-text" placeholder="Enter your SPS username" /></td>
                </tr>
                <!-- SPS API Key form -->
                <tr>
                    <th scope="row"><label for="sps_api_key">SPS API Key *</label></th>
                    <td><input type="password" name="sps_api_key"
                            value="<?php echo esc_html(get_option('ph_sps_api_key')); ?>" id="sps_api_key"
                            class="regular-text" placeholder="Enter your SPS API key" /></td>
                </tr>
                <!-- hr -->
                <tr>
                    <th scope="row">
                        <hr>
                    </th>
                    <td>
                        <hr>
                    </td>
                </tr>
                <!-- Payhero Payment Method -->
                <tr>
                    <th scope="row"><label for="payment_method">Pay Hero Payment Method</label></th>
                    <td>
                        <select name="payment_method" id="payment_method">
                            <option value="">Select Payment Method</option>
                            <?php
                            $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
                            foreach ($installed_payment_methods as $method) {
                                if ($method->enabled == 'yes') {
                                    echo '<option value="' . $method->id . '" ' . ((get_option('ph_payment_method') == $method->id) ? 'selected' : '') . '>' . $method->title . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <p class="description">Select the Pay Hero payment method that you created.</p>
                    </td>
                </tr>
                <!-- If gateway fees should be added to cart total -->
                <tr>
                    <th scope="row"><label for="payment_gateway_fees">Add Gateway Fees To Cart Total ?</label></th>
                    <td>
                        <select name="payment_gateway_fees" id="payment_gateway_fees">
                            <option value="no" <?php if (get_option('ph_payment_gateway_fees') == "no") {
                                "selected";
                            } ?>>No</option>
                            <option value="yes" <?php if (get_option('ph_payment_gateway_fees') == "yes") {
                                echo
                                    "selected";
                            } ?>>Yes</option>
                        </select>
                        <p class="description">If you select Yes, Pay Hero gateway fees will be added to the user's cart
                            total during checkout, this is a simple way to get "compensated" for the service fees we will
                            charge you.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="fee_name">Fee Name</label></th>
                    <td>
                        <input type="text" name="fee_name" id="fee_name" value="<?php echo get_option('ph_fee_name'); ?>" placeholder="Enter payment gateway fee name">
                        <p class="description">This is the name of the fee that will be displayed to the user during checkout</p>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="submit_store_details" id="submit" class="button button-primary"
                    value="Save Changes" /></p>
        </form>
    </div>
    <?php
}

if (isset($_POST['submit_store_details'])) {
    update_option('ph_store_name', sanitize_text_field($_POST['store_name']));
    update_option('ph_store_phone', sanitize_text_field($_POST['store_phone']));
    update_option('ph_store_email', sanitize_email($_POST['store_email']));
    update_option('ph_store_address', sanitize_textarea_field($_POST['store_address']));
    update_option('ph_payment_success_status', sanitize_text_field($_POST['payment_success_status']));
    update_option('ph_payment_method', sanitize_text_field($_POST['payment_method']));
    update_option('ph_payment_gateway_fees', sanitize_text_field($_POST['payment_gateway_fees']));
    update_option('ph_fee_name', sanitize_text_field($_POST['fee_name']));
    //ph_sps_username
    update_option('ph_sps_username', sanitize_text_field($_POST['sps_username']));
    //ph_sps_api_key
    update_option('ph_sps_api_key', sanitize_text_field($_POST['sps_api_key']));
    //show success message
    echo '<div class="updated notice is-dismissible"><p>Store information saved successfully.</p></div>';
}

//This function will send a request to SPS API
function Payhero_SendSPSRequest($url, $data)
{
    $args = array(
        'body' => json_encode($data),
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'cookies' => array(),
    );
    $response = wp_remote_post($url, $args);

    return $response;
}

//SPS account information management page
function payhero_sps_info_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
    <?php
    $cssurl = plugins_url('node_modules/bootstrap/dist/css/bootstrap.min.css', __FILE__);
    $jsurl = plugins_url('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', __FILE__);
    // wp_enqueue_script
    wp_enqueue_style('bootstrap', $cssurl);
    wp_enqueue_script('bootstrap', $jsurl);
    ?>
    <div class="wrap">
        <h1>SPS Account Management</h1>
        <div class="alert alert-primary" role="alert">
            <h4 class="alert-heading">SPS Account Management</h4>
            <p>You can use this section to manage or view details of your SPS account. You need to have provided your SPS
                username and API Key under Pay Hero Store Information menu.</p>
            <p>Here you will be able to top up your service wallet, monitor your wallet balance and also update or change
                your callback URL.</p>
            <hr>
            <p class="mb-0">You need to have an account on SPS, <a class="btn btn-dark btn-sm"
                    href="http://payherokenya.com/sps-app" target="_blank">Login/Sign Up Now</a></p>
        </div>
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home"
                    type="button" role="tab" aria-controls="nav-home" aria-selected="true"><span
                        class="dashicons dashicons-money-alt"></span> Service Wallet</button>
                <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                    type="button" role="tab" aria-controls="nav-profile" aria-selected="false"><span
                        class="dashicons dashicons-admin-users"></span> Account Details</button>
                <button class="nav-link" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-contact"
                    type="button" role="tab" aria-controls="nav-contact" aria-selected="false"><span
                        class="dashicons dashicons-admin-links"></span> Callback URL</button>
                <button class="nav-link" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-pchannels"
                    type="button" role="tab" aria-controls="nav-contact" aria-selected="false"><span
                        class="dashicons dashicons-tickets-alt"></span> Payment Channels</button>
                <button class="nav-link" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-tcharges"
                    type="button" role="tab" aria-controls="nav-contact" aria-selected="false"><span
                        class="dashicons dashicons-editor-ul"></span> Transaction Charges</button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <?php
                $bal_request = Payhero_SendSPSRequest(
                    "https://payherokenya.com/sps/portal/app/balance",
                    array(
                        "username" => get_option('ph_sps_username'),
                        "api_key" => get_option('ph_sps_api_key')
                    )
                );
                $decoded_bal_request = json_decode($bal_request['body'], true);
                $Currency = $decoded_bal_request['response']['Currency'];
                $Service_Wallet_Balance = number_format($decoded_bal_request['response']['Service_Wallet_Balance'], 2);
                $Status = $decoded_bal_request['response']['Status'];
                $Message = $decoded_bal_request['response']['Message'];
                if ($Status == 'Sucess') {
                    echo '<br><h3>Service Wallet Balance <span class="badge bg-success">' . esc_html($Currency) . ' ' . esc_html($Service_Wallet_Balance) . '</span></h3>';
                } else {
                    echo '<div class="alert alert-danger d-flex align-items-center" role="alert">
                <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg>
                <div>
                    ' . esc_html($Message) . '
                </div>
              </div>';
                }
                ?>
                <!-- Top up service wallet modal -->
                <!-- Button trigger modal -->
                <?php if ($Status == 'Sucess') { ?><button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#exampleModal">
                        Top Up Service Wallet
                    </button>
                <?php } ?>

                <!-- Modal -->
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Top Up From MPESA</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- show form with phone number input:required and amount input:required -->
                                <form action="" method="post">
                                    <input type="hidden" name="username"
                                        value="<?php echo esc_html(get_option('ph_sps_username')); ?>">
                                    <input type="hidden" name="api_key"
                                        value="<?php echo esc_html(get_option('ph_sps_api_key')); ?>">
                                    <div class="mb-3">
                                        <label for="phone_number" class="form-label">MPESA Phone Number</label>
                                        <input type="text" class="form-control" id="phone_number" name="phone_number"
                                            placeholder="Enter MPESA Phone Number" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount</label>
                                        <input type="number" class="form-control" id="amount" name="amount"
                                            placeholder="Enter Amount" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary" name="top-up">Proceed..</button>
                                    <p>After pressing the Proceed button, you will be redirected to a payment page, to
                                        complete transaction. If successfull you will be redirected back here.</p>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <!-- Create a table to hold the following fields: business_name
                registration_number
                contact_phone
                contact_email
                account_number
                callback_url
                status
                products -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead>
                            <tr>
                                <th scope="col">Business Name</th>
                                <th scope="col">Registration Number</th>
                                <th scope="col">Contact Phone</th>
                                <th scope="col">Contact Email</th>
                                <th scope="col">Account Number</th>
                                <th scope="col">Callback URL</th>
                                <th scope="col">Status</th>
                                <th scope="col">Products</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $account_details_request = Payhero_SendSPSRequest(
                                "https://payherokenya.com/sps/portal/app/app_table_data.php",
                                array(
                                    "username" => get_option('ph_sps_username'),
                                    "table_name" => "applications"
                                )
                            );
                            $decoded_account_details_request = json_decode($account_details_request['body'], true);
                            $records = $decoded_account_details_request['response']['Records'];
                            if (empty($records)) {
                                $callback_url = '';
                                echo '<tr><td colspan="8">No records found, please provide your SPS API Key and Username under payhero store information.</td></tr>';
                            } else {
                                $business_name = $decoded_account_details_request['response']['Records'][0]['business_name'];
                                $registration_number = $decoded_account_details_request['response']['Records'][0]['registration_number'];
                                $contact_phone = $decoded_account_details_request['response']['Records'][0]['contact_phone'];
                                $contact_email = $decoded_account_details_request['response']['Records'][0]['contact_email'];
                                $account_number = $decoded_account_details_request['response']['Records'][0]['account_number'];
                                $callback_url = $decoded_account_details_request['response']['Records'][0]['callback_url'];
                                $status = $decoded_account_details_request['response']['Records'][0]['status'];
                                $products = $decoded_account_details_request['response']['Records'][0]['products'];
                                echo '<tr>
                    <td>' . esc_html($business_name) . '</td>
                    <td>' . esc_html($registration_number) . '</td>
                    <td>' . esc_html($contact_phone) . '</td>
                    <td>' . esc_html($contact_email) . '</td>
                    <td>' . esc_html($account_number) . '</td>
                    <td>' . esc_url($callback_url) . '</td>
                    <td>' . esc_html($status) . '</td>
                    <td>' . esc_html($products) . '</td>
                    </tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                <!-- create a form to hold callback URL from the table data -->

                <form action="" method="post">
                    <input type="hidden" name="username" value="<?php echo esc_html(get_option('ph_sps_username')); ?>">
                    <input type="hidden" name="api_key" value="<?php echo esc_html(get_option('ph_sps_api_key')); ?>">
                    <div class="mb-3">
                        <!-- show an alert about the cllback URL -->
                        <div class="alert alert-info"> Your callback should be: <b>{your wordpress site
                                URL}</b>/wp-json/payhero/v1/receive-callback <br>replace <b>{your wordpress site URL}</b>
                            with an actual URL eg https://myshop.com . This will enable you receive payment notifications on
                            your WooCommerce store and update orders automatically.</div>
                        <label for="callback_url" class="form-label">Callback URL</label>
                        <input type="text" class="form-control" id="callback_url" name="callback_url"
                            placeholder="Enter Callback URL" required value="<?php echo esc_url($callback_url); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" name="update_callback_url">Update Callback URL</button>
                </form>
            </div>
            <div class="tab-pane fade" id="nav-pchannels" role="tabpanel" aria-labelledby="nav-pchannels-tab">
                <br>
                <div class="alert alert-dark" role="alert">
                    You can see your linked payment channels. This enable you to receive payments directly to your linked
                    channel like bank account, MPESA Paybill or till number. You can register new payment channels easily.
                </div>
                <!-- create two tabs: Payment channels and add payment channel -->
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                            aria-selected="true">My Payment Channels</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile"
                            aria-selected="false">Add Payment Channel</button>
                    </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#ID</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Short Code</th>
                                        <th scope="col">Account</th>
                                        <th scope="col">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pchannelsrequest = Payhero_SendSPSRequest(
                                        "https://payherokenya.com/sps/portal/app/app_table_data.php",
                                        array(
                                            "username" => get_option('ph_sps_username'),
                                            "table_name" => "external_c2b"
                                        )
                                    );
                                    $decode_pchannelsrequest = json_decode($pchannelsrequest['body'], true);
                                    $records = $decode_pchannelsrequest['response']['Records'];
                                    if (empty($records)) {
                                        echo '<tr>
                                <td scope="row">No Payment Channels Found</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                </tr>';
                                    } else {
                                        foreach ($records as $record) {
                                            $id = $record['id'];
                                            $type = $record['type'];
                                            $shortcode = $record['short_code'];
                                            $account = $record['account_number'];
                                            $description = $record['description'];
                                            echo '<tr>
                                <td scope="row">' . esc_html($id) . '</td>
                                <td>' . esc_html($type) . '</td>
                                <td>' . esc_html($shortcode) . '</td>
                                <td>' . esc_html($account) . '</td>
                                <td>' . esc_html($description) . '</td>
                                </tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-regbank" type="button" role="tab" aria-controls="nav-home"
                                    aria-selected="true">Register Bank</button>
                                <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-regpt" type="button" role="tab" aria-controls="nav-profile"
                                    aria-selected="false">Register Paybill/Till</button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-regbank" role="tabpanel"
                                aria-labelledby="nav-home-tab">
                                <br>
                                <form action="" method="post">
                                    <label for="bank" class="form-label">Select Your Bank: </label>
                                    <div class="mb-3">
                                        <!-- select list for banks get data from: https://payherokenya.com/sps/portal/app/bank_list-->
                                        <?php
                                        $getbanks = wp_remote_get('https://payherokenya.com/sps/portal/app/bank_list');
                                        $decode_getbanks = json_decode($getbanks['body'], true);
                                        $bank_details = $decode_getbanks['response']['bank_details'];
                                        //create a select list for banks us value bank_id and label bank_name
                                        echo '<select class="form-select form-control" aria-label="Select Bank" name="bank_id" required>';
                                        foreach ($bank_details as $bank) {
                                            $bank_id = $bank['bank_id'];
                                            $bank_name = $bank['bank_name'];
                                            echo '<option value="' . esc_html($bank_id) . '">' . esc_html($bank_name) . '</option>';
                                        }
                                        echo '</select>';
                                        ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="account" class="form-label">Bank Account</label>
                                        <input type="text" class="form-control" id="bank_account_number"
                                            name="bank_account_number" placeholder="Enter Your Bank Account Number"
                                            required>
                                    </div>
                                    <!-- submit button -->
                                    <p class="submit"><input type="submit" name="register_bank" id="submit"
                                            class="button button-primary" value="Register Bank" /></p>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="nav-regpt" role="tabpanel" aria-labelledby="nav-profile-tab">
                                <br>
                                <form action="" method="post">
                                    <!-- create select channel type: Paybill or Till -->
                                    <label for="channel" class="form-label">Select Your Channel: </label>
                                    <select class="form-select form-control" aria-label="Select Channel" name="type"
                                        required>
                                        <option value="Paybill">Paybill</option>
                                        <option value="Till">Till</option>
                                    </select>
                                    <label for="scode" class="form-label">Shortcode</label>
                                    <input type="text" class="form-control" id="short_code" name="short_code"
                                        placeholder="Enter Paybill/Till Number" required>
                                    <label for="account" class="form-label">Account Number (Optional)</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number"
                                        placeholder="Enter Account Number">
                                    <p class="submit"><input type="submit" name="register_ptill" id="submit"
                                            class="button button-primary" value="Register Paybill/Till" /></p>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="tab-pane fade" id="nav-tcharges" role="tabpanel" aria-labelledby="nav-tcharges-tab">
                <br>
                <h4>Payment Transaction Charges</h4>
                <p>You will be charged this amount from your service wallet, ensure you always have sufficient funds.</p>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Amount From</th>
                            <th scope="col">Amount To</th>
                            <th scope="col">Transaction Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tfeesrequest = Payhero_SendSPSRequest(
                            "https://payherokenya.com/sps/portal/app/app_table_data.php",
                            array(
                                "username" => "admin",
                                "table_name" => "mtb_t_cost",
                                "page_number" => "1",
                                "results_per_page" => "19"
                            )
                        );
                        $decode_tfees = json_decode($tfeesrequest['body'], true);
                        $records = $decode_tfees['response']['Records'];
                        foreach ($records as $record) {
                            $amount_from = $record['amount_from'];
                            $amount_to = $record['amount_to'];
                            $transaction_fee = $record['transaction_fee'];
                            echo '<tr>
                                <td scope="row">' . number_format($amount_from) . '</td>
                                <td>' . number_format($amount_to) . '</td>
                                <td>' . esc_html($transaction_fee) . '</td>
                                </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

//CUSTOM/FORM ACTIONS BELOW
if (isset($_POST['top-up'])) {
    $phone_number = sanitize_text_field($_POST['phone_number']);
    $amount = is_numeric($_POST['amount']) ? $_POST['amount'] : 0;
    $username = sanitize_text_field($_POST['username']);
    $reference = sanitize_text_field("api." . $username);
    $channel_id = "17";
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        $url = "https://";
    else
        $url = "http://";
    // Append the host(domain name, ip) to the URL.   
    $url .= sanitize_url($_SERVER['HTTP_HOST']);

    // Append the requested resource location to the URL   
    $url .= sanitize_url($_SERVER['REQUEST_URI']);
    //redirect the user to the following URL: https://payherokenya.com/sps/portal/app/lipwa/3575
    header('Location: https://payherokenya.com/sps/portal/app/lipwa/3575?reference=' . $reference . '&amount=' . $amount . '&phone=' . $phone_number . '&return_url=' . $url . '&channel_id=' . $channel_id . '');
    exit;
}

if (isset($_POST['update_callback_url'])) {
    $callback_url = sanitize_text_field($_POST['callback_url']);
    $username = sanitize_text_field($_POST['username']);
    $api_key = sanitize_text_field($_POST['api_key']);
    $update_callback_url_request = Payhero_SendSPSRequest(
        "https://payherokenya.com/sps/portal/app/register_callback",
        array(
            "username" => $username,
            "api_key" => $api_key,
            "callback_url" => $callback_url
        )
    );
    $decoded_update_callback_url_request = json_decode($update_callback_url_request['body'], true);
    $Status = $decoded_update_callback_url_request['response']['Status'];
    $message = $decoded_update_callback_url_request['response']['Message'];
    if ($Status == "Sucess") {
        echo ' <div class="updated notice notice-success is-dismissible">
        ' . esc_html($message) . '
      </div>';
    } else {
        echo ' <div class="notice notice-error  is-dismissible">
        ' . esc_html($message) . '
        </div>';
    }

}

if (isset($_POST['register_bank'])) {
    $bank_id = is_numeric($_POST['bank_id']) ? $_POST['bank_id'] : 0;
    $bank_account_number = sanitize_text_field($_POST['bank_account_number']);
    $register_bank = Payhero_SendSPSRequest(
        "https://payherokenya.com/sps/portal/app/register_channel",
        array(
            "username" => get_option('ph_sps_username'),
            "api_key" => get_option('ph_sps_api_key'),
            "type" => "Bank",
            "short_code" => "",
            "bank_id" => $bank_id,
            "account_number" => $bank_account_number,
        )
    );
    $decoded_register_bank = json_decode($register_bank['body'], true);
    $Status = $decoded_register_bank['response']['Status'];
    $message = $decoded_register_bank['response']['Message'];
    if ($Status == "Sucess") {
        echo ' <div class="updated notice notice-success is-dismissible">
        ' . esc_html($message) . '
      </div>';
    } else {
        echo ' <div class="notice notice-error  is-dismissible">
        ' . esc_html($message) . '
        </div>';
    }
}
if (isset($_POST['register_ptill'])) {
    $type = sanitize_text_field($_POST['type']);
    $short_code = sanitize_text_field($_POST['short_code']);
    $account_number = sanitize_text_field($_POST['account_number']);
    $register = Payhero_SendSPSRequest(
        "https://payherokenya.com/sps/portal/app/register_channel",
        array(
            "username" => get_option('ph_sps_username'),
            "api_key" => get_option('ph_sps_api_key'),
            "type" => $type,
            "short_code" => $short_code,
            "bank_id" => "",
            "account_number" => $account_number,
        )
    );
    $decoded_register = json_decode($register['body'], true);
    $Status = $decoded_register['response']['Status'];
    $message = $decoded_register['response']['Message'];
    if ($Status == "Sucess") {
        echo ' <div class="updated notice notice-success is-dismissible">
        ' . esc_html($message) . '
      </div>';
    } else {
        echo ' <div class="notice notice-error  is-dismissible">
        ' . esc_html($message) . '
        </div>';
    }
}

global $pagenow;
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
if (!is_plugin_active('payhero-custom-payment-gateway/payhero-custom-payment-gateway.php')) {
    //show an alert that plugin is not active:index.php/plugins.php/admin.php
    if ($pagenow == 'index.php' || $pagenow == 'plugins.php' || $pagenow == 'admin.php') {
        echo '<div class="notice notice-info is-dismissible"> <p><b>PayHero Custom Payment Gateway Creator Plugin</b> is not active or installed. Please download and activate it. <a href="https://drive.google.com/file/d/1KBXyAf1jD_x0wPfVt0opvApIJhsbB4iS/view?usp=share_link" target="_blank">Use This Link</a></p></div>';
    }
}

if (empty(get_option('ph_sps_username')) || empty(get_option('ph_sps_api_key'))) {
    //show an alert that plugin is not active:index.php/plugins.php/admin.php
    if ($pagenow == 'index.php' || $pagenow == 'plugins.php' || $pagenow == 'admin.php') {
        echo '<div class="notice notice-error is-dismissible"> <p>Please enter your PayHero SPS Username and API Key in the <a href="admin.php?page=ph_store-info">Settings</a> page.</p></div>';
    }
}

// Add cart fees if applicable
if (!function_exists('payhero_wc_add_cart_fees_by_payment_gateway')) {
    /**
     * payhero_wc_add_cart_fees_by_payment_gateway.
     */
    function payhero_wc_add_cart_fees_by_payment_gateway($cart)
    {
        $ph_payment_method = esc_html(get_option('ph_payment_method'));
        $add_gateway_fees = esc_html(get_option('ph_payment_gateway_fees'));
        $cart_contents_total = $cart->get_cart_contents_total();
        // Getting current chosen payment gateway
        $chosen_payment_method = false;
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (isset(WC()->session->chosen_payment_method)) {
            $chosen_payment_method = WC()->session->chosen_payment_method;
        } elseif (!empty($_REQUEST['payment_method'])) {
            $chosen_payment_method = sanitize_key($_REQUEST['payment_method']);
        } elseif ('' != ($default_gateway = get_option('woocommerce_default_gateway'))) {
            $chosen_payment_method = $default_gateway;
        } elseif (!empty($available_gateways)) {
            $chosen_payment_method = current(array_keys($available_gateways));
        }
        if (!isset($available_gateways[$chosen_payment_method])) {
            $chosen_payment_method = false;
        }
        // Applying fee (maybe)
        if ($chosen_payment_method == $ph_payment_method && $add_gateway_fees == 'yes') {
            $tfeesrequest = Payhero_SendSPSRequest(
                "https://payherokenya.com/sps/portal/app/app_table_data.php",
                array(
                    "username" => "admin",
                    "table_name" => "mtb_t_cost",
                    "page_number" => "1",
                    "results_per_page" => "19"
                )
            );
            $decode_tfees = json_decode($tfeesrequest['body'], true);
            $records = $decode_tfees['response']['Records'];
            foreach ($records as $record) {
                $amount_from = $record['amount_from'];
                $amount_to = $record['amount_to'];
                $transaction_fee = $record['transaction_fee'];
                if ($cart_contents_total >= $amount_from && $cart_contents_total <= $amount_to) {
                    $fee = $transaction_fee;
                }
            }
            $name = empty(esc_html(get_option('ph_fee_name')))?'Payment Gateway Fee (Pay Hero)':esc_html(get_option('ph_fee_name'));
            $amount = $fee;
            $taxable = true;
            $tax_class = '';
            $cart->add_fee($name, $amount, $taxable, $tax_class);
        }
    }

}