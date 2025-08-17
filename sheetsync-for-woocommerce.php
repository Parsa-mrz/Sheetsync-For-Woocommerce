<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://parsamirzaie.com
 * @since             1.0.0
 * @package           Sheetsync_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       SheetSync For WooCommerce
 * Plugin URI:        https://parsamirzaie.com
 * Description:       The plugin syncs data from a Google Sheet to a WooCommerce store.
 * Version:           1.0.0
 * Author:            Parsa Mirzaie
 * Author URI:        https://parsamirzaie.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sheetsync-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

use Parsamirzaie\SheetsyncForWoocommerce\Includes\Sheetsync_For_Woocommerce;
use Parsamirzaie\SheetsyncForWoocommerce\Includes\Sheetsync_For_Woocommerce_Activator;
use Parsamirzaie\SheetsyncForWoocommerce\Includes\Sheetsync_For_Woocommerce_Deactivator;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SHEETSYNC_FOR_WOOCOMMERCE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sheetsync-for-woocommerce-activator.php
 */
function activate_sheetsync_for_woocommerce() {
	Sheetsync_For_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sheetsync-for-woocommerce-deactivator.php
 */
function deactivate_sheetsync_for_woocommerce() {
	Sheetsync_For_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_sheetsync_for_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_sheetsync_for_woocommerce' );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sheetsync_for_woocommerce() {

	$plugin = new Sheetsync_For_Woocommerce();
	$plugin->run();
}
run_sheetsync_for_woocommerce();
