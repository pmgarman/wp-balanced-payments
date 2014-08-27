<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Balanced Payments plugin Stream extension class
 *
 * @package WordPress
 * @subpackage Balanced_Payments
 * @author Patrick Garman
 * @since 2.0.0
 */
class Balanced_Payments_Stream_Connector extends WP_Stream_Connector {

	/**
	 * Actions registered for this context
	 * @var array
	 */
	public static $actions = array(
		'balanced_payments_card_debited'
	);

	/**
	 * Context name
	 * @var string
	 */
	public static $name = 'balanced-payments';

	/**
	 * Return translated context label
	 *
	 * @return string Translated context label
	 */
	public static function get_label() {
		return __( 'Balanced Payments', 'balanced-payments' );
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public static function get_context_labels() {
		return array(
			'bp-debit'   => __( 'Debit', 'balanced-payments' )
		);
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public static function get_action_labels() {
		return array(
			'card-debited' => __( 'Card Debited', 'balanced-payments' )
		);
	}

	/**
	 * Log a card being debited succesfully
	 *
	 * @action woocommerce_attribute_added
	 */
	public static function callback_balanced_payments_card_debited( $amount = '0.00' ) {
		self::log(
			_x(
				'Card debited for %1$s',
				'amount debited',
				'stream-connector-balanced-payments'
			),
			array(
				$amount
			),
			null,
			array( 'bp-debit' => 'card-debited' )
		);
	}

}