<?php
class Cashfree_Checkout
{
    /** Get config detail from custom post/page
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        // initializing our object with all the setting variables
        $this->appID = get_option('cf_app_id');
        $this->secretKey = get_option('cf_secret_key');
        $this->paymentMode = get_option('cf_payment_mode');
        $this->url = 'https://test.cashfree.com';

        if ($this->paymentMode == 'prod') {
            $this->url = 'https://api.cashfree.com';
        }
    }

    /**
     * Process payment
     *
     * @return void
     */
    public function process()
    {
        $postArgs = filter_input_array(INPUT_POST);

        $pageID = $postArgs['pageID'];

        $metaData = get_post_meta($pageID);

        if (!defined('CASHFREE_RETURN_URL')) {
            define('CASHFREE_RETURN_URL', esc_url(add_query_arg('act', 'ret', get_permalink($pageID))));
        }

        if (!defined('CASHFREE_NOTIFY_URL')) {
            define('CASHFREE_NOTIFY_URL', esc_url(add_query_arg('act', 'notify', get_permalink($pageID))));
        }

        $cfRequest = array();
        if (empty($this->appID) || empty($this->secretKey)) {
            echo 'Before making payment please check whether App-ID/Secret-Key is empty or not';
            exit();

        }

        $cfRequest["appId"] = $this->appID;
        $cfRequest["secretKey"] = $this->secretKey;
        $cfRequest["orderId"] = "CFB_" . time();
        $cfRequest["orderAmount"] = $metaData['orderAmount'][0];
        $cfRequest["orderCurrency"] = 'INR';
        $cfRequest["customerPhone"] = $postArgs['customerPhone'];
        $cfRequest["customerName"] = $postArgs['customerName'];
        $cfRequest["customerEmail"] = $postArgs['customerEmail'];
        $cfRequest["source"] = "woocommerce";
        $cfRequest["returnUrl"] = CASHFREE_RETURN_URL;
        $cfRequest["notifyUrl"] = CASHFREE_NOTIFY_URL;
        $timeout = 30;
        
        try {
            $apiEndpoint = $this->url . "/api/v1/order/create";
            $postBody = array("body" => $cfRequest, "timeout" => $timeout);
            $cf_result = wp_remote_retrieve_body(wp_remote_post(esc_url($apiEndpoint), $postBody));
            error_log($cf_result);
            $jsonResponse = json_decode($cf_result);
            if ($jsonResponse->{'status'} == "OK") {
                $paymentLink = $jsonResponse->{"paymentLink"};
                header("Location: $paymentLink", true);
            } else {

                echo $jsonResponse->{'reason'};
            }
        } catch (Exception $e) {
            echo 'Cashfree Error: ' . $e->getMessage();
        }

        exit;
    }
}
