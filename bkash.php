<?php 
/*
Plugin Name: bKash Payment Gateway
Plugin URI:  http://abulkhayer.com 
Description: bKash Payment Gateway is a plugin to enable the ability of recieving WooCommerce payment. It requires WooCommerce to be installed and activated to enable the bKash payment option on the checkout page.
Version:     1.0.0
Author:      Abul Khayer 
Author URI:  http://abulkhayer.com 
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: bpg
*/
defined('ABSPATH') or die('Catch me if you can. ;-) ');

add_filter('woocommerce_payment_gateways', 'bkash_payment_gateways');
function bkash_payment_gateways( $gateways ){
	$gateways[] = 'bKash_Payment_Gateway';
	return $gateways;
}

add_action('plugins_loaded', 'bkash_plugin_activation');
function bkash_plugin_activation(){
	
	class bKash_Payment_Gateway extends WC_Payment_Gateway {

		public $bkash_number;
		public $number_type;
		public $order_status;
		public $instructions;
		public $bkash_charge;
		public $domain;

		public function __construct(){
			$this->domain 				= 'bpg';

			$this->id 					= 'bkash';
			$this->title 				= $this->get_option('title', 'bKash Payment Gateway');
			$this->description 			= $this->get_option('description', 'bKash Payment Gateway for WooCommerce');
			$this->method_title 		= __("bKash", $this->domain);
			$this->method_description 	= __("bKash Payment Gateway Options", $this->domain );
			$this->icon 				= plugins_url('images/bkash.png', __FILE__);
			$this->has_fields 			= true;

			$this->bkash_options_fields();
			$this->init_settings();
			
			$this->bkash_number = $this->get_option('bkash_number');
			$this->number_type 	= $this->get_option('number_type');
			$this->order_status = $this->get_option('order_status');
			$this->instructions = $this->get_option('instructions');
			$this->bkash_charge = $this->get_option('bkash_charge');

			add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
            add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'bkash_thankyou_page' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'bkash_email_instructions' ), 10, 3 );
		}


		public function bkash_options_fields(){
			$this->form_fields = array(
				'enabled' 	=>	array(
					'title'		=> __( 'Enable/Disable', $this->domain ),
					'type' 		=> 'checkbox',
					'label'		=> __( 'bKash Payment Gateway', $this->domain ),
					'default'	=> 'yes'
				),
				'title' 	=> array(
					'title' 	=> __( 'Title', $this->domain ),
					'type' 		=> 'text',
					'default'	=> __( 'bKash', $this->domain )
				),
				'description' => array(
					'title'		=> __( 'Description', $this->domain ),
					'type' 		=> 'textarea',
					'default'	=> __( 'Please complete your bKash payment at first, then fill up the form below.', $this->domain ),
					'desc_tip'    => true
				),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-on-hold',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),				
				'bkash_number'	=> array(
					'title'			=> 'bKash Number',
					'description' 	=> __( 'Add a bKash mobile no which will be shown in checkout page', $this->domain ),
					'type'			=> 'text',
					'desc_tip'      => true
				),
				'number_type'	=> array(
					'title'			=> __( 'Account Type', $this->domain ),
					'type'			=> 'select',
					'class'       	=> 'wc-enhanced-select',
					'description' 	=> __( 'Select bKash account type', $this->domain ),
					'options'	=> array(
						'Agent'		=> __( 'Agent', $this->domain ),
						'Personal'	=> __( 'Personal', $this->domain )
					),
					'desc_tip'      => true
				),
				'bkash_charge' 	=>	array(
					'title'			=> __( 'Enable bKash Charge', $this->domain ),
					'type' 			=> 'checkbox',
					'label'			=> __( 'Add 2% bKash "Send Money" charge to net price', $this->domain ),
					'description' 	=> __( 'If cart total is upto 1000 then customer have to pay ( 1000 + 20 ) = 1020. Here 20 is bKash send money charge', $this->domain ),
					'default'		=> 'no',
					'desc_tip'    	=> true
				),						
                'instructions' => array(
                    'title'       	=> __( 'Instructions', $this->domain ),
                    'type'        	=> 'textarea',
                    'description' 	=> __( 'Instructions to be displayed to the thank you page and emails.', $this->domain ),
                    'default'     	=> __( 'Thanks for puchasing through bKash. We will check and notify you very soon.', $this->domain ),
                    'desc_tip'    	=> true
                ),								
			);
		}


		public function payment_fields(){

			global $woocommerce;
			$bkash_charge = ($this->bkash_charge == 'yes') ? __(' Also note that 2% bKash "SEND MONEY" cost will be added with net price. Total amount you need to send us at', $this->domain ). ' ' . get_woocommerce_currency_symbol() . $woocommerce->cart->total : '';
			echo wpautop( wptexturize( __( $this->description, $this->domain ) ) . $bkash_charge  );
			echo wpautop( wptexturize( "bKash ".$this->number_type." Number : ".$this->bkash_number ) );

			?>
				<p>
					<label for="bkash_number"><?php _e( 'bKash Number', $this->domain );?></label>
					<input type="text" name="bkash_number" id="bkash_number" placeholder="01XXXXXXXXX">
				</p>
				<p>
					<label for="bkash_transaction_id"><?php _e( 'bKash Transaction ID', $this->domain );?></label>
					<input type="text" name="bkash_transaction_id" id="bkash_transaction_id" placeholder="1A2B3C4D5E">
				</p>
			<?php 
		}
		

		public function process_payment( $order_id ) {
			global $woocommerce;
			$order = new WC_Order( $order_id );
			
			$status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
			// Mark as on-hold (we're awaiting the bKash)
			$order->update_status( $status, __( 'Checkout with bKash Payment Gateway. ', $this->domain ) );

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			$woocommerce->cart->empty_cart();

			// Return thankyou redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}	


        public function bkash_thankyou_page() {
		    $order_id = get_query_var('order-received');
		    $order = new WC_Order( $order_id );
		    if( $order->payment_method == $this->id ){
	            $thankyou = $this->instructions;
	            return $thankyou;		        
		    } else {
		    	return __( 'Thank you. Your order has been received.', $this->domain );
		    }

        }


        public function bkash_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		    if( $order->payment_method != $this->id )
		        return;        	
            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

	}

}

/**
 * If bKash charge is activated
 */
$bkash_charge = get_option( 'woocommerce_bkash_settings' );
if( $bkash_charge['bkash_charge'] == 'yes' ){

	add_action( 'wp_enqueue_scripts', 'bkash_script' );
	function bkash_script(){
		wp_enqueue_script( 'bpg-script', plugins_url( 'js/scripts.js', __FILE__ ), array('jquery'), '1.0', true );
	}

	add_action( 'woocommerce_cart_calculate_fees', 'bkash_charge' );
	function bkash_charge(){

	    global $woocommerce;
	    $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
	    $current_gateway = '';

	    if ( !empty( $available_gateways ) ) {
	        if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
	            $current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
	        } 
	    }
	    
	    if( $current_gateway!='' ){

	        $current_gateway_id = $current_gateway->id;

			if ( is_admin() && ! defined( 'DOING_AJAX' ) )
				return;

			if ( $current_gateway_id =='bkash' ) {
				$percentage = 0.02;
				$surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
				$woocommerce->cart->add_fee( __('bKash Charge', 'bpg'), $surcharge, true, '' ); 
			}
	       
	    }    	
	    
	}
	
}

/**
 * Empty field validation
 */
add_action( 'woocommerce_checkout_process', 'bkash_payment_process' );
function bkash_payment_process(){

    if($_POST['payment_method'] != 'bkash')
        return;

    $bkash_number = sanitize_text_field( $_POST['bkash_number'] );
    $bkash_transaction_id = sanitize_text_field( $_POST['bkash_transaction_id'] );

    $match_number = isset($bkash_number) ? $bkash_number : '';
    $match_id = isset($bkash_transaction_id) ? $bkash_transaction_id : '';

    $validate_number = preg_match( '/^01[3-9]\d{8}$/', $match_number );
    $validate_id = preg_match( '/^[0-9A-Z]{10}$/',  $match_id );

    if( !isset($bkash_number) || empty($bkash_number) )
        wc_add_notice( __( 'Please add your mobile number', 'bpg'), 'error' );

	if( !empty($bkash_number) && $validate_number == false )
        wc_add_notice( __( 'Incorrect mobile number. It must be 11 digit, starts with 013 / 014 / 015 / 016 / 017 / 018 / 019', 'bpg'), 'error' );

    if( !isset($bkash_transaction_id) || empty($bkash_transaction_id) )
        wc_add_notice( __( 'Please enter your bKash Transaction ID', 'bpg' ), 'error' );

	if( !empty($bkash_transaction_id) && $validate_id == false )
        wc_add_notice( __( 'Please enter a valid Transaction ID', 'bpg'), 'error' );

}

/**
 * Update bKash field to database
 */
add_action( 'woocommerce_checkout_update_order_meta', 'bkash_additional_fields_update' );
function bkash_additional_fields_update( $order_id ){

    if($_POST['payment_method'] != 'bkash' )
        return;

    $bkash_number = sanitize_text_field( $_POST['bkash_number'] );
    $bkash_transaction_id = sanitize_text_field( $_POST['bkash_transaction_id'] );

	$number = isset($bkash_number) ? $bkash_number : '';
	$transaction = isset($bkash_transaction_id) ? $bkash_transaction_id : '';

	update_post_meta($order_id, '_bkash_number', $number);
	update_post_meta($order_id, '_bkash_transaction', $transaction);

}

/**
 * Admin order page bKash data output
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'bkash_admin_order_data' );
function bkash_admin_order_data( $order ){
    
    if( $order->payment_method != 'bkash' )
        return;

	$number = (get_post_meta($order->id, '_bkash_number', true)) ? get_post_meta($order->id, '_bkash_number', true) : '';
	$transaction = (get_post_meta($order->id, '_bkash_transaction', true)) ? get_post_meta($order->id, '_bkash_transaction', true) : '';

	?>
		<table class="wp-list-table widefat fixed striped posts">
			<tbody>
				<tr>
					<th><?php _e('bKash Number', 'bpg') ;?></th>
					<td><?php echo esc_attr( $number );?></td>
				</tr>
				<tr>
					<th><?php _e('Transaction ID', 'bpg') ;?></th>
					<td><?php echo esc_attr( $transaction );?></td>
				</tr>
			</tbody>
		</table>
	<?php 
	
}

/**
 * Order review page bKash data output
 */
add_action('woocommerce_order_details_after_customer_details', 'bkash_additional_info_order_review_fields' );
function bkash_additional_info_order_review_fields( $order ){
    
    if( $order->payment_method != 'bkash' )
        return;

	$number = (get_post_meta($order->id, '_bkash_number', true)) ? get_post_meta($order->id, '_bkash_number', true) : '';
	$transaction = (get_post_meta($order->id, '_bkash_transaction', true)) ? get_post_meta($order->id, '_bkash_transaction', true) : '';

	?>
		<tr>
			<th><?php _e('bKash Number:', 'bpg');?></th>
			<td><?php echo esc_attr( $number );?></td>
		</tr>
		<tr>
			<th><?php _e('Transaction ID:', 'bpg');?></th>
			<td><?php echo esc_attr( $transaction );?></td>
		</tr>
	<?php 
	
}	

/**
 * Register new admin column
 */
add_filter( 'manage_edit-shop_order_columns', 'bkash_admin_new_column' );
function bkash_admin_new_column($columns){

    $new_columns = (is_array($columns)) ? $columns : array();
    unset( $new_columns['order_actions'] );
    $new_columns['mobile_no'] = __('bKash Number', 'bpg');
    $new_columns['tran_id'] = __('Tran. ID', 'bpg');

    $new_columns['order_actions'] = $columns['order_actions'];
    return $new_columns;

}

/**
 * Load data in new column
 */
add_action( 'manage_shop_order_posts_custom_column', 'bkash_admin_column_value', 2 );
function bkash_admin_column_value($column){

    global $post;

    $mobile_no = (get_post_meta($post->ID, '_bkash_number', true)) ? get_post_meta($post->ID, '_bkash_number', true) : '';
    $tran_id = (get_post_meta($post->ID, '_bkash_transaction', true)) ? get_post_meta($post->ID, '_bkash_transaction', true) : '';

    if ( $column == 'mobile_no' ) {    
        echo esc_attr( $mobile_no );
    }
    if ( $column == 'tran_id' ) {    
        echo esc_attr( $tran_id );
    }
}
