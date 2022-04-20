<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    add_action( 'plugins_loaded', array ( 'Cashfree_Gateway', 'init' ));

    if (!class_exists('Cashfree_Gateway')){

    class Cashfree_Gateway
    {
        public static function init() {
            $class = __CLASS__;
            new $class;
        }
        
        /** Get config detail from custom post/page
         * __construct
         *
         * @return void
         */
        public function __construct()
        {
            // initializing our object with all the setting variables
            $this->appID            = get_option('cf_app_id');
            $this->secretKey        = get_option('cf_secret_key');
            $this->paymentMode      = get_option('cf_payment_mode');
            $this->successMessage   = get_option('cf_success_message');

            $this->url = 'https://sandbox.cashfree.com/pg';

            if ($this->paymentMode == 'production') {
                $this->url = 'https://api.cashfree.com/pg';
            }
        
            require_once QB_CASHFREE_DIR_PATH . '/includes/cashfree-functions.php';
            require_once QB_CASHFREE_DIR_PATH . '/cashfree-quick-button.php';
            add_action( 'wp_enqueue_scripts', array( $this, 'load_checkout_script' ) );
        
        }

        /**
         * Register/queue frontend scripts.
         */
        public function load_checkout_script() {

            $pageID = $_GET['pageId'];

            if (!defined('CASHFREE_CAPTURE_URL')) {
                define('CASHFREE_CAPTURE_URL', esc_url(add_query_arg('act', 'capture', get_permalink($pageID))));
            }
            
            if (!defined('CASHFREE_CANCEL_URL')) {
                define('CASHFREE_CANCEL_URL', esc_url(add_query_arg('act', 'cancel', get_permalink($pageID))));
            }
        
            if (!defined('CASHFREE_DISMISS_URL')) {
                define('CASHFREE_DISMISS_URL', esc_url(add_query_arg('act', 'dismiss', get_permalink($pageID))));
            }

            if ( !empty( $_GET['token_param'] ) && !empty( $_GET['pageId'] ) ) {
                cb_cashfree_js( $this->appID );

                cb_cashfree_script(
                    'cb-cashfree-checkout',
                    array( 
                        'token' => wc_clean( wp_unslash( $_GET['token_param'] ) ),
                        'environment' 	=> $this->paymentMode,
                        'capture_url' 	=> CASHFREE_CAPTURE_URL,
                        'cancel_url' 	=> CASHFREE_CANCEL_URL, 
                        'dismiss_url' 	=> CASHFREE_DISMISS_URL
                    )
                );
            }
        }

        /**
         * Process checkout
         *
         * @return void
         */
        public function checkout_process()
        {
            add_action( 'admin_enqueue_scripts', 'load_checkout_script' );
        }

        /**
         * Process payment
         *
         * @return void
         */
        public function capture()
        {
            if($_POST['order_status'] === 'PAID') {
                $getOrderUrl = $this->url."/orders/".$_POST['orderId']."/payments";
                $args = array(
                    'timeout'     => '30',
                    'headers'     => array(
                        'x-api-version' 	=> 	'2021-05-21',
                        'x-client-id' 		=> 	$this->appID,
                        'x-client-secret'	=>  $this->secretKey,
                    ),
                );
        
                $response = wp_remote_get( $getOrderUrl, $args );
                $http_code = wp_remote_retrieve_response_code( $response );
                $body     = json_decode(wp_remote_retrieve_body( $response ));
        
                if($http_code == 200) {
                    if($body[0]->payment_status === 'SUCCESS') {
                        $this->message = '<br><div class="alert alert-success">' . $this->successMessage
                        . "<br>" . "Order ID : " . esc_html($body[0]->order_id). "<br>" . "Transaction ID : " . esc_html($body[0]->cf_payment_id) . "<br>" . "Order Amount: ₹".$body[0]->order_amount . "</div>";
                        $response = array(
                            'txMsg'     => $this->message,
                            'txStatus'  => $body[0]->payment_status
                        );
                    } else {
                        $this->message = '<br><div class="alert alert-danger">Thank you for being with us. However, the payment has been '.$body[0]->payment_status.'.<br>' . $body[0]->payment_message . '</div>';
                        $response = array(
                            'txMsg'     => $this->message,
                            'txStatus'  => $body[0]->payment_status
                        );
                    }
                } else {
                    $this->message = '<br><div class="alert alert-danger">Thank you for being with us. However, the payment has been '.$body[0]->payment_status.'.<br>' . $body[0]->payment_message . '</div>';
                        $response = array(
                            'txMsg'     => $this->message,
                            'txStatus'  => $body[0]->payment_status
                        );
                }
            } else {
                $this->message = '<br><div class="alert alert-danger">Thank you for being with us. However, the payment has been '.$_POST['order_status'].'.<br>' . $_POST['transaction_msg'] . '</div>';
                $response = array(
                    'txMsg'     => $this->message,
                    'txStatus'  => $_POST['transaction_msg']
                );
            }
            return $response;
        }
    }
}