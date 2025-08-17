<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://parsamirzaie.com
 * @since      1.0.0
 *
 * @package    Sheetsync_For_Woocommerce
 * @subpackage Sheetsync_For_Woocommerce/admin
 */

namespace Parsamirzaie\SheetsyncForWoocommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sheetsync_For_Woocommerce
 * @subpackage Sheetsync_For_Woocommerce/admin
 * @author     Parsa Mirzaie <Mirzaie_parsa@protonmail.ch>
 */
class Sheetsync_For_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sheetsync_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sheetsync_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$asset_file = include SFW_PLUGIN_DIR . '/build/index.asset.php';

		wp_enqueue_script(
			'react-for-sheetsync-for-woocommerce',
			SFW_PLUGIN_URL . '/build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			array(
				'in_footer' => true,
			)
		);

		wp_enqueue_style( 'wp-components' );
	}

	/**
	 * Registers a new admin menu item for the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'SheetSync', 'sheetsync-for-woocommerce' ),
			__( 'SheetSync', 'sheetsync-for-woocommerce' ),
			'manage_options',
			'sheetsync-for-woocommerce',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-site',
			100
		);
	}

	/**
	 * Renders the HTML for the main admin page.
	 *
	 * This function serves as the callback for the admin menu page. It
	 * creates a container for the React-based settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_admin_page() {
		printf(
			'<div class="wrap" id="sheetsync-for-woocommerce-settings">%s</div>',
			esc_html__( 'Loading…', 'sheetsync-for-woocommerce' )
		);
	}
}
