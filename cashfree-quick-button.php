<?php
/**
 * Plugin Name: Cashfree Quick Button
 * Plugin URI: https://www.cashfree.com
 * Description: Cashfree Button plugin for Wordpress by Cashfree.
 * Version: 1.0.0
 * Author: Cashfree Dev
 * Author URI: techsupport@gocashfree.com
 * Wordpress requires at least: 4.2
 * Wordpress tested up to: 5.6.1
 */

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/cashfree-checkout.php';

add_action('plugins_loaded', 'quickPaymentInit', 0);
add_action( 'wp_enqueue_scripts','initialize_bootsrtap');

//Add external css and js file for modal
function initialize_bootsrtap() {
    wp_enqueue_script('jquery');
    wp_register_style('cashfree_bootstrap_css', plugins_url('assets/css/bootstrap.min.css',__FILE__ ));
    wp_enqueue_style('cashfree_bootstrap_css');
    wp_register_script( 'cashfree_bootstrap_js', plugins_url('assets/js/bootstrap.min.js',__FILE__ ));
    wp_enqueue_script('cashfree_bootstrap_js');
}

/**
 * Initiate quick payment button
 *
 * @return void
 */
function quickPaymentInit()
{
    if ( is_user_logged_in() ) {
        add_action('admin_post_cashfree_checkout_form', 'checkout_form', 10, 0);
    } else {
        add_action('admin_post_nopriv_cashfree_checkout_form', 'checkout_form', 10, 0);
    }
    // Adding constants
    if (!defined('CASHFREE_BASE_NAME')) {
        define('CASHFREE_BASE_NAME', plugin_basename(__FILE__));
    }

    if (!defined('CASHFREE_CHECKOUT_URL')) {
        define('CASHFREE_CHECKOUT_URL', esc_url(admin_url('admin-post.php')) . '?action=cashfree_checkout_form');
    }

    // The main plug in class
    class Cashfree_Quick_Button
    {
        /**
         * __construct
         * Initiate config data and
         *
         * @return void
         */
        public function __construct()
        {
            $this->id = 'cashfree';
            $this->method = 'Cashfree';
            $this->icon = plugins_url('assets/images/logo.png', __FILE__);
            $this->has_fields = false;

            // initializing our object with all the setting variables
            $this->title            = get_option('cf_title');
            $this->appID            = get_option('cf_app_id');
            $this->secretKey        = get_option('cf_secret_key');
            $this->paymentMode      = get_option('cf_payment_mode');
            $this->successMessage   = get_option('cf_success_message');

            $this->message = "";

            // Creates the settings page
            $settings = new Cashfree_Settings();

            // Creates a customizable tag for us to place our pay button anywhere using [CFPB]
            add_shortcode('CFPB', array($this, 'checkout'));

            // Adding links on the plugin page for docs, support and settings
            add_filter('plugin_action_links_' . CASHFREE_BASE_NAME, array($this, 'pluginLinks'));
        }

        /**
         * Creating the settings link from the plug ins page
         **/
        function pluginLinks($links)
        {
            $pluginLinks = array(
                'settings' => '<a href="' . esc_url(admin_url('admin.php?page=cashfree')) . '">Settings</a>',
                'docs' => '<a href="https://github.com/cashfree/cashfree-payment-button">Docs</a>',
                'support' => '<a href="http://knowledgebase.cashfree.com/support/home">Support</a>',
            );

            $links = array_merge($links, $pluginLinks);

            return $links;
        }

        /**
         * This method is used to generate the pay button using wordpress shortcode [CFPB]
         **/
        function checkout()
        {
            $response = array(
                            "txMsg"   => "",
                            'txStatus'  => ""
                        );
            if (isset($_GET['act']) && $_GET['act'] == 'ret') {
                $postArgs = filter_input_array(INPUT_POST);

                $response = $this->cashfreePaymentResponse($postArgs, $response);
            }
            $html = $this->generateCashfreePaymentButton($response);
            
            
            return $html;
        }

        /**
         * Generates the Cashfree Payment Button
         **/
        function generateCashfreePaymentButton($response)
        {
            $pageID = get_the_ID();

            $metaData = get_post_meta($pageID);

            $orderAmount = round($metaData['orderAmount'][0], 2);

            $cfButton = "";

            $title = $metaData['title'][0];

            $description = $metaData['description'][0];

            if (isset($this->appID) && isset($this->secretKey) && $orderAmount != null) {
                $buttonHtml = file_get_contents(__DIR__ . '/templates/checkout.phtml');
                if($response['txStatus'] != 'SUCCESS') {
                    $cfButton = '<button id="btn-cashfree" type="button" class="btn btn-primary" data-toggle="modal" data-target="#cfCheckoutModal">
                    ' . $this->title . '
                    </button>';
                }
                // Replacing placeholders in the HTML with PHP variables for the form to be handled correctly
                $keys = array("#redirectUrl#", "#pageID#", "#cfButton#", "#icon#", "#title#", "#description#", "#orderAmount#", "#txMsg#");

                $values = array(CASHFREE_CHECKOUT_URL, $pageID, $cfButton, $this->icon, $title, $description, $orderAmount, $response['txMsg']);

                $html = str_replace($keys, $values, $buttonHtml);

                return $html;
            }

            return null;
        }

        /**
         * Check response of payment gateway and redirect accordingly
         *
         * @param  mixed $postArgs
         * @return void
         */
        function cashfreePaymentResponse($postArgs, $response)
        {
            if (!empty($postArgs)) {
                $txMsg = $postArgs['txMsg'];
                if ($postArgs['txStatus'] == 'SUCCESS') {
                    $amount = $postArgs['orderAmount'];
                    $data = "{$postArgs['orderId']}{$postArgs['orderAmount']}{$postArgs['referenceId']}{$postArgs['txStatus']}{$postArgs['paymentMode']}{$txMsg}{$postArgs['txTime']}";
                    $hash_hmac = hash_hmac('sha256', $data, $this->secretKey, true);
                    $computedSignature = base64_encode($hash_hmac);
                    if ($postArgs["signature"] != $computedSignature) {
                        $this->message = '<br><div class="alert alert-danger">Thank you for being with us. However, the payment failed because of some invalidate payment</div>';
                    } else {
                        $this->message = '<br><div class="alert alert-success">' . $this->successMessage
                        . "<br>" . "Order ID : " . esc_html($postArgs['orderId']). "<br>" . "Transaction ID : " . esc_html($postArgs['referenceId']) . "<br>" . "Order Amount: ₹$amount . </div>";
                    }
                } elseif ($postArgs['txStatus'] == 'PENDING') {
                    $this->message = '<br><div class="alert alert-warning">Thank you for being with us. However, the payment pending.</div>';
                } else {
                    $this->message = '<br><div class="alert alert-danger">Thank you for being with us. However, the payment failed.<br>' . $txMsg . '</div>';
                }

                $response = array(
                    'txMsg'          => $this->message,
                    'txStatus'         => $postArgs['txStatus']
                );
        
                return $response;
            }
        }
    }

    return new Cashfree_Quick_Button();
}

/**
 * Generate checkout form to redirect on payment page
 *
 * @return void
 */
function checkout_form()
{
    $cfCheckout = new Cashfree_Checkout();

    $cfCheckout->process();
}
