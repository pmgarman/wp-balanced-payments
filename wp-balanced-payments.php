<?php
/**
 * Plugin Name: Balanced Payments
 * Plugin URI: https://pmgarman.me/
 * Description: Add simple credit card form to your site with a short code to take payments using Balanced Payments.
 * Version: 2.0.0
 * Author: Patrick Garman
 * Author URI: https://pmgarman.me
 * Text Domain: balanced-payments
 * Domain Path: /languages/
 * License: GPLv2
 */

/**
 * Copyright 2013  Patrick Garman  (email: patrick@pmgarman.me)
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if( ! class_exists( 'Balanced_Payments' ) ) {
	// Required Files
	require_once 'classes/class-balanced-payments-admin.php';
	require_once 'classes/class-balanced-payments.php';

	// Start the engines!
	global $Balanced_Payments;
	$Balanced_Payments = new Balanced_Payments( __FILE__ );
	load_plugin_textdomain( 'balanced-payments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}