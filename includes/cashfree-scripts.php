<?php
/**
 * Cashfree scripts
 */

defined( 'ABSPATH' ) || exit;

return array(
	'cb-cashfree-checkout' => array(
		'src'     => QB_CASHFREE_DIR_PATH . 'assets/js/checkout.js',
		'deps'    => array(),
	),
	'cb-cashfree-js'       => array(
		'src'     => 'https://sdk.cashfree.com/js/pippin/1.0.1/pippin.min.js',
		'deps'    => array(),
	),
);
