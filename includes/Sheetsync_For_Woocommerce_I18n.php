<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://parsamirzaie.com
 * @since      1.0.0
 *
 * @package    Sheetsync_For_Woocommerce
 * @subpackage Sheetsync_For_Woocommerce/includes
 */

namespace Parsamirzaie\SheetsyncForWoocommerce\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Sheetsync_For_Woocommerce
 * @subpackage Sheetsync_For_Woocommerce/includes
 * @author     Parsa Mirzaie <Mirzaie_parsa@protonmail.ch>
 */
class Sheetsync_For_Woocommerce_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'sheetsync-for-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
