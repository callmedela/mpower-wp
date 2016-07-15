<?php 
    /*
    Plugin Name: Mpower Payment Gateway WooCommerce
    Plugin URI: http://www.delabx.com/
    Description: This Plugin is intended to enhance Online Payment Processing in Ghana via Mpower
    Author: @callmedela
    Version: 1.0
    Author URI: http://twitter.com/callmedela
 

------------------------------------------------------------------------
*/  

add_action('plugins_loaded', 'woocommerce_mPowerPayments_init', 0);

function woocommerce_mPowerPayments_init() {

	if ( ! class_exists( 'Woocommerce' ) ) { return; }



	if(!defined('MPOWERPAYMENTS_SDK')) {
		define('MPOWERPAYMENTS_SDK', 1);
		require_once('wc-gateway-mpower.php');
	}
	function add_mPowerPayments_gateway($methods) {
		$methods[] = 'WC_Gateway_mPowerPayments';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'add_mPowerPayments_gateway' );


}
