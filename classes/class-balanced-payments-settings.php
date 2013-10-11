<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
 * Balanced Payments Settings
 *
 * All functionality pertaining to the subscribe settings screen.
 *
 * @package WordPress
 * @subpackage Balanced_Payments_Settings
 * @category Admin
 * @author Patrick Garman
 * @since 1.0.0
 */
class Balanced_Payments_Settings extends Balanced_Payments_Settings_API {
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct () {
		global $Balanced_Payments;
		parent::__construct( $Balanced_Payments->name, 'balanced-payments' ); // Required in extended classes.
	} // End __construct()

	/**
	 * init_sections function.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function init_sections () {
		$sections = array();

		$sections['general'] = array(
			'name' => __('General Settings' , 'balanced-payments'),
		);

		$this->sections = $sections;
	} // End init_sections()
	
	/**
	 * init_fields function.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function init_fields () {
		$fields = array();

		$fields['uri'] = array(
			'name' => __( 'Marketplace URI', 'balanced-payments' ),
			'description' => __( 'This must be the full URI including the /v1/marketplaces/ in the begining.', 'balanced-payments' ),
			'type' => 'textbox',
			'placeholder' => '/v1/marketplaces/yourstoreid',
			'section' => 'general'
		);

		$fields['secret'] = array(
			'name' => __( 'API Key Secret', 'balanced-payments' ),
			'description' => __( 'Do not share this key with anyone!', 'balanced-payments' ),
			'type' => 'password',
			'section' => 'general'
		);

		$fields['styles'] = array(
			'name' => __( 'Styles', 'balanced-payments' ),
			'description' => __( 'Which styles to load with the form.', 'balanced-payments' ),
			'type' => 'select',
			'default' => 'skeuocard',
			'options' => array(
				'skeuocard' => __( 'Skeuocard', 'balanced-payments' ),
				'basic' => __( 'Basic', 'balanced-payments' )
			),
			'section' => 'general'
		);
		
		$this->fields = $fields;
	} // End init_fields()
	
} // End Class
?>