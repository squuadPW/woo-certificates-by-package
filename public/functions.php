<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class to handle the public-facing side of the plugin.
 */
class Woocerti_Public {

    /**
     * Constructor.
     */
    public function __construct() {
        // Enqueue scripts and styles.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));

        // Add 'Certificates' link to WooCommerce 'My Account' menu.
        add_filter('woocommerce_account_menu_items', array($this, 'add_certificates_link'), 40);

        // Register the new endpoint for the 'Certificates' page.
        add_action('init', array($this, 'add_certificates_endpoint'));

        // Render content for the 'Certificates' endpoint.
        add_action('woocommerce_account_certificates_endpoint', array($this, 'render_certificates_content'));
    }

    /**
     * Enqueues public-facing CSS and JavaScript files.
     */
    public function enqueue_public_assets() {
        // Use the asset version defined in settings.php.
        $version = WOOCERTI_VERSION_ASSETS;

        // Enqueue CSS file.
        wp_enqueue_style('woocerti-public-style', WOOCERTI_PLUGIN_URL.'public/assets/css/style.css', array(), $version, 'all');

        // Enqueue JS file.
        wp_enqueue_script('woocerti-public-script', WOOCERTI_PLUGIN_URL.'public/assets/js/main.js', array('jquery'), $version, true);
    }

    /**
     * Adds a 'Certificates' link to the WooCommerce account menu.
     *
     * @param array $menu_links Existing menu links.
     * @return array Modified menu links.
     */
    public function add_certificates_link($menu_links) {
        $new_link = array('certificates' => __('Certificates', 'woocertificatespackage'));
        $new_menu_links = array_slice($menu_links, 0, 5, true)
                        + $new_link
                        + array_slice($menu_links, 5, null, true);
        return $new_menu_links;
    }

    /**
     * Registers the 'certificates' endpoint for the account page.
     */
    public function add_certificates_endpoint() {
        add_rewrite_endpoint('certificates', EP_PAGES);
    }

    /**
     * Renders the content for the 'Certificates' page using a template.
     */
    public function render_certificates_content() {
        if (!is_user_logged_in()) {
            return;
        }
        // Certificate product slug.
        $certificate_slug = 'certificado-academico-virtual';
        $certificate_product_id = get_page_by_path($certificate_slug, OBJECT, 'product')->ID;

        if (!$certificate_product_id) {
            echo '<p>'.__('The certificate product could not be found.', 'woocertificatespackage').'</p>';
            return;
        }

        // Get the current user's ID.
        $user_id = get_current_user_id();

        // Get the user's completed orders.
        $customer_orders = wc_get_orders(array(
            'customer' => $user_id,
            'status'   => 'completed',
            'limit'    => -1,
        ));

        $certificates = array();

        // Iterate over orders to find the certificate product.
        if ($customer_orders) {
            foreach ($customer_orders as $order) {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    if ($product_id == $certificate_product_id) {
                        $certificates[] = array(
                            'product_name' => $item->get_name(),
                            'order_id' => $order->get_id(),
                        );
                    }
                }
            }
        }
        // Load the template file.
        $template_file = WOOCERTI_PLUGIN_DIR.'public/templates/certificates.php';

        if (file_exists($template_file)) {
            // Include the template and pass the $certificates variable.
            include $template_file;
        } else {
            echo '<p>'.__('Certificate template file not found.', 'woocertificatespackage').'</p>';
        }
    }
}

new Woocerti_Public();