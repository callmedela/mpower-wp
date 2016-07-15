<?php
/**
 * Gateway class
 **/

class WC_Gateway_mPowerPayments extends WC_Payment_Gateway {

	/**
	 * Test mode
	 */
	var $testmode;
	
	/**
	 * notify url
	 */
	var $notify_url;
	
	function __construct() { 
		global $woocommerce;
		require_once ('libs/mpower.php');

		$this->id		= 'mPower';
		$this->method_title 	= __('Mpower Payments', 'woocommerce');
		$this->icon 			= apply_filters('woocommerce_mpower_icon', plugins_url('/images/credit-card.png', __FILE__));
		$this->has_fields 		= false;



        // Load the form fields
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Get setting values
		$this->title 			= $this->settings['title'];
		$this->description 		= $this->settings['description'];
		$this->master_key 		= $this->settings['master_key'];
		$this->token_key		= $this->settings['test_token_key'];
		$this->test_secret_key 		= $this->settings['test_secret_key'];
		$this->test_public_key 		= $this->settings['test_public_key'];
		$this->live_secret_key 		= $this->settings['live_secret_key'];
		$this->live_public_key 		= $this->settings['live_public_key'];
		$this->testmode 			= $this->settings['testmode'];
		$this->debug 				= $this->settings['debug'];
		
		// Logs
		if ($this->debug=='yes') $this->log = $woocommerce->logger();
		
		add_action('woocommerce_receipt_'. $this->id, array(&$this, 'receipt_page'));

		// Check for SSL HTTPS on checkout
		//add_action( 'admin_notices', array( &$this, 'ssl_check') );
		
		$this->notify_url = add_query_arg('mPowerListener', 'mPower', get_permalink(woocommerce_get_page_id('pay')));
		
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			add_action( 'init', array( $this, 'notify_handler' ) );
		} else {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            add_action('woocommerce_api_'.strtolower(get_class($this)), array(&$this, 'notify_handler'));
			$this->notify_url   = add_query_arg( 'wc-api', 'WC_Gateway_mPowerPayments', $this->notify_url );
		}
		
		if ( !$this->is_valid_for_use() ) $this->enabled = false;


	}
	
	/**
 	* Check if SSL is enabled and notify the user if SSL is not enabled
 	**/
	function ssl_check() {
		if (get_option('woocommerce_force_ssl_checkout')=='no' && $this->enabled=='yes') :
			echo '<div class="error"><p>'.sprintf(__('Mpower Payments is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate - Mpower will only work in test mode.', 'woocommerce'), admin_url('admin.php?page=woocommerce')).'</p></div>';
		endif;
	}
	
	/**
     * Initialize Gateway Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array(
    		'enabled' => array(
						'title' => __( 'Enable/Disable', 'woocommerce' ), 
						'label' => __( 'Enable mPower Payments', 'woocommerce' ), 
						'type' => 'checkbox', 
						'description' => '', 
						'default' => 'no'
					),
			'title' => array(
						'title' => __( 'Title', 'woocommerce' ), 
						'type' => 'text', 
						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ), 
						'default' => __( 'Pay via mPower', 'woocommerce' ),						
					),
			'description' => array(
						'title' => __( 'Description', 'woocommerce' ), 
						'type' => 'textarea', 
						'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ), 
						'default' => "The quikest way to pay with mPower",					
					),
			'master_key' => array(
						'title' => __( 'Mpower Master Key' ), 
						'type' => 'text', 
						'description' => __( 'Get your Master Key credentials from mPower', 'woocommerce' ), 
						'default' => '',
						'css' => "width: 300px;"
					),
			'test_token_key' => array(
						'title' => __( 'Test Token Key' ),
						'type' => 'text', 
						'description' => __( 'Get your Test Token credentials from mPower', 'woocommerce' ),
						'default' => '',
						'css' => "width: 300px;"
					),
 			'test_secret_key' => array(
						'title' => __( 'Test Private Key' ), 
						'type' => 'text', 
						'description' => __( 'Get your Test Private Key credentials from mPower', 'woocommerce' ), 
						'default' => '',
						'css' => "width: 300px;"
					),
			'test_public_key' => array(
						'title' => __( 'Test Public Key' ), 
						'type' => 'text', 
						'description' => __( 'Get your Test Public Key credentials from mPower', 'woocommerce' ), 
						'default' => '',
						'css' => "width: 300px;"
					),
            'live_token_key' => array(
                'title' => __( 'Live Token Key' ),
                'type' => 'text',
                'description' => __( 'Get your Live Token credentials from mPower', 'woocommerce' ),
                'default' => '',
                'css' => "width: 300px;"
            ),
			'live_secret_key' => array(
						'title' => __( 'Live Private Key' ), 
						'type' => 'text', 
						'description' => __( 'Get your Live Private Key credentials from mPower', 'woocommerce' ), 
						'default' => '',
						'css' => "width: 300px;"
					),
			'live_public_key' => array(
						'title' => __( 'Live Public Key' ), 
						'type' => 'text', 
						'description' => __( 'Get your Live Public Key credentials from mPower', 'woocommerce' ), 
						'default' => '',
						'css' => "width: 300px;"
					),
			'testmode' => array(
						'title' => __( 'Test Mode', 'woocommerce' ), 
						'label' => __( 'Enable mPower Test', 'woocommerce' ), 
						'type' => 'checkbox', 
						'description' => __( 'Process transactions in Test Mode via the mPower Test account.', 'woocommerce' ), 
						'default' => 'no'
					),
			'debug' => array(
						'title' => __( 'Debug', 'woocommerce' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable logging (<code>wc-logs/mPower.txt</code>)', 'woocommerce' ),
						'default' => 'no'
					)
			);
    }
    
    /**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 */
	function admin_options() {
    	?>
    	<h3><?php _e( 'mPower Payment Gateway', 'woocommerce' ); ?></h3>
    	<p><?php _e( 'mPower Payments is the Best Payment Gateway used in Ghana for online Processing', 'woocommerce' ); ?></p>
    	<table class="form-table">
    		<?php
    		if ( $this->is_valid_for_use() ) :
    			// Generate the HTML For the settings form.
    			$this->generate_settings_html();
    		else :
    			?>
            		<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'Mpower Payments support only Ghana Cedis.', 'woocommerce' ); ?></p></div>
        		<?php
        		
    		endif;
    		?>
		</table><!--/.form-table-->
    	<?php
    }
	
		/**
		 * Get payment config
		 */
		function get_config($attr=''){
            $site_url = get_site_url();

			$config = array(
				'store_name'=> get_bloginfo('name'),
				'store_desc' => get_bloginfo('description'),
				);

			if ($this->testmode=="no"){
				$config['mode'] = "live";
                $this->token_key = $this->settings['live_token_key'];
				$this->secret_key = $config['private_key'] = $this->live_secret_key;
                $this->public_key = $config['public_key'] = $this->live_public_key;
			} else {
				$config['mode'] = "test";
                $this->token_key = $this->settings['test_token_key'];
                $this->secret_key = $this->test_secret_key;
                $this->public_key =  $this->test_public_key;
			}


            MPower_Setup::setMasterKey($this->master_key);
            MPower_Setup::setPublicKey($this->public_key);
            MPower_Setup::setPrivateKey($this->secret_key);
            MPower_Setup::setMode($config['mode']);
            MPower_Setup::setToken($this->token_key);

            MPower_Checkout_Store::setName($config['store_name']);
            MPower_Checkout_Store::setTagline($config['store_desc']);
            MPower_Checkout_Store::setReturnUrl("$site_url/wc-api/".strtolower(get_class($this)));
            MPower_Checkout_Store::setCancelUrl($site_url);



			if(!empty($attr) && !empty($config[$attr])) {
				return $config[$attr];
			} 
			
			return $config;
		}

		
		/**
	     * Check if this gateway is enabled and available in the user's country
	     */
	    function is_valid_for_use() { 
	        	if (!in_array(get_woocommerce_currency(), array('GHS'))) return false;
	        return true;
	    }
		
		/**
	     * Payment form on checkout page
	     */
		function payment_fields() {
	?>
			<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE/SANDBOX ENABLED', 'woocommerce'); ?></p><?php endif; ?>
			<?php if ($this->description) : ?><p><?php echo wpautop(wptexturize($this->description)); ?></p><?php endif; ?>
	<?php

		}
		
	 	/**
		 * Get args for passing
		 * 
		 **/
		function get_params( $order) {
			global $woocommerce;
			
			if ($this->debug=='yes') 
				$this->log->add( 'mPower', 'Generating payment form for order #' . $order->id);

			
			$params = array();
			
			//Order info------------------------------------		
			$params['amount'] 			= number_format($order->order_total, 2, '.', '') * 100;		
			$params['currency'] 		= get_option('woocommerce_currency');
			
			//Item name
			$item_names = array();
			if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
				if ($item['qty']) $item_names[] = $item['name'] . ' x ' . $item['qty'];
			endforeach; endif;
			
			$params['description'] 		= sprintf( __('Order %s' , 'woocommerce'), $order->id ) . " - " . implode(', ', $item_names);
			
			//$params['card'] 	= $token;
			
			return $params;
		}
		
		
		/**
	     * Process the payment
		 * 
	     */
		function process_payment($order_id) {
			global $woocommerce;
            $config = $this->get_config();
			$order = new WC_Order($order_id);

            $co = new MPower_Checkout_Invoice();

            $co->setCancelUrl($order->get_cancel_order_url());
            $co->addCustomData("Order#",$order_id);
            $co->addCustomData("order_key",$order->order_key);
            foreach ($order->get_items() as $item){
                if ($item['qty']) {
                    $co->addItem($item['name'],$item['qty'],$item['line_total']/$item['qty'],$item['line_total']);

                }else{
                    $co->addItem($item['name'], 1,$item['line_total'],$item['line_total']);
                }
            }

            $co->setTotalAmount($order->order_total);

            $redirect_url = '';
            $response_text = '';

            if ($co->create()){
                $redirect_url = $co->getInvoiceUrl();
            }else{
                $response_text = $co->response_text;
                if ($this->debug=='yes')
                    $this->log->add( 'mPower', 'Error from Creating Payment : ' . $response_text);

                $redirect_url = add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))));
            }


			if ($this->debug=='yes') {
				$this->log->add( 'mPower', 'Redirect url: ' . $redirect_url);
			// Return thank you redirect
        }
            return array(
                'result'   => 'success',
                'redirect' => $redirect_url
            );


		}

		/**
		 * receipt_page
		 * 
		 **/



		function receipt_page($order_id) {
			global $woocommerce;

			echo '<p>'.__('Thank you for your order, please wait to pay with mPower.', 'woocommerce').'</p>';
			
			$order = new WC_Order($order_id);
			$config = $this->get_config();



		}
    function notify_handler() {
        global $woocommerce;
        $redirect = get_permalink(woocommerce_get_page_id('cart'));
        $urlvars = $_GET;
        $token = (isset($urlvars['token'])?$urlvars['token']:false);

        if($token){
            $config = $this->get_config();
            $invoice = new MPower_Checkout_Invoice();

            if($invoice->confirm($token)){
                $order_id = $invoice->getCustomData("Order#");
                $order_key= $invoice->getCustomData("order_key");
                $order = new WC_Order($order_id);

                if($order->order_key != $order_key) {
                    $woocommerce->add_error(__('Order key do not match!', 'woocommerce'));
                    wp_redirect($redirect); //redirect page
                    exit;
                }

                if($invoice->getStatus() === "completed"){
                    $order->add_order_note( __('Mpower payment completed - Order# '.$order_id, 'woocommerce') . ' (Receipt ID: ' . $invoice->getReceiptUrl(). ')' );
                    $order->payment_complete();

                    $woocommerce->cart->empty_cart();
                    $redirect = $this->get_return_url( $order );
                } else {
                    if ($this->debug=='yes')
                        $this->log->add( 'mPower', 'Error: ' . $invoice->response_text, true);

                    $woocommerce->add_error(__('Payment error', 'woocommerce') . ': ' . $invoice->response_text . '');
                }



                //continue checks
            }else {
                if ($this->debug == 'yes') {
                    $this->log->add('mPower', 'Detail from Mpower ' . print_r($invoice, true));

                }
            }

        }
        wp_redirect($redirect);
    }

		
	} // end woocommerce_mpower



        
//Add Ghana cedis to woocommerce
            add_filter( 'woocommerce_currencies', 'add_ghana_cedis' );
            add_filter('woocommerce_currency_symbol', 'add_ghana_cedis_symbol', 10, 2);
            function add_ghana_cedis( $currencies ) {
                 $currencies['GHS'] = __( 'Ghanaian Cedis', 'woocommerce' );
                 return $currencies;
            }
            function add_ghana_cedis_symbol( $currency_symbol, $currency ) {
                 switch( $currency ) {
                      case 'GHS': $currency_symbol = "GH&cent; "; break;
                 }
                 return $currency_symbol;
            }

