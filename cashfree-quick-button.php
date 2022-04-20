<?php
/**
 * Plugin Name: Cashfree Quick Button
 * Plugin URI: https://www.cashfree.com
 * Description: Cashfree Button plugin for Wordpress by Cashfree.
 * Version: 2.1.0
 * Stable tag: 2.1.0
 * Author: Cashfree Dev
 * Author URI: techsupport@gocashfree.com
 * Wordpress requires at least: 4.2
 * Wordpress tested up to: 5.6.1
 */

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/cashfree-checkout.php';
require_once __DIR__ . '/includes/cashfree-payments-gateway.php';
require_once __DIR__ . '/includes/cashfree-functions.php';

add_action('plugins_loaded', 'cashfreeQuickPaymentInit', 0);
add_action( 'wp_enqueue_scripts','cashfreeInitializeJquery');

//Add external css and js file for modal
function cashfreeInitializeJquery() {
    wp_enqueue_script('jquery');
}

/**
 * Initiate quick payment button
 *
 * @return void
 */
function cashfreeQuickPaymentInit()
{

    if ( is_user_logged_in() ) {
        add_action('admin_post_cashfree_checkout_form', 'cashfreeCheckoutForm', 10, 0);
    } else {
        add_action('admin_post_nopriv_cashfree_checkout_form', 'cashfreeCheckoutForm', 10, 0);
    }
    // Adding constants
    if (!defined('CASHFREE_BASE_NAME')) {
        define('CASHFREE_BASE_NAME', plugin_basename(__FILE__));
    }

    // Adding constants
    if (!defined('QB_CASHFREE_DIR_PATH')) {
        define('QB_CASHFREE_DIR_PATH', plugin_dir_url( __FILE__ ));
    }

    if (!defined('CASHFREE_CHECKOUT_URL')) {
        define('CASHFREE_CHECKOUT_URL', esc_url(admin_url('admin-post.php')) . '?action=cashfree_checkout_form');
    }

    // The main plugin class
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
            $this->icon = plugins_url('images/logo.png', __FILE__);
            $this->has_fields = false;

            // initializing our object with all the setting variables
            $this->enable           = get_option('cf_enable');
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
            $cfGateway = new Cashfree_Gateway();
            $response = array(
                            "txMsg"   => "",
                            'txStatus'  => ""
                        );

            if (isset($_GET['token_param']) && isset($_GET['pageId'])) {

                $cfGateway->checkout_process();
            }

            if (isset($_GET['act']) && $_GET['act'] == 'dismiss') {

                $this->message = '<br><div class="alert alert-danger">Please try again.</div>';
                $response = array(
                    'txMsg'          => $this->message,
                    'txStatus'         => 'PENDING'
                );
            }

            if (isset($_GET['act']) && $_GET['act'] == 'capture') {

                $response = $cfGateway->capture();
               
            }

            if (isset($_GET['act']) && $_GET['act'] == 'cancel') {

                $response = $cfGateway->capture();
               
            }

            if (isset($_GET['act']) && $_GET['act'] == 'error') {

                $this->message = '<br><div class="alert alert-danger">Sorry that email address or phone number is not right. Please fill correct email/phone fields.</div>';
                $response = array(
                    'txMsg'          => $this->message,
                    'txStatus'         => 'PENDING'
                );
               
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

            if (isset($this->appID) && isset($this->secretKey) && $orderAmount != null && $this->enable == 'enable') {
                $buttonHtml = file_get_contents(__DIR__ . '/templates/checkout.phtml');
                if($response['txStatus'] != 'SUCCESS') {
                    $cfButton = '<button id="btn_cashfree" type="button" class="btn btn-primary" data-toggle="modal" data-target="#cfCheckoutModal">
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

    }

    return new Cashfree_Quick_Button();
}

/**
 * Generate checkout form to redirect on payment page
 *
 * @return void
 */
function cashfreeCheckoutForm()
{
    $cfCheckout = new Cashfree_Checkout();

    $cfCheckout->process();
}