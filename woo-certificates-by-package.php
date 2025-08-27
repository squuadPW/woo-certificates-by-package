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

// We need them available for the activation/deactivation hooks.
require_once WOOCERTI_PLUGIN_DIR.'settings.php';
require_once WOOCERTI_PLUGIN_DIR.'public/functions.php';
require_once WOOCERTI_PLUGIN_DIR.'admin/functions.php';

// We register the activation/deactivation hooks
register_activation_hook(__FILE__, array('Woocerti_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Woocerti_Deactivator', 'deactivate'));


/**
 * Main function to run the plugin after all plugins have been loaded.
 */
function woocerti_run_plugin() {
    /**
     * WooCommerce Check
     * Please check if the WooCommerce main class exists before continuing.
     */
    if (!class_exists('WooCommerce')) {
        return;
    }

    /**
     * Main plugin class for initializing all functionality.
     * This class follows the Singleton pattern.
     */
    final class Woocerti_Core {

        /**
         * The only instance of the class.
         *
         * @var Woocerti_Core
         */
        private static $instance;

        /**
         * Gets the only instance of the class.
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
            // Initializes public and administrative functionality classes from the main class.
            new Woocerti_Public();
            new Woocerti_Admin();
        }
    }

    // Initializes the main class of the plugin.
    Woocerti_Core::get_instance();
}

// Connect our main function to the 'plugins_loaded' action.
add_action('plugins_loaded', 'woocerti_run_plugin');