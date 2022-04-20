<?php
/**
 * Cashfree functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the script and inject parameters.
 *
 * @param string     $handle Script handle the data will be attached to.
 * @param array|null $params Parameters injected.
 */
function cb_cashfree_script( $handle, $params = null ) {
	$script = ( include 'cashfree-scripts.php' )[ $handle ];
	wp_enqueue_script( $handle, $script['src'], $script['deps'], $script['version'] );

	if ( null !== $params ) {
		wp_localize_script( $handle, str_replace( '-', '_', $handle ) . '_params', $params );
	}
}

/**
 * Register and load wc-cashfree script.
 *
 * @param array $settings Gateway settings.
 */
function cb_cashfree_js( $appId ) {
	cb_cashfree_script( 'cb-cashfree-js' );

	cb_cashfree_script(
		'cb-cashfree-checkout',
		array(
			'appId' => $appId,
			'locale'     => get_locale(),
		)
	);
}
