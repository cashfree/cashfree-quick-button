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
        $this->url = 'https://sandbox.cashfree.com/pg';

        if ($this->paymentMode == 'production') {
            $this->url = 'https://api.cashfree.com/pg';
        }
        require_once QB_CASHFREE_DIR_PATH . '/includes/cashfree-functions.php';
        require_once QB_CASHFREE_DIR_PATH . '/cashfree-quick-button.php';
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

        if (!defined('CASHFREE_RETURN_URL')) {
            define('CASHFREE_RETURN_URL', add_query_arg(array('order_id' => "{order_id}", 'order_token' => "{order_token}"), get_permalink($pageID)));
        }
    
        if (!defined('CASHFREE_NOTIFY_URL')) {
            define('CASHFREE_NOTIFY_URL', esc_url(add_query_arg('act', 'notify', get_permalink($pageID))));
        }

        $metaData = get_post_meta($pageID);

        if (empty($this->appID) || empty($this->secretKey)) {
            echo 'Before making payment please check whether App-ID/Secret-Key is empty or not';
            exit();

        }

        $requestParams = array(
			"customer_details"      => array(
				"customer_id"       => "cashfree-payments-buttons-" . time(),
				"customer_email"    => $postArgs['customerEmail'],
				"customer_phone"    => $postArgs['customerPhone']
			),
			"order_id"          =>  "CFB_" . time(),
			"order_amount"      => $metaData['orderAmount'][0],
			"order_currency"    => "INR",
			"order_note"        => "cashfree-payments-buttons",
		);

        $curlPostfield = json_encode($requestParams);

        try{
			$result = $this->curlPostRequest($this->url."/orders", $curlPostfield);

			$ret = add_query_arg(array('token_param' => $result->order_token, 'pageId' => $pageID), get_permalink($pageID));
            header("Location: $ret", true);

		} catch(Exception $e) {
            $ret = add_query_arg(array('act' => 'error', 'pageId' => $pageID), get_permalink($pageID));
			header("Location: $ret", true);
		}

        exit;
    }

    private function curlPostRequest($curlUrl, $data) {
		$headers = array(
			'Accept' 			=>	'application/json',
			'Content-Type' 		=>	'application/json',
			'x-api-version' 	=> 	'2021-05-21',
			'x-client-id' 		=> 	$this->appID,
			'x-client-secret'	=>  $this->secretKey,
		);
		
		$args = array(
			'body'        => $data,
			'timeout'     => '30',
			'headers'     => $headers,
		);

		$response   = wp_remote_post( $curlUrl, $args );
		$http_code  = wp_remote_retrieve_response_code( $response );
		$body       = json_decode(wp_remote_retrieve_body( $response ));

		if($http_code == 200) {
			return $body;
		} else {
			throw new Exception($body->message);
		}
	}
}
