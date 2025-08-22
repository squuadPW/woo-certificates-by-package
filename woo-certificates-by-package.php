<?php
/**
 * Plugin Name:       WooCommerce Certificates by Package
 * Plugin URI:        https://edusof/woo-certificates-by-package
 * Description:       Generates and manages certificates as products in WooCommerce.
 * Version:           1.0.0
 * Author:            Edusof
 * Author URI:        https://edusof.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocertificatespackage
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants.
define('WOOCERTI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOOCERTI_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class to bootstrap all functionalities.
 * This class follows the Singleton pattern.
 */
final class Woocerti_Core {

	/**
	 * The single instance of the class.
	 *
	 * @var Woocerti_Core
	 */
	private static $instance;

	/**
	 * Main Woocerti_Core Instance.
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @static
	 * @return Woocerti_Core
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct creation of the object.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include all necessary files.
	 */
	private function includes() {
		require_once WOOCERTI_PLUGIN_DIR.'settings.php';
		require_once WOOCERTI_PLUGIN_DIR.'public/functions.php';
		require_once WOOCERTI_PLUGIN_DIR.'admin/functions.php';
	}

	/**
	 * Setup all WordPress hooks.
	 */
	private function hooks() {
		// Activation and deactivation hooks.
		register_activation_hook(__FILE__, array('Woocerti_Activator', 'activate'));
		register_deactivation_hook(__FILE__, array('Woocerti_Deactivator', 'deactivate'));
	}
}

/**
 * Initialize the main plugin class.
 */
function woocerti_init_plugin() {
	Woocerti_Core::get_instance();
}

add_action('plugins_loaded', 'woocerti_init_plugin');