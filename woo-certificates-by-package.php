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

// Include the custom WP_List_Table class for the admin panel.
require_once WOOCERTI_PLUGIN_DIR.'admin/class-courses-list-table.php';

/**
 * Prevents deletion of the 'Certificates' category to protect plugin functionality.
 */
function woocerti_prevent_category_deletion($term_id, $taxonomy) {
    // If the taxonomy is not 'product_cat', we do nothing.
    if ($taxonomy !== 'product_cat') {
        return;
    }

    $protected_slug = WOOCERTI_SLUG_CATEGORY_DEFAULT;
    $term = get_term($term_id, $taxonomy);

    // If the term exists and its slug matches the protected one, we return an error to stop the deletion.
    if ($term && $term->slug === $protected_slug) {
        // Stores the error message in a temporary (transient) variable.
        set_transient(
            'woocerti_deletion_error',
            __('The "Certificates" category cannot be deleted because it is required for the plugin to function.', 'woocertificatespackage'),
        );
        wp_safe_redirect(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'));
        exit;
    }

    return null;
}
add_filter('pre_delete_term', 'woocerti_prevent_category_deletion', 99, 2);

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


/**
 * Generates and updates certificate records in the database based on the status of a WooCommerce order.
 *
 * @param int $order_id - The order ID.
 * @param string $old_status - The previous state of the order.
 * @param string $new_status - The new state of the order.
 * @param WC_Order $order - The object of the order.
 */
function generate_certificate_records($order_id, $old_status, $new_status, $order) {
    global $wpdb;
    $table_name = $wpdb->prefix.'total_certificates';
    $current_date = current_time('mysql');

    if ($new_status === 'completed') {
        // Go through each item in the order.
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            // Checks if the product exists and belongs to the defined category.
            if (has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
                // Gets the course's custom metadata.
                $custom_data = $item->get_meta('woocerti_custom_data_certificates', true);

                if ($custom_data && isset($custom_data['course_id'])) {
                    $course_id = $custom_data['course_id'];
                    $quantity = $item->get_quantity();

                    $data = array(
                        'quantity_purchased' => $quantity,
                        'status' => 'Active',
                        'date_updated' => $current_date,
                    );

                    $certificate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id_wc_order = %d AND id_course = %d", $order_id, $course_id));

                    if ($certificate) {
                        // If it exists, we update the record
                        $wpdb->update(
                            $table_name,
                            $data,
                            array('id_certificate ' => $certificate->id_certificate)
                        );
                    } else {
                        // If it doesn't exist, we create a new record
                        $data['id_wc_order'] = $order_id;
                        $data['id_course'] = $course_id;
                        $data['quantity_available'] = $quantity;
                        $data['date_created'] = $current_date;
                        $wpdb->insert(
                            $table_name,
                            $data
                        );
                    }
                }
            }
        }
    }

    if ($old_status === 'completed' && $new_status !== 'completed') {
        // Update the status of the certificates linked to the order
        $wpdb->update(
            $table_name,
            array('status' => ucfirst($new_status), 'date_updated' => $current_date),
            array('id_wc_order' => $order_id)
        );
    }
}
// Connect our function to the WooCommerce hook.
add_action('woocommerce_order_status_changed', 'generate_certificate_records', 10, 4);

/**
 * Retrieves all participant information and the course template_id using a double JOIN.
 * Allows other plugins to modify the output through a filter.
 *
 * @param int $record_id The ID of the record in the 'course_participants' join table.
 * @return array|null The combined data (participant + template_id), or null if not found.
 */
function woocerti_get_course_and_participant_data(int $record_id = 0) {
    global $wpdb;
    $junction_table = $wpdb->prefix . 'course_participants';
    $participants_table = $wpdb->prefix . 'participants';
    $courses_table = $wpdb->prefix . 'courses';
    $data = null;

    if ($record_id > 0) {
        $sql = $wpdb->prepare("
            SELECT p.*, c.template_id, c.id_course
            FROM {$junction_table} AS cp
            INNER JOIN {$participants_table} AS p
            ON cp.id_participant = p.id_participant
            INNER JOIN {$courses_table} AS c
            ON cp.id_course = c.id_course
            WHERE cp.id_course_participant = %d
        ", $record_id);

        $data = $wpdb->get_row($sql, ARRAY_A);
    }

    $data = apply_filters('woocerti_get_course_participant_data', $data, $record_id);

    return $data;
}

/**
* Retrieves all participant information.
* Allows other plugins to modify the output using a filter.
*
* @param int $id_participant The ID of the participant 'participants'.
* @return array|null The participant data, or null if none are found.
*/
function woocerti_get_participant_data(int $id_participant = 0) {
    global $wpdb;
    $participants_table = $wpdb->prefix . 'participants';
    $data = null;

    if ($id_participant > 0) {
        $sql = $wpdb->prepare("
            SELECT *
            FROM {$participants_table}
            WHERE id_participant = %d
        ", $id_participant);

        $data = $wpdb->get_row($sql, ARRAY_A);
    }

    $data = apply_filters('woocerti_get_participant_data', $data, $id_participant);

    return $data;
}

/**
* Retrieves all course information.
* Allows other plugins to modify the output using a filter.
*
* @param int $id_course The ID of the course 'courses'.
* @return array|null The course data, or null if none are found.
*/
function woocerti_get_course_data(int $id_course = 0) {
    global $wpdb;
    $courses_table = $wpdb->prefix . 'courses';
    $data = null;

    if ($id_course > 0) {
        $sql = $wpdb->prepare("
            SELECT *
            FROM {$courses_table}
            WHERE id_course = %d
        ", $id_course);

        $data = $wpdb->get_row($sql, ARRAY_A);
    }

    $data = apply_filters('woocerti_get_course_data', $data, $id_course);

    return $data;
}