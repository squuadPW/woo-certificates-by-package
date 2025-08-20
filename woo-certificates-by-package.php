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

// Si este archivo es accedido directamente, aborta.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require plugin_dir_path(__FILE__) . 'settings.php';
require plugin_dir_path(__FILE__) . 'public/functions.php';
require plugin_dir_path(__FILE__) . 'admin/functions.php';
