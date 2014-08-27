<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Balanced Payments Plugin class
 *
 * @package WordPress
 * @subpackage Balanced_Payments
 * @author Patrick Garman
 * @since 1.0.0
 */
class Balanced_Payments {

	public $version = '2.0.0';
	public $skeuocard_version = '1.0.3';
	public $api_url = 'https://api.balancedpayments.com';
	public $api_version = '1.1';

	/**
	 * Construct
	 * 
	 * @param string $file
	 */
	public function __construct( $file ) {
		$this->name = 'Balanced Payments';
		$this->token = 'balanced-payments';

		$this->plugin_base = plugin_basename( $file );
		$this->plugin_url  = trailingslashit( plugin_dir_url( $file ) );
		$this->plugin_dir  = trailingslashit( plugin_dir_path( $file ) );

		if( is_admin() ) {
			$this->admin = new WP_Balanced_Payments_Admin();
		}

		add_filter( 'plugin_action_links_' . $this->plugin_base, array( $this, 'action_links' ) );

		add_action( 'init', array( $this, 'init_cmb'), 9999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles') );

		add_action('wp_ajax_bp_post_listener', array( $this, 'ajax_post_listener' ) );
		add_action('wp_ajax_nopriv_bp_post_listener', array( $this, 'ajax_post_listener' ) );

		add_shortcode( 'bp-form', array( $this, 'shortcode' ) );
	}

	/**
	 * Initialize the metabox class.
	 */
	function init_cmb() {

		if ( ! class_exists( 'cmb_Meta_Box' ) ) {
			require_once $this->plugin_dir . 'libraries/cmb/init.php';
		}

	}

	/**
	 * Add custom action links to plugins dashboard
	 */
	public function action_links ( $links ) {
		$new_links[] = sprintf( '<a href="%2$s">%1$s</a>', __( 'Settings', 'balanced-payments' ), admin_url( 'options-general.php?page=balanced-payments' ) );
		$new_links[] = sprintf( '<a target="_blank" href="%2$s">%1$s</a>', __( 'Support', 'balanced-payments' ), 'https://pmgarman.me/plugin-support/' );
		$new_links[] = sprintf( '<a target="_blank" href="%2$s">%1$s</a>', __( 'Donate', 'balanced-payments' ), 'https://pmgarman.me/donate/' );

		return array_merge( $new_links, $links );
	}


	/**
	 * Make API call to Balanced Payments
	 * 
	 * @return array
	 */
	public function call_api( $method, $uri, $data ) {
		$url = untrailingslashit( $this->api_url ) . $uri;

		$args = array(
			'method' => strtoupper( $method ),
			'body' => json_encode( $data ),
			'user-agent' => 'WP-Balanced-Payments/' . $this->version,
			'sslverify' => true,
			'redirection' => 0,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->settings['balanced-payments']['secret'] . ':' ),
				'Content-Type' => 'application/json'
			)
		);

		$response = wp_remote_request( $url, $args );

		return array(
			'json' => wp_remote_retrieve_body( $response ),
			'status' => wp_remote_retrieve_response_code( $response ),
			'raw' => $response
		);
	}

	/**
	 * Listen for AJAX CC payment postings
	 * 
	 * @return void
	 */
	public function ajax_post_listener() {
		echo json_encode( $this->process_payment( $_POST['cc_uri'] ) );
		exit;
	}

	/**
	 * Listen for no-js CC payment postings
	 * 
	 * @return array|bool
	 */
	public function fallback_post_listener() {
		if( !$this->test_fallback() ) {
			return false;
		}

		$card = array(
			'card_number' => $_POST['cc_number'],
			'expiration_year' => '20' . $_POST['cc_exp_year'],
			'expiration_month' => $_POST['cc_exp_month'],
			'security_code' => $_POST['cc_cvc'],
			'name' => $_POST['cc_name'],
			'postal_code' => $_POST['cc_post_code']
		);

		$card = $this->tokenize_card( $card );

		if( intval( $card['status'] ) !== 201 ) {
			return '<p class="alert">' . __( 'There was an error securely storing the credit card data, no charges were made.', 'balanced-payments' ) . '</p>';
		}

		$card = json_decode( $card['json'] );

		$result = $this->process_payment( $card->uri );

		if( $result['success'] ) {
			return '<p class="success">' . __( 'Thank you for your payment!', 'balanced-payments' ) . '</p>';
		} else {
			return '<p class="alert">' . $result['error'] . '</p>';
		}

	}

	/**
	 * Handle the actual processing after the CC is tokenized
	 * 
	 * @return void
	 */
	public function process_payment( $token ) {
		$customer = $this->create_customer( array( 'name' => $_POST['cc_name'] ) );

		if( intval( $customer['status'] ) !== 201 ) {
			return array( 'success' => false, 'error' => _( 'Unable to create customer', 'balanced-payments' ) );
		}

		$customer = json_decode( $customer['json'] );

		$card = $this->attach_token_to_customer( $customer->uri, $token, 'card' );

		if( intval( $card['status'] ) !== 200 ) {
			return array( 'success' => false, 'error' => _( 'Unable to attach source to customer.', 'balanced-payments' ) );
		}

		$debit = $this->debit_customer( $customer->uri, $token, intval( number_format( $_POST['cc_amount'], 2 ) * 100 ) );

		if( intval( $debit['status'] ) !== 201 ) {
			return array( 'success' => false, 'error' => _( 'Unable to debit the source.', 'balanced-payments' ) );
		}

		return array( 'success' => true, 'error' => null );
	}

	/**
	 * Throw ajax error
	 * 
	 * @return void
	 */
	public function throw_ajax_error( $error ) {
		echo json_encode( array( 'error' => $error ) );
		status_header( 500 );
		exit;
	}

	/**
	 * Create Customer
	 * 
	 * @return array
	 */
	public function tokenize_card( $card ) {
		return $this->call_api( 'post', $this->settings['balanced-payments']['uri'] . '/cards', $card );
	}

	/**
	 * Create Customer
	 * 
	 * @return array
	 */
	public function create_customer( $data ) {
		return $this->call_api( 'post', '/v1/customers', $data );
	}

	/**
	 * Create Customer
	 * 
	 * @return array
	 */
	public function attach_token_to_customer( $customer_uri, $token_uri, $type = 'card' ) {
		switch( $type ) {
			case 'card':
				$key = 'card_uri';
				break;
			case 'bank':
				$key = 'bank_account_uri';
				break;
			default:
				return false;
		}
		$data[ $key ] = $token_uri;
		return $this->call_api( 'put', $customer_uri, $data );
	}

	/**
	 * Debit Customer
	 * 
	 * @return array
	 */
	public function debit_customer( $customer_uri, $source_uri, $amount ) {

		$args = array(
			'source_uri' => $source_uri,
			'amount' => intval( $amount )
		);

		return $this->call_api( 'post', trailingslashit( $customer_uri ) . 'debits', $args );
	}

	/**
	 * Should we use the fallback processor?
	 * 
	 * @return array|bool
	 */
	public function test_fallback() {
		if( isset( $_POST['nojs-post'] ) && intval( $_POST['nojs-post'] ) === 1 ) {
			return true;
		}
		return false;
	}

	/**
	 * Return an asset URL
	 * 
	 * @return string
	 */
	public function get_asset_url( $file ) {
		return trailingslashit( $this->plugin_url ) . 'assets/' . $file;
	}

	/**
	 * Return an asset URL
	 * 
	 * @return string
	 */
	public function get_library_file_url( $library, $file ) {
		return trailingslashit( $this->plugin_url ) . 'libraries/' . $library . '/' . $file;
	}

	/**
	 * Return the post URL
	 * 
	 * @return string
	 */
	public function get_post_url() {
		return add_query_arg( 'cc-payment-listener', 1 );
	}

	/**
	 * Load CSS
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'balanced-payments-basic', $this->get_asset_url( 'css/basic.css' ), array(), $this->version );

		if( get_balanced_payments_setting( 'styles' ) === 'skeuocard' ) {
			wp_enqueue_style( 'skeuocard-reset', $this->get_library_file_url( 'skeuocard', 'styles/skeuocard.reset.css' ), array( 'balanced-payments-basic' ), $this->skeuocard_version );
			wp_enqueue_style( 'skeuocard', $this->get_library_file_url( 'skeuocard', 'styles/skeuocard.css' ), array( 'skeuocard-reset'), $this->skeuocard_version );
		}
	}

	/**
	 * Load Scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$data = array(
			'URI'             => get_balanced_payments_setting( 'uri' ),
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'cardCreateError' => get_balanced_payments_setting( 'message-error-card-create' ),
			'success'         => get_balanced_payments_setting( 'message-payment-success' )
		);

		$deps = array( 'jquery' );

		if( get_balanced_payments_setting( 'styles' ) == 'skeuocard' ) {
			wp_enqueue_script( 'skeuocard', $this->get_library_file_url( 'skeuocard', 'javascripts/skeuocard.min.js' ), array( 'jquery' ), $this->skeuocard_version );
			$deps[] = 'skeuocard';
		}

		wp_enqueue_script( 'balanced.js', 'https://js.balancedpayments.com/1.1/balanced.js', $deps, $this->api_version );
		wp_enqueue_script( 'balanced-payments', $this->get_asset_url( 'js/balanced-payments.js' ), array( 'balanced.js' ), $this->version );

		wp_localize_script( 'balanced-payments', 'balancedPayments', $data );
	}

	/**
	 * Shortcode
	 * 
	 * @return string
	 */
	public function shortcode( $atts ) {
		extract( shortcode_atts( array(
			'default' => '0.00',
		), $atts ) );

		$this->enqueue_scripts();

		if( $this->test_fallback() ) {
			return $this->fallback_post_listener();
		} else {
			return $this->get_cc_form( $default );	
		}
	}

	/**
	 * Generate CC Form
	 * 
	 * @return string
	 */
	public function get_cc_form( $default = '0.00' ) {
		ob_start();
		?>
		<form id="cc-form" action="<?php echo $this->get_post_url(); ?>" method="POST">
			<div class="credit-card-input no-js">
				<p class="no-support-warning"><?php _e( 'Javascript seems to be disabled, please use the form below for your credit card payment.', 'balanced-payments' ); ?></p>
				<label for="cc_type"><?php _e( 'Card Type', 'balanced-payments' ); ?></label>
				<select name="cc_type">
					<option value="-"><?php _e( 'Select a Card Type', 'balanced-payments' ); ?></option>
					<option value="visa"><?php _e( 'Visa', 'balanced-payments' ); ?></option>
					<option value="discover"><?php _e( 'Discover', 'balanced-payments' ); ?></option>
					<option value="mastercard"><?php _e( 'MasterCard', 'balanced-payments' ); ?></option>
					<option value="amex"><?php _e( 'American Express', 'balanced-payments' ); ?></option>
				</select>
				<label for="cc_number"><?php _e( 'Card Number', 'balanced-payments' ); ?></label>
				<input type="text" name="cc_number" id="cc_number" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" size="19">
				<label for="cc_exp_month"><?php _e( 'Expiration Month', 'balanced-payments' ); ?></label>
				<input type="text" name="cc_exp_month" id="cc_exp_month" maxlength="2" placeholder="<?php echo date('m'); ?>">
				<label for="cc_exp_year"><?php _e( 'Expiration Year', 'balanced-payments' ); ?></label>
				<input type="text" name="cc_exp_year" id="cc_exp_year" maxlength="2" placeholder="<?php echo date('y'); ?>">
				<label for="cc_name"><?php _e( 'Cardholder\'s Name', 'balanced-payments' ); ?></label>
				<input type="text" name="cc_name" id="cc_name" placeholder="John Doe">
				<label for="cc_cvc"><?php _e( 'Card Validation Code', 'balanced-payments' ); ?></label>
				<input type="text" name="cc_cvc" id="cc_cvc" placeholder="123" maxlength="3" size="3">
			</div>
			<label for="cc_post_code"><?php _e( 'Billing Zip Code', 'balanced-payments' ); ?></label>
			<input type="text" name="cc_post_code" id="cc_post_code" placeholder="<?php _e( '12345', 'balanced-payments' ); ?>" >
			<label for="cc_amount"><?php _e( 'Amount', 'balanced-payments' ); ?></label>
			<input type="text" name="cc_amount" id="cc_amount" placeholder="<?php echo number_format( $default, 2 ); ?>">
			<input type="submit" id="cc_submit" class="button" value="<?php _e( 'Make Payment', 'balanced-payments' ); ?>">
			<input type="hidden" name="nojs-post" value="1" />
		</form>
		<?php
		return ob_get_clean();
	}

}