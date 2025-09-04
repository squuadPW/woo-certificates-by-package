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
        // Add custom menu items to the "My Account" page.
        add_filter('woocommerce_account_menu_items', array($this, 'add_plugin_account_links'), 40);
        // Render content for the custom endpoints.
        add_action('woocommerce_account_certificates_endpoint', array($this, 'render_certificates_content'));
        add_action('woocommerce_account_courses_endpoint', array($this, 'render_courses_content'));
        // Handle form submissions for course CRUD operations.
        add_action('template_redirect', array($this, 'handle_course_crud'));
        // Register custom endpoints.
        add_action('init', array($this, 'add_plugin_endpoints'));
        // Add custom query variables to handle pagination.
        add_filter('query_vars', array($this, 'add_plugin_query_vars'));
        // It's triggered just before a product is added to the cart. Its purpose is to attach additional metadata to a cart item.
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_custom_data_to_cart_item'), 10, 2);
        // Overwrites the original price of the certified product, ensuring that the custom price is applied.
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_custom_price_to_cart_item'), 10, 1);
        // Allows you to modify the name of a product as it appears on the cart page.
        add_filter('woocommerce_cart_item_name', array($this, 'display_course_name_in_cart'), 10, 2);
        // Generates a personalized notification, right after a product has been added to the cart.
        add_action('woocommerce_add_to_cart', array($this, 'add_custom_add_to_cart_notice'), 10, 6);
        // Deletes the original message, ensuring only the custom notification is displayed.
        add_filter('wc_add_to_cart_message', array($this, 'hide_default_add_to_cart_notice'), 10, 2);
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
        // Get the current logged-in user's data
        $current_user = wp_get_current_user();
        $user_name = $current_user->display_name;
        // Pass messages and user data from PHP to JavaScript
        $data_to_pass = array(
            'messages' => array(
                'course_name_required' => __('The course name is required.', 'woocertificatespackage'),
                'course_name_maxlength' => __('The course name cannot exceed 300 characters.', 'woocertificatespackage'),
                'academic_hours_invalid' => __('Academic hours must be a number greater than zero.', 'woocertificatespackage'),
                'course_date_past' => __('The date cannot be earlier than the current one.', 'woocertificatespackage'),
                'certification_fee_type_invalid' => __('Please select a valid fare type.', 'woocertificatespackage'),
                'price_per_student_invalid' => __('The price must be a number greater than zero.', 'woocertificatespackage'),
                'certification_fee_value_invalid' => __('The rate value must be a number greater than zero.', 'woocertificatespackage'),
                'is_percentage_rate_valid' => __('The rate value cannot be greater than 100 if the type is "Percentage".', 'woocertificatespackage'),
            ),
            'user_name' => $user_name,
            'deleteConfirmText' => __('Are you sure you want to delete this course? This action cannot be undone.', 'woocertificatespackage'),
        );
        wp_localize_script('woocerti-public-script', 'woocerti_data', $data_to_pass);
    }

    /**
     * Registers the custom endpoints for the account page.
     */
    public function add_plugin_endpoints() {
        add_rewrite_endpoint('courses', EP_PAGES);
        add_rewrite_endpoint('certificates', EP_PAGES);
    }

    /**
     * Add custom query variables to WordPress.
     *
     * @param array $vars The array of query variables.
     * @return array
     */
    public function add_plugin_query_vars($vars) {
        $vars[] = 'pageds';
        return $vars;
    }

    /**
     * Adds custom menu items to the WooCommerce account menu.
     *
     * @param array $menu_links Existing menu links.
     * @return array Modified menu links.
     */
    public function add_plugin_account_links($menu_links) {
        // Define new links.
        $new_links = array();
        // Add a Courses link only for 'institute' users.
        if (current_user_can(WOOCERTI_ROLE_USER_ALIANZA)) {
            $new_links['courses'] = __('Courses', 'woocertificatespackage');
        }
        // Add a Certificates link.
        $new_links['certificates'] = __('Certificates', 'woocertificatespackage');
        // Insert new links after the 'dashboard' item.
        $dashboard = array_slice($menu_links, 0, 1, true); // Get the 'dashboard' link.
        $rest = array_slice($menu_links, 1, null, true); // Get the rest of the links.

        return array_merge($dashboard, $new_links, $rest);
    }

    /**
     * Renders the content for the 'Certificates' page using a template.
     */
    public function render_certificates_content() {
        if (!is_user_logged_in()) {
            return;
        }
        // Certificate product slug.
        $certificate_product_id = get_page_by_path(WOOCERTI_SLUG_PRODUCT_DEFAULT, OBJECT, 'product')->ID;

        if (!$certificate_product_id) {
            echo '<p>'.__('The certificate product could not be found.', 'woocertificatespackage').'</p>';
            return;
        }

        // Get the current user's ID.
        $user_id = get_current_user_id();

        // Get the user's completed orders.
        $customer_orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => 'completed',
            'limit' => -1,
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

    /**
     * Renders the CRUD content for the 'Courses' page.
     */
    public function render_courses_content() {
        // Check if the user has the 'institutes' role.
        if (!current_user_can(WOOCERTI_ROLE_USER_ALIANZA)) {
            echo '<div class="woocommerce-error">'.__('You do not have permission to view this page.', 'woocertificatespackage').'</div>';
            return;
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'view';
        $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

        // Check if a specific course exists for the current user.
        if ($course_id > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix.'courses';
            $user_id = get_current_user_id();
            $course = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_name` WHERE id_course = %d AND id_user = %d", $course_id, $user_id));
            if (!$course) {
                echo '<div class="woocommerce-error">'.__('Course not found.', 'woocertificatespackage').'</div>';
                $action = 'view'; // Fallback to list view.
            }
        }

        switch ($action) {
            case 'create':
            case 'edit':
                $this->render_course_form($course_id, isset($course) ? $course : null);
                break;
            case 'buy':
                $this->render_buy_certificates_template($course_id, $course);
                break;
            case 'view':
            default:
                $this->render_course_list();
                break;
        }
    }

    /**
     * Renders the list of courses for the current user.
     */
    private function render_course_list() {
        global $wpdb;
        $table_name = $wpdb->prefix.'courses';
        $user_id = get_current_user_id();

        // Pagination settings
        $posts_per_page = WOOCERTI_POSTS_PER_PAGE;
        $current_page = max(1, get_query_var('pageds')); // Gets the current page, default is 1
        $offset = ($current_page - 1) * $posts_per_page; // Calculates the offset for the query

        // Query to get the total number of courses (for pagination)
        $total_courses = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE id_user = %d", $user_id));
        $total_pages = ceil($total_courses / $posts_per_page); // Calcula el total de páginas

        // Check to get the courses on the current page
        $courses = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM `$table_name`
            WHERE id_user = %d
            ORDER BY date_created DESC
            LIMIT %d OFFSET %d
        ", $user_id, $posts_per_page, $offset));

        $endpoint_url = wc_get_account_endpoint_url('courses');

        // Include the template file.
        $template_file = WOOCERTI_PLUGIN_DIR.'public/templates/courses-list.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>'.__('Course list template file not found.', 'woocertificatespackage').'</p>';
        }
    }

    /**
     * Renders the form for creating or editing a course.
     *
     * @param int $course_id
     * @param object|null $course
     */
    private function render_course_form($course_id = 0, $course = null) {
        $is_edit = ($course_id > 0 && $course);
        $form_title = $is_edit ? __('Edit Draft Course', 'woocertificatespackage') : __('Create New Course', 'woocertificatespackage');
        $submit_label = $is_edit ? __('Update Draft', 'woocertificatespackage') : __('Save Draft', 'woocertificatespackage');

        // Form fields initialization
        $course_name = $is_edit ? $course->course_name : '';
        $academic_hours = $is_edit ? $course->academic_hours : '';
        $tutor_instructor = $is_edit ? $course->tutor_instructor : '';
        $location = $is_edit ? $course->location : '';
        $course_date = $is_edit ? $course->course_date : '';
        $academic_program = $is_edit ? $course->academic_program : '';
        $price_per_student = $is_edit ? $course->price_per_student : '';
        $certification_fee_type = $is_edit ? $course->certification_fee_type : 'Fixed';
        $certification_fee_value = $is_edit ? $course->certification_fee_value : '';
        // $status = $is_edit ? $course->status : 'Pending';

        $endpoint_url = wc_get_account_endpoint_url('courses');

        $template_file = WOOCERTI_PLUGIN_DIR.'public/templates/course-form.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>'.__('Course form template file not found.', 'woocertificatespackage').'</p>';
        }
    }

    /**
     * Renders the template for purchasing certificates for a course.
     *
     * @param int $course_id - The ID of the course.
     * @param object $course - The course object.
     */
    private function render_buy_certificates_template($course_id, $course) {
        $certificate_product = self::search_product_by_attributes(WOOCERTI_NAME_PRODUCT_DEFAULT, WOOCERTI_SLUG_PRODUCT_DEFAULT, WOOCERTI_NAME_CATEGORY_DEFAULT);

        if (!$certificate_product) {
            echo '<div class="woocommerce-error">'.__('The certificate product could not be found.', 'woocertificatespackage').'</div>';
            return;
        }

        $price = 0;

        if ($course->certification_fee_type === 'Fixed') {
            $price = $course->certification_fee_value;
        } elseif ($course->certification_fee_type === 'Percentage') {
            $price = ($course->price_per_student * $course->certification_fee_value) / 100;
        } else {
            echo '<div class="woocommerce-error">'.__('An error occurred with the course fee type.', 'woocertificatespackage').'</div>';
            return;
        }

        $certificate_product_data = array(
            'name' => $certificate_product->get_name(),
            'price' => $price,
        );

        // Upload the template file for purchase.
        $template_file = WOOCERTI_PLUGIN_DIR.'public/templates/buy-certificates-form.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>'.__('Buy certificates template file not found.', 'woocertificatespackage').'</p>';
        }
    }

    /**
     * Searches for a single WooCommerce product by its name, slug, and category.
     *
     * @param string $product_name - The name of the product to search for.
     * @param string $product_slug - The slug of the product. This should be a URL-friendly string.
     * @param string $product_category - The slug of the product category.
     * @return WC_Product|false Returns a WC_Product object if a product is found, or false if no matching product is found.
     */
    public function search_product_by_attributes($product_name, $product_slug, $product_category) {
        // Prepare the arguments for the consultation
        $args = array(
            'limit' => 1,
            'post_status' => 'publish',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $product_category,
                ),
            ),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_product_name',
                    'value' => $product_name,
                    'compare' => '=',
                ),
                array(
                    'key' => '_product_slug',
                    'value' => $product_slug,
                    'compare' => '=',
                ),
            ),
        );

        // Create a new instance of WC_Product_Query
        $query = new WC_Product_Query($args);
        $productos = $query->get_products();

        if (!empty($productos)) {
            // Return the first product found
            return $productos[0];
        } else {
            // No products found
            return false;
        }
    }

    /**
     * Handles the CRUD operations for courses.
     */
    public function handle_course_crud() {
        if (!current_user_can(WOOCERTI_ROLE_USER_ALIANZA) || !is_account_page() || get_query_var('courses') === false) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix.'courses';
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $user_initials = '';
        if ($current_user) {
            $names = explode(' ', $current_user->display_name);
            foreach ($names as $name) {
                $user_initials .= strtoupper(substr($name, 0, 1));
            }
            $user_initials .= $user_id;
        }

        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['course_id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'delete_course')) {
                $course_id = intval($_GET['course_id']);
                if ($course_id > 0) {
                    $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM `{$table_name}` WHERE id_course = %d AND id_user = %d", $course_id, $user_id));

                    if ($current_status !== 'Draft') {
                        wc_add_notice(__('You can only delete courses with the status "Draft"', 'woocertificatespackage'), 'error');
                    } else {
                        $wpdb->delete(
                            $table_name,
                            array(
                                'id_course' => $course_id,
                                'id_user' => $user_id
                            )
                        );
                        wc_add_notice(__('Course draft successfully deleted.', 'woocertificatespackage'));
                    }
                    // Redirect to avoid resubmission
                    wp_safe_redirect(wc_get_account_endpoint_url('courses'));
                    exit;
                }
            }
        }

        // Handle save (create/update) action
        if (isset($_POST['save_course']) || isset($_POST['save_draft'])) {
            if (!isset($_POST['course_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['course_nonce']), 'save_course_data')) {
                wc_print_notice(__('Security check failed. Please try again.', 'woocertificatespackage'), 'error');
                return;
            }

            $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

            if ($course_id > 0) {
                $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM `{$table_name}` WHERE id_course = %d AND id_user = %d", $course_id, $user_id));

                if ($current_status !== 'Draft') {
                    wc_add_notice(__('You can only edit courses with the status "Draft"', 'woocertificatespackage'), 'error');
                    // Redirect to avoid resubmission
                    wp_safe_redirect(wc_get_account_endpoint_url('courses'));
                    exit;
                }
            }

            // Determine the status based on the button clicked.
            $status_to_save = isset($_POST['save_draft']) ? 'Draft' : 'Pending';

            // Sanitize and validate all form fields.
            $course_name = sanitize_text_field($_POST['course_name']);
            $academic_hours = floatval($_POST['academic_hours']);
            $tutor_instructor = sanitize_text_field($_POST['tutor_instructor']);
            $location = sanitize_text_field($_POST['location']);
            $course_date = sanitize_text_field($_POST['course_date']);
            $academic_program = sanitize_textarea_field($_POST['academic_program']);
            $price_per_student = floatval($_POST['price_per_student']);
            $certification_fee_type = sanitize_text_field($_POST['certification_fee_type']);
            $certification_fee_value = floatval($_POST['certification_fee_value']);
            $current_date = current_time('mysql');

            // --- VALIDATION: Ensure price and fee value are not negative. ---
            if ($price_per_student < 0 || $certification_fee_value < 0) {
                wc_add_notice(__('Values cannot be negative.', 'woocertificatespackage'), 'error');
                return;
            }

            $data = array(
                'course_name' => $course_name,
                'academic_hours' => $academic_hours,
                'tutor_instructor' => $tutor_instructor,
                'location' => $location,
                'course_date' => $course_date,
                'academic_program' => $academic_program,
                'price_per_student' => $price_per_student,
                'certification_fee_type' => $certification_fee_type,
                'certification_fee_value' => $certification_fee_value,
                'date_updated' => $current_date,
            );

            if ($course_id > 0) {
                // Update existing course
                $wpdb->update(
                    $table_name,
                    $data,
                    array(
                        'id_course' => $course_id,
                        'id_user' => $user_id,
                    )
                );
            } else {
                // Generate a unique course code for a new course
                $course_name_parts = explode(' ', $course_name);
                $course_acronym = '';
                foreach ($course_name_parts as $part) {
                    $course_acronym .= strtoupper(substr($part, 0, 1));
                }
                $unique_id = strtoupper(wp_generate_password(6, false, false));
                $course_code = 'C-'.$course_acronym.'-'.$user_initials.'-'.$unique_id;

                $data['code'] = $course_code;
                // Insert new course
                $data['id_user'] = $user_id;
                $data['date_created'] = $current_date;
                $data['status'] = $status_to_save;
                $wpdb->insert(
                    $table_name,
                    $data
                );
            }

            // Redirect to avoid form resubmission
            // wc_add_notice(__('Course saved successfully.', 'woocertificatespackage'), 'success');
            wp_safe_redirect(wc_get_account_endpoint_url('courses'));
            exit;
        }
    }

    /**
     * Add custom data (course and price) to the cart item, but only for products in the 'Certificate' category.
     *
     * @param array $cart_item_data - Cart item data.
     * @param int $product_id - Product ID.
     * @return array Cart item data modified.
     */
    public function add_custom_data_to_cart_item($cart_item_data, $product_id) {
        $product = wc_get_product($product_id);

        // Checks if the product exists and belongs to the defined category.
        if ($product && has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
            // If the product is in the correct category, add the custom data.
            if (isset($_POST['course_id']) && isset($_POST['woocerti_custom_price'])) {
                $cart_item_data['woocerti_custom_data'] = array(
                    'course_id' => intval($_POST['course_id']),
                    'custom_price' => floatval($_POST['woocerti_custom_price']),
                );
            }
        }

        // Returns the cart data, either modified or unchanged.
        return $cart_item_data;
    }

    /**
     * Apply the custom price to the cart item, but only if it's in the 'Certificate' category.
     *
     * @param object $cart - WooCommerce Cart Object.
     */
    public function apply_custom_price_to_cart_item($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (did_action('woocommerce_before_calculate_totals')) {
            foreach ($cart->get_cart() as $cart_item) {
                // Check if the cart item has our personalized data
                if (isset($cart_item['woocerti_custom_data']['custom_price'])) {
                    $product = $cart_item['data'];
                    // Checks if the product exists and belongs to the defined category.
                    if ($product && has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product->get_id())) {
                        // Overwrites the price of the product in the cart.
                        $cart_item['data']->set_price($cart_item['woocerti_custom_data']['custom_price']);
                    }
                }
            }
        }
    }

    /**
     * Displays the course name in the cart for the certificate product, only if it is from the correct category.
     *
     * @param string $product_name - The name of the original product.
     * @param array $cart_item_data - The cart item data.
     * @return string The name of the modified product.
     */
    public function display_course_name_in_cart($product_name, $cart_item_data) {
        // Check if the cart item has our personalized data.
        if (isset($cart_item_data['woocerti_custom_data']['course_id'])) {
            $product_id = $cart_item_data['product_id'];
            // Check if the product is from the 'Certificate' category.
            if (has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
                global $wpdb;
                $table_name = $wpdb->prefix.'courses';
                $course_id = $cart_item_data['woocerti_custom_data']['course_id'];

                // Gets the name of the course from the database.
                $course_name = $wpdb->get_var($wpdb->prepare("SELECT course_name FROM `$table_name` WHERE id_course = %d", $course_id));

                // If the course name was found, it is added to the product name.
                if ($course_name) {
                    $product_name = __('Certificate', 'woocertificatespackage').': '.esc_html($course_name);
                }
            }
        }
        return $product_name;
    }

    /**
     * Display a custom notification after adding a product to the cart,
     * but only if it's from the "Certified" category.
     *
     * @param string $cart_item_key - The key of the cart item.
     * @param int $product_id - The product ID.
     * @param int $quantity - The quantity of the product.
     * @param int $variation_id - The variation ID.
     * @param array $variation - The variation data.
     * @param array $cart_item_data - Los datos del ítem del carrito.
     */
    public function add_custom_add_to_cart_notice($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // Check if the product is from the 'Certificate' category.
        if (has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
            // Check if the cart item has our personalized data.
            if (isset($cart_item_data['woocerti_custom_data']['course_id'])) {
                global $wpdb;
                $table_name = $wpdb->prefix.'courses';
                $course_id = $cart_item_data['woocerti_custom_data']['course_id'];

                $course_name = $wpdb->get_var($wpdb->prepare("SELECT course_name FROM `$table_name` WHERE id_course = %d", $course_id));

                if ($course_name) {
                    // Use _n() to handle plural and singular.
                    $message = sprintf(
                        _n(
                            '1 certificate for the course "%2$s" has been added to your cart.',
                            '%1$d certificates for the course "%2$s" have been added to your cart.',
                            $quantity,
                            'woocertificatespackage'
                        ),
                        $quantity,
                        esc_html($course_name)
                    );
                    // Add the message to the WooCommerce notification queue.
                    wc_add_notice($message);
                }
            }
        }
    }

    /**
     * Hide the default WooCommerce message for the certificate product,
     * only if it is from the correct category.
     *
     * @param string $message - Default message.
     * @param int $product_id - Product ID added.
     * @return string Empty message to hide the notification.
     */
    public function hide_default_add_to_cart_notice($message, $product_id) {
        // Gets the product object.
        $product = wc_get_product($product_id);

        // Checks if the product exists and belongs to the defined category.
        if ($product && has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
            // Returns an empty message to hide the default notification.
            return '';
        }
        // If it's not our product, return the original WooCommerce message.
        return $message;
    }

}