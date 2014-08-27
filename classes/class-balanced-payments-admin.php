<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class for WP Balanced Payments
 *
 * @package WordPress
 *
 * @subpackage Project
 */
class WP_Balanced_Payments_Admin {

	// A single instance of this class.
	public static $instance         = null;
	public static $settings         = array();
	public static $key              = 'balanced-payments';
	public static $title            = '';

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  2.0.0
	 *
	 * @return _S_Admin A single instance of this class.
	 */
	public static function go() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate our hooks
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		self::$title = __( 'Balanced Payments', 'balanced-payments' );

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Register our setting to WP
	 *
	 * @since 2.0.0
	 */
	public function init() {
		register_setting( self::$key, self::$key );
	}

	/**
	 * Add menu options page
	 *
	 * @since 2.0.0
	 */
	public function add_page() {
		$this->options_page = add_options_page( self::$title, self::$title, 'manage_options', self::$key, array( $this, 'admin_page' ) );
		add_action( 'admin_head-' . $this->options_page, array( $this, 'admin_head' ) );
	}

	/**
	 * CSS
	 *
	 * @since 2.0.0
	 */
	public function admin_head() { ?>
		<style type="text/css">
		.cmb-form .button-primary {
			margin: 48px 0 0 8px;
		}
		</style>
	<?php }

	/**
	 * Admin page markup. Mostly handled by CMB
	 *
	 * @since 2.0.0
	 */
	public function admin_page() { ?>
		<div class="wrap cmb_options_page <?php echo self::$key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php // Check for CMB
			if( class_exists( 'cmb_Meta_Box' ) ) {
				cmb_metabox_form( $this->option_fields(), self::$key );
			} ?>
		</div>
	<?php }

	/**
	 * Defines the site option metabox and field configuration
	 *
	 * @since  2.0.0
	 *
	 * @return array
	 */
	public static function option_fields() {

		// Only need to initiate the array once per page-load
		if ( ! empty( self::$settings ) ) {
			return self::$settings;
		}

		self::$settings = array(
			'id'         => self::$key,
			'show_on'    => array( 'key' => 'options-page', 'value' => array( self::$key, ), ),
			'show_names' => true,
			'cmb_styles' => false,
			'fields'     => array(
				'api-title' => array(
					'name' => __( 'API Details', 'balanced-payments' ),
					'desc' => __( 'These are found on the Account > Settings page of the Balanced Payments dashboard.', 'balanced-payments' ),
					'id'   => 'api-title',
					'type' => 'title',
				),
				'uri'       => array(
					'name' => __( 'Marketplace ID', 'balanced-payments' ),
					'id'   => 'uri',
					'type' => 'text',
					'attributes' => array(
						'placeholder' => 'TEST-MP1TvJwxVNwP6QcgI4z6puO4'
					)
				),
				'secret'    => array(
					'name' => __( 'API Key Secret', 'balanced-payments' ),
					'id'   => 'secret',
					'type' => 'text',
					'attributes' => array(
						'placeholder' => 'ak-test-jYsT5bBaiHRcEYQ25VYuOxHSoCVuKtLT'
					)
				),
				'processing-settings-title' => array(
					'name' => __( 'Processing Settings', 'balanced-payments' ),
					'desc' => __( 'Configure how you want to handle your transactions.', 'balanced-payments' ),
					'id'   => 'api-title',
					'type' => 'title',
				),
				'description'    => array(
					'name' => __( 'Description', 'balanced-payments' ),
					'desc' => __( 'Private description of credits which will appear only in the Balanced Payments dashboard.', 'balanced-payments' ),
					'id'   => 'description',
					'type' => 'text',
					'attributes' => array(
						'placeholder' => 'Donation for WordPress Plugins'
					)
				),
				'on_statement'    => array(
					'name' => __( 'Appears On Statement As', 'balanced-payments' ),
					'desc' => sprintf( __( 'Learn more about the "Appears On Statement As" field restrictions in the Balanced Payments API documentation <a target="_blank" href="%s">here</a>.', 'balanced-payments' ), 'https://docs.balancedpayments.com/1.1/api/debits/#create-a-card-debit' ),
					'id'   => 'on_statement',
					'type' => 'text',
					'attributes' => array(
						'placeholder' => '@pmgarman donation'
					),
				),
				'frontend-title' => array(
					'name' => __( 'Frontend Settings', 'balanced-payments' ),
					'desc' => __( 'Configure how you want to handle your credit card form to look.', 'balanced-payments' ),
					'id'   => 'api-title',
					'type' => 'title',
				),
				'styles'    => array(
					'name'    => __( 'Styles', 'balanced-payments' ),
					'id'      => 'styles',
					'type'    => 'select',
					'options' => array(
						'skeuocard' => __( 'Skeuocard', 'balanced-payments' ),
						'basic'     => __( 'Basic', 'balanced-payments' )
					)
				),
				'default-amount'    => array(
					'name' => __( 'Default Amount', 'balanced-payments' ),
					'id'   => 'default-amount',
					'type' => 'text_small',
					'attributes' => array(
						'placeholder' => '5.00'
					)
				),
			)
		);

		return self::$settings;

	}

	/**
	 * Make public the protected $key variable.
	 *
	 * @since  2.0.0
	 *
	 * @return string  Option key
	 */
	public static function key() {
		return self::$key;
	}

}

/**
 * Wrapper function around cmb_get_option
 *
 * @since  2.0.0
 *
 * @param  string  $key Options array key
 *
 * @return mixed        Option value
 */
function get_balanced_payments_options( $key = '' ) {
	return cmb_get_option( WP_Balanced_Payments_Admin::key(), $key );
}
