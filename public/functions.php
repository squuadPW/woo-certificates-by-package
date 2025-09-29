<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

require WOOCERTI_PLUGIN_DIR.'vendor/autoload.php';

/**
 * Class to handle the public-facing side of the plugin.
 */
class Woocerti_Public {

    /**
     * Constructor.
     */
    public function __construct() {
        // Enqueue scripts and styles.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'), 99);
        // Add custom menu items to the "My Account" page.
        add_filter('woocommerce_account_menu_items', array($this, 'add_plugin_account_links'), 40);
        // Render content for the custom endpoints.
        add_action('woocommerce_account_certificates_endpoint', array($this, 'render_certificates_content'));
        add_action('woocommerce_account_courses_endpoint', array($this, 'render_courses_content'));
        add_action('woocommerce_account_issue-certificate_endpoint', array($this, 'render_issue_certificate_page'));
        // Handle form submissions for course CRUD operations.
        add_action('template_redirect', array($this, 'handle_requests'));
        // Handle AJAX request for single certificate form
        add_action('wp_ajax_woocerti_issue_single_certificate', array($this, 'handle_ajax_issue_certificate'));
        add_action('wp_ajax_nopriv_woocerti_issue_single_certificate', array($this, 'handle_ajax_issue_certificate'));
        add_action('wp_ajax_woocerti_add_to_cart_checkout', array($this, 'woocerti_add_to_cart_checkout'));
        // Register custom endpoints.
        add_action('init', array($this, 'add_plugin_endpoints'));
        // Add custom query variables to handle pagination.
        add_filter('query_vars', array($this, 'add_plugin_query_vars'));
        // It's triggered just before a product is added to the cart. Its purpose is to attach additional metadata to a cart item.
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_custom_data_to_cart_item'), 10, 2);
        // Overwrites the original price of the certified product, ensuring that the custom price is applied.
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_custom_price_to_cart_item'), 10, 1);
        // Save custom cart metadata to the order item at checkout.
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_custom_data_to_order_item'), 10, 4);
        // New filter to display the course name on the order page
        add_filter('woocommerce_order_item_name', array($this, 'display_course_name_on_order'), 10, 2);
        // Allows you to modify the name of a product as it appears on the cart page.
        add_filter('woocommerce_cart_item_name', array($this, 'display_course_name_in_cart'), 10, 2);
        // Generates a personalized notification, right after a product has been added to the cart.
        add_filter('wc_add_to_cart_message_html', array($this, 'add_custom_add_to_cart_notice'), 10, 2);
        // Correctly locates the template file, allowing a theme to override it.
        add_filter('woocommerce_locate_template', array($this, 'woocerti_locate_template'), 10, 3);
    }

    /**
     * Handles form submissions and redirects for all custom endpoints.
     */
    public function handle_requests() {
        if (isset($_POST['course_nonce']) && wp_verify_nonce($_POST['course_nonce'], 'save_course_data')) {
            $this->handle_course_crud();
        }

        if (isset($_POST['woocerti_issue_certificate_nonce']) && wp_verify_nonce($_POST['woocerti_issue_certificate_nonce'], 'woocerti_issue_certificate_action')) {
            if (isset($_FILES['student_list']) && $_FILES['student_list']['error'] === UPLOAD_ERR_OK) {
                $this->handle_certificate_issuance();
            }
        }
    }

    /**
     * Enqueues public-facing CSS and JavaScript files.
     */
    public function enqueue_public_assets() {
        // Use the asset version defined in settings.php.
        $version = WOOCERTI_VERSION_ASSETS;

        // Enqueue CSS file.
        wp_enqueue_style('woocerti-public-style', WOOCERTI_PLUGIN_URL.'public/assets/css/style.css', array(), $version, 'all');

        $is_target_page = get_query_var('issue-certificate') !== false && isset($_GET['mode']) && $_GET['mode'] === 'single';
        if ($is_target_page) {
            wp_deregister_style('intel-css');
            wp_deregister_script('intel-js');
            wp_enqueue_style('woocerti-intl-tel-input-style', WOOCERTI_PLUGIN_URL.'public/assets/css/intlTelInput.min.css', array(), '25.10.6');
            wp_enqueue_script('woocerti-intl-tel-input-script', WOOCERTI_PLUGIN_URL.'public/assets/js/libs/intlTelInput.min.js', array('jquery'), '25.10.6', true);
            wp_enqueue_script('woocerti-public-script', WOOCERTI_PLUGIN_URL.'public/assets/js/main.js', array('jquery', 'woocerti-intl-tel-input-script'), $version, true);
        } else {
            wp_enqueue_script('woocerti-public-script', WOOCERTI_PLUGIN_URL.'public/assets/js/main.js', array('jquery'), $version, true);
        }

        // Get the current logged-in user's data
        $current_user = wp_get_current_user();
        $user_name = $current_user->display_name;

        $nationalities = $this->get_sorted_nationalities();

        $my_account_page_id = get_option('woocommerce_myaccount_page_id');
        $my_account_url = ($my_account_page_id) ? get_permalink($my_account_page_id) : get_home_url();

        // Construye la URL base para el punto de acceso "issue-certificate"
        $issue_certificate_url = add_query_arg('issue-certificate', '', $my_account_url);

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
                'field_is_required' => __('This field is required.', 'woocertificatespackage'),
                'email_invalid' => __('Please enter a valid email address.', 'woocertificatespackage'),
                'file_is_empty' => __('Please select a file.', 'woocertificatespackage'),
                'file_size_exceeded' => __('The file cannot be larger than 5 MB.', 'woocertificatespackage'),
                'file_type_invalid' => __('Only Excel (.xls, .xlsx) or CSV files are accepted.', 'woocertificatespackage'),
                'selected_file' => __('Selected file: ', 'woocertificatespackage'),
                'document_type_required' => __('Please select a document type.', 'woocertificatespackage'),
                'phone_number_invalid' => __('Invalid phone number.', 'woocertificatespackage'),
                'ajax_error' => __('There was an error processing your request. Please try again.', 'woocertificatespackage'),
                'bulk_results_error' => __('An error occurred while processing the bulk upload results. Please try again.', 'woocertificatespackage'),
            ),
            'iti_phone' => array(
                'search_placeholder' => __('Search', 'woocertificatespackage'),
                'no_country_selected' => __('Select country', 'woocertificatespackage'),
                'country_list_aria_label' => __('List of countries', 'woocertificatespackage'),
                'clear_search_aria_label' => __('Clear search', 'woocertificatespackage'),
                'zero_search_results' => __('No results found', 'woocertificatespackage'),
            ),
            'text_results' => array(
                'document_number' => __('Document Number', 'woocertificatespackage'),
                'first_name' => __('First Name', 'woocertificatespackage'),
                'last_name' => __('Last Name', 'woocertificatespackage'),
                'email' => __('Email', 'woocertificatespackage'),
                'status' => __('Status', 'woocertificatespackage'),
                'issued' => __('Issued', 'woocertificatespackage'),
                'failed' => __('Failed', 'woocertificatespackage'),
                'error_details' => __('Error details:', 'woocertificatespackage'),
            ),
            'user_name' => $user_name,
            'woocerti_plugin_url' => WOOCERTI_PLUGIN_URL,
            'deleteConfirmText' => __('Are you sure you want to delete this course? This action cannot be undone.', 'woocertificatespackage'),
            'nationalities' => $nationalities,
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => esc_url(wc_get_checkout_url()),
            'currency_code' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'text_forms' => array(
                'btn_submit_issue_certificate' => __('Issue Certificate', 'woocertificatespackage'),
                'txt_btn_issuing' => __('Issuing...', 'woocertificatespackage'),
                'btn_view_issued_certificates' => __('View Issued Certificates', 'woocertificatespackage'),
            ),
            'endpoints' => array(
                'issue_certificate_page' => $issue_certificate_url,
            ),
        );
        wp_localize_script('woocerti-public-script', 'woocerti_data', $data_to_pass);
    }

    /**
     * Handle AJAX request for issuing a single certificate.
     */
    public function handle_ajax_issue_certificate() {
        global $wpdb;
        $participants_table = $wpdb->prefix.'participants';
        $course_participants_table = $wpdb->prefix.'course_participants';
        $errors = array();

        // Check for nonce security
        if (!isset($_POST['woocerti_issue_certificate_nonce']) || !wp_verify_nonce($_POST['woocerti_issue_certificate_nonce'], 'woocerti_issue_certificate_action')) {
            wp_send_json_error(array('messages' => array(__('Security check failed.', 'woocertificatespackage'))));
        }

        // Get and sanitize the form data
        $document_type = isset($_POST['single_student_document_type']) ? sanitize_text_field($_POST['single_student_document_type']) : '';
        $document_number = isset($_POST['single_student_document_number']) ? sanitize_text_field($_POST['single_student_document_number']) : '';
        $first_name = isset($_POST['single_student_first_name']) ? sanitize_text_field($_POST['single_student_first_name']) : '';
        $last_name = isset($_POST['single_student_last_name']) ? sanitize_text_field($_POST['single_student_last_name']) : '';
        $email = isset($_POST['single_student_email']) ? sanitize_email($_POST['single_student_email']) : '';
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $inssued_in = isset($_POST['single_student_inssued_in']) ? sanitize_text_field($_POST['single_student_inssued_in']) : '';
        $phone_number = isset($_POST['phone_full']) ? sanitize_text_field($_POST['phone_full']) : '';

        $allowed_types = ['passport', 'identification_document', 'ssn'];
        if (empty($document_type) || !in_array($document_type, $allowed_types)) {
            $errors['single_student_document_type'] = __('Please select a valid document type.', 'woocertificatespackage');
        }
        if (empty($first_name)) {
            $errors['single_student_first_name'] = __('This field is required.', 'woocertificatespackage');
        }
        if (empty($last_name)) {
            $errors['single_student_last_name'] = __('This field is required.', 'woocertificatespackage');
        }
        if (empty($document_number)) {
            $errors['single_student_document_number'] = __('This field is required.', 'woocertificatespackage');
        }
        if (empty($email)) {
            $errors['single_student_email'] = __('This field is required.', 'woocertificatespackage');
        } elseif (!is_email($email)) {
            $errors['single_student_email'] = __('Please enter a valid email address.', 'woocertificatespackage');
        }
        if ($document_type === 'identification_document') {
            $nationalities = $this->get_sorted_nationalities();

            if (empty($inssued_in) || !array_key_exists(strtoupper($inssued_in), $nationalities)) {
                $errors['single_student_inssued_in'] = __('This field is required.', 'woocertificatespackage');
            }
        } else {
            $inssued_in = "US";
        }

        // If there are errors, return them
        if (!empty($errors)) {
            wp_send_json_error(array('validations' => $errors));
        }

        // If validation passes, proceed with saving the data
        $existing_participant_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id_participant FROM `$participants_table` WHERE document_type = %s AND inssued_in = %s AND document_number = %s",
            $document_type,
            $inssued_in,
            $document_number
        ));

        $participant_id = 0;

        if ($existing_participant_id) {
            $participant_id = $existing_participant_id;
            $wpdb->update(
                $participants_table,
                array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone_number' => $phone_number,
                    'date_updated' => current_time('mysql')
                ),
                array('id_participant' => $participant_id)
            );
        } else {
            $wpdb->insert(
                $participants_table,
                array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'document_type' => $document_type,
                    'document_number' => $document_number,
                    'inssued_in' => $inssued_in,
                    'email' => $email,
                    'phone_number' => $phone_number,
                    'date_created' => current_time('mysql'),
                    'date_updated' => current_time('mysql')
                )
            );
            $participant_id = $wpdb->insert_id;
        }

        if ($participant_id) {
            $relation_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id_course_participant FROM `$course_participants_table` WHERE id_course = %d AND id_participant = %d",
                $course_id,
                $participant_id
            ));

            if ($relation_exists) {
                wp_send_json_error(array('messages' => array(__('This participant is already registered for this course.', 'woocertificatespackage'))));
            } else {
                $wpdb->insert(
                    $course_participants_table,
                    array(
                        'id_course' => $course_id,
                        'id_participant' => $participant_id,
                        'date_created' => current_time('mysql'),
                        'date_updated' => current_time('mysql')
                    )
                );
            }
        }

        // Issue the certificate if there are still certificates available for this course
        if ($this->woocerti_issue_certificate($course_id)) {
            wp_send_json_success(array('message' => __('Certificate issued successfully!', 'woocertificatespackage')));
        } else {
            wp_send_json_error(array('messages' => array(__('There are no certificates available for this course.', 'woocertificatespackage'))));
        }
        wp_die();
    }

    /**
     * Registers the custom endpoints for the account page.
     */
    public function add_plugin_endpoints() {
        add_rewrite_endpoint('courses', EP_PAGES);
        add_rewrite_endpoint('certificates', EP_PAGES);
        add_rewrite_endpoint('issue-certificate', EP_PAGES);
    }

    /**
     * Add custom query variables to WordPress.
     *
     * @param array $vars The array of query variables.
     * @return array
     */
    public function add_plugin_query_vars($vars) {
        $vars[] = 'pageds';
        $vars[] = 'action';
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
        // Add a Courses and Certificates link only for 'institute' users.
        if (current_user_can(WOOCERTI_ROLE_USER_ALIANZA)) {
            $new_links['courses'] = __('Courses', 'woocertificatespackage');
            $new_links['certificates'] = __('Certificates', 'woocertificatespackage');
        }
        // Insert new links after the 'dashboard' item.
        $dashboard = array_slice($menu_links, 0, 1, true); // Get the 'dashboard' link.
        $rest = array_slice($menu_links, 1, null, true); // Get the rest of the links.

        return array_merge($dashboard, $new_links, $rest);
    }

    /**
     * Renders the certificate content page in the "My Account" area.
     * This function retrieves and displays certificates grouped by course.
     */
    public function render_certificates_content() {
        if (!is_user_logged_in()) {
            return;
        }
        global $wpdb;
        $user_id = get_current_user_id();

        $certificates_table = $wpdb->prefix.'certificates';
        $courses_table = $wpdb->prefix.'courses';

        $posts_per_page = WOOCERTI_POSTS_PER_PAGE;
        $current_page = max(1, get_query_var('pageds'));
        $offset = ($current_page - 1) * $posts_per_page;

        $total_certificates_query = $wpdb->prepare("
            SELECT COUNT(DISTINCT c.id_course)
            FROM `$certificates_table` AS c
            INNER JOIN `$courses_table` AS co ON c.id_course = co.id_course
            WHERE co.id_user = %d
        ", $user_id);

        $total_records = $wpdb->get_var($total_certificates_query);
        $total_pages = ceil($total_records / $posts_per_page);

        $certificates_by_course = $wpdb->get_results(
            $wpdb->prepare("
                SELECT c.id_course, co.course_name, SUM(c.quantity_purchased) as quantity_purchased, (SUM(c.quantity_purchased) - SUM(c.quantity_available)) as total_issued
                FROM `$certificates_table` AS c
                INNER JOIN `$courses_table` AS co ON c.id_course = co.id_course
                WHERE co.id_user = %d
                GROUP BY co.id_course, co.course_name
                LIMIT %d OFFSET %d
            ", $user_id, $posts_per_page, $offset)
        );

        $endpoint_url = wc_get_account_endpoint_url('certificates');

        // Include the template file.
        $template_file = WOOCERTI_PLUGIN_DIR.'public/templates/certificates-list.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>'.__('Certificate list template file not found.', 'woocertificatespackage').'</p>';
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

        // Sorting logic: Default 'course_name' ASC
        $current_orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'course_name';
        $current_order = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'ASC';

        $allowed_orderby = array('course_name', 'status', 'date_created');
        if (!in_array($current_orderby, $allowed_orderby)) {
            $current_orderby = 'course_name';
        }
        if (!in_array($current_order, array('ASC', 'DESC'))) {
            $current_order = 'ASC';
        }

        // Pagination settings
        $posts_per_page = WOOCERTI_POSTS_PER_PAGE;
        $current_page = max(1, get_query_var('pageds')); // Gets the current page, default is 1
        $offset = ($current_page - 1) * $posts_per_page; // Calculates the offset for the query

        // Query to get the total number of courses (for pagination)
        $total_courses = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE id_user = %d", $user_id));
        $total_pages = ceil($total_courses / $posts_per_page);

        // Check to get the courses on the current page
        $courses = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM `$table_name`
            WHERE id_user = %d
            ORDER BY {$current_orderby} {$current_order}
            LIMIT %d OFFSET %d
        ", $user_id, $posts_per_page, $offset));

        $endpoint_url = wc_get_account_endpoint_url('courses');

        // Include the template, passing the variables.
        $template_file = WOOCERTI_PLUGIN_DIR.'public/templates/courses-list.php';
        if (file_exists($template_file)) {
            extract(array(
                'courses' => $courses,
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'endpoint_url' => $endpoint_url,
                'current_orderby' => $current_orderby,
                'current_order' => $current_order,
            ));
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
                'status' => $status_to_save,
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
                $cart_item_data['woocerti_custom_data_certificates'] = array(
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
                if (isset($cart_item['woocerti_custom_data_certificates']['custom_price'])) {
                    $product = $cart_item['data'];
                    // Checks if the product exists and belongs to the defined category.
                    if ($product && has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product->get_id())) {
                        // Overwrites the price of the product in the cart.
                        $cart_item['data']->set_price($cart_item['woocerti_custom_data_certificates']['custom_price']);
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
        if (isset($cart_item_data['woocerti_custom_data_certificates']['course_id'])) {
            $product_id = $cart_item_data['product_id'];
            // Check if the product is from the 'Certificate' category.
            if (has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
                global $wpdb;
                $table_name = $wpdb->prefix.'courses';
                $course_id = $cart_item_data['woocerti_custom_data_certificates']['course_id'];

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
     * Add a custom notification message after adding a product to the cart,
     * but only if it's from the 'Certificate' category.
     *
     * @param string $message - The HTML notification message.
     * @param array $cart_item_keys - Keys of the added cart items.
     * @return string The modified HTML notification message.
     */
    public function add_custom_add_to_cart_notice($message, $cart_item_keys) {
        if (isset($_REQUEST['add-to-cart'])) {
            $product_id = intval($_REQUEST['add-to-cart']);
            $product = wc_get_product($product_id);

            // Checks if the product exists and belongs to the defined category.
            if ($product && has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
                $course_id = isset($_REQUEST['course_id']) ? intval($_REQUEST['course_id']) : 0;
                $quantity = isset($_REQUEST['quantity']) ? intval($_REQUEST['quantity']) : 1;
                if ($course_id > 0) {
                    global $wpdb;
                    $table_name = $wpdb->prefix.'courses';
                    $course_name = $wpdb->get_var($wpdb->prepare("SELECT course_name FROM `$table_name` WHERE id_course = %d", $course_id));
                    if ($course_name) {
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

                        $message .= '<a href="'.esc_url(wc_get_account_endpoint_url('courses')).'" class="button wc-forward">'.__('Back to Courses', 'woocertificatespackage').'</a>';
                    }
                }
            }
        }
        // If it is not a certified product, we return the original message.
        return $message;
    }

    /**
     * Saves custom cart item data as order metadata,
     * but only if the product is from the 'Certificate' category.
     *
     * @param WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     */
    public function save_custom_data_to_order_item($item, $cart_item_key, $values, $order) {
        $product_id = $values['product_id'];

        // Check if the product is from the 'Certificate' category.
        if (has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
            // Check if the cart item has our personalized data.
            if (isset($values['woocerti_custom_data_certificates'])) {
                // Saves the data as order metadata.
                $item->add_meta_data('woocerti_custom_data_certificates', $values['woocerti_custom_data_certificates']);
            }
        }
    }

    /**
     * Displays the course name in the order details on the "Order Received" page,
     * but only if the product is from the 'Certificate' category.
     *
     * @param string $item_name - The name of the order item.
     * @param object $item - The object of the order item.
     * @return string The name of the modified item.
     */
    public function display_course_name_on_order($item_name, $item) {
        $product_id = $item->get_product_id();

        // Check if the product is from the 'Certificate' category.
        if (has_term(WOOCERTI_NAME_CATEGORY_DEFAULT, 'product_cat', $product_id)) {
            // Check if the order item has the custom course data.
            $course_data = $item->get_meta('woocerti_custom_data_certificates', true);

            if ($course_data && isset($course_data['course_id'])) {
                global $wpdb;
                $table_name = $wpdb->prefix.'courses';
                $course_id = $course_data['course_id'];

                $course_name = $wpdb->get_var($wpdb->prepare("SELECT course_name FROM `$table_name` WHERE id_course = %d", $course_id));

                if ($course_name) {
                    $item_name = __('Certificate', 'woocertificatespackage').': '.esc_html($course_name);
                }
            }
        }

        return $item_name;
    }

    /**
     * Finds and loads the correct template file, allowing a theme to override it.
     *
     * @param string $template - The template file path.
     * @param string $template_name - The name of the template being loaded.
     * @param string $template_path - The path to search for the template file.
     * @return string The path to the template file.
     */
    public function woocerti_locate_template($template, $template_name, $template_path) {
        $_template = $template;

        if (!$template_path) {
            $template_path = WC()->template_path();
        }

        // We search for the template in the plugin's template folder.
        $plugin_template_path = WOOCERTI_PLUGIN_DIR.'public/templates/'.$template_name;

        // Check if the template exists in the theme.
        $template = locate_template(
            array(
                $template_path.$template_name,
                $template_name
            )
        );

        // If the template is not found in the theme, load it from the plugin's folder.
        if (!$template && file_exists($plugin_template_path)) {
            $template = $plugin_template_path;
        }

        // Return the found template path.
        if (!$template) {
            $template = $_template;
        }

        return $template;
    }

    /**
     * Renders the page for issuing certificates, including the form and the results table.
     */
    public function render_issue_certificate_page() {
        global $wpdb;
        $courses_table = $wpdb->prefix.'courses';
        $user_id = get_current_user_id();
        $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

        // Check if the user wants to see the list of issued certificates
        if (isset($_GET['action']) && $_GET['action'] === 'list_issued' && $course_id) {
            // Get the table names with the WordPress prefix
            $certificates_table = $wpdb->prefix.'certificates';
            $participants_table = $wpdb->prefix.'participants';
            $course_participants_table = $wpdb->prefix.'course_participants';
            $courses_table = $wpdb->prefix.'courses';

            $posts_per_page = WOOCERTI_POSTS_PER_PAGE;
            $current_page = max(1, get_query_var('pageds'));
            $offset = ($current_page - 1) * $posts_per_page;

            $total_issued_students_query = $wpdb->prepare("
                SELECT COUNT(cp.id_course_participant)
                FROM `$course_participants_table` AS cp
                INNER JOIN
                    `$participants_table` AS p ON cp.id_participant = p.id_participant
                INNER JOIN
                    `$courses_table` AS c ON cp.id_course = c.id_course
                WHERE
                    cp.id_course = %d AND c.id_user = %d
            ", $course_id, $user_id);

            $total_records = $wpdb->get_var($total_issued_students_query);
            $total_pages = ceil($total_records / $posts_per_page);

            // Query to get the list of issued students for the course
            $issued_students_query = $wpdb->prepare("
                SELECT
                    cp.id_course_participant,
                    p.id_participant,
                    CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                    p.email,
                    p.document_number,
                    cp.date_created
                FROM `$course_participants_table` AS cp
                INNER JOIN `$participants_table` AS p ON cp.id_participant = p.id_participant
                INNER JOIN `$courses_table` AS c ON cp.id_course = c.id_course
                WHERE cp.id_course = %d AND c.id_user = %d
                ORDER BY cp.date_created DESC
                LIMIT %d OFFSET %d
            ", $course_id, $user_id, $posts_per_page, $offset);
            $issued_students = $wpdb->get_results($issued_students_query, ARRAY_A);

            $endpoint_url = wc_get_account_endpoint_url('issue-certificate');

            // Query to get the summary of purchased and available certificates
            $certificate_summary_query = $wpdb->prepare("
                SELECT
                    SUM(quantity_purchased) AS purchased,
                    SUM(quantity_available) AS available
                FROM
                    `$certificates_table`
                WHERE
                    id_course = %d
            ", $course_id);
            $certificate_summary = $wpdb->get_row($certificate_summary_query, ARRAY_A);

            // Get the course name
            $course_record = $wpdb->get_row($wpdb->prepare(
                "SELECT course_name FROM `$courses_table` WHERE id_course = %d",
                $course_id
            ));
            $course_name = $course_record ? $course_record->course_name : '';

            // $issued_count = $certificate_summary['purchased'] - $certificate_summary['available'];
            $certificate_summary['issued_count'] = $certificate_summary['purchased'] - $certificate_summary['available'];
            // var_dump($certificate_summary);

            wc_get_template(
                'issued-certificates-list.php',
                array(
                    'course_id' => $course_id,
                    'course_name' => $course_name,
                    'students' => $issued_students,
                    'certificate_summary' => $certificate_summary,
                    'total_pages' => $total_pages,
                    'endpoint_url' => $endpoint_url,
                    'current_page' => $current_page,
                )
            );
            return;
        }

        // Check if the course exists and belongs to the current user
        $course_record = $wpdb->get_row($wpdb->prepare(
            "SELECT course_name FROM `$courses_table` WHERE id_course = %d AND id_user = %d",
            $course_id,
            $user_id
        ));

        // If the course does not exist or does not belong to the user, redirect with an error.
        if (empty($course_record)) {
            wc_print_notice(__('Course not found or you do not have permission to access it.', 'woocertificatespackage'), 'error');
            echo '<script>window.location.href = "'.esc_url(wc_get_account_endpoint_url('courses')).'";</script>';
            return;
        }

        $course_name = $course_record->course_name;

        $nationalities = $this->get_sorted_nationalities();

        wc_get_template(
            'issue-certificate-form.php',
            array('course_id' => $course_id, 'course_name' => $course_name, 'nationalities' => $nationalities)
        );
    }

    /**
     * Handles the issuance of certificates from a bulk file upload.
     */
    public function handle_certificate_issuance() {
        global $wpdb;
        $participants_table = $wpdb->prefix.'participants';
        $course_participants_table = $wpdb->prefix.'course_participants';
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $file_data = $_FILES['student_list'];

        $column_map = [
            'document_type' => 'document_type',
            'document_number' => 'document_number',
            'inssued_in' => 'inssued_in',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'phone_number' => 'phone_number',
        ];

        $required_columns = [
            'document_type',
            'document_number',
            'inssued_in',
            'first_name',
            'last_name',
            'email',
        ];

        $results = [
            'success' => true,
            'issued_count' => 0,
            'failed_count' => 0,
            'detailed_results' => [],
            'general_messages' => [],
        ];

        $nationalities = $this->get_sorted_nationalities();

        try {
            $file_type = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file_data['tmp_name']);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($file_type);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file_data['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $header = array_shift($rows);
            $header_map = array_map('trim', $header);
            $header_map_flipped = array_flip($header_map);

            foreach ($required_columns as $col) {
                if (!isset($header_map_flipped[$col])) {
                    throw new Exception(sprintf(__('The column "%s" is missing from the file.', 'woocertificatespackage'), $col));
                }
            }

            $row_number = 1;
            foreach ($rows as $row) {
                $row_number++;
                $errors = [];
                $participant_data = [];

                foreach ($column_map as $header_name => $db_field) {
                    $column_index = $header_map_flipped[$header_name] ?? null;
                    if ($column_index !== null) {
                        $value = trim($row[$column_index]);
                        if ($db_field === 'email') {
                            $participant_data[$db_field] = sanitize_email($value);
                        } elseif ($db_field === 'phone_number') {
                            $participant_data[$db_field] = sanitize_text_field($value);
                        } else {
                            $participant_data[$db_field] = sanitize_text_field($value);
                        }
                    }
                }

                foreach ($required_columns as $col) {
                    if (empty($participant_data[$column_map[$col]])) {
                        $errors[] = sprintf(__('The "%s" field is required.', 'woocertificatespackage'), $col);
                    }
                }

                if (!is_email($participant_data['email'])) {
                    $errors[] = __('Invalid email format.', 'woocertificatespackage');
                }

                $document_type = $participant_data['document_type'];
                $inssued_in = $participant_data['inssued_in'];
                $allowed_types = ['passport', 'identification_document', 'ssn'];

                if (!in_array($document_type, $allowed_types)) {
                    $errors[] = __('Please select a valid document type.', 'woocertificatespackage');
                }

                if ($document_type === 'identification_document') {
                    if (empty($inssued_in) || !array_key_exists(strtoupper($inssued_in), $nationalities)) {
                        $errors[] = __('The "Issued In" field is required for the "Identification Document" type.', 'woocertificatespackage');
                    }
                } else {
                    $participant_data['inssued_in'] = 'US';
                }

                if (!empty($errors)) {
                    $results['failed_count']++;
                    $results['detailed_results'][] = [
                        'row_number' => $row_number,
                        'status' => 'failed',
                        'data' => $participant_data,
                        'errors' => $errors,
                    ];
                    continue;
                }

                $existing_participant_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id_participant FROM `$participants_table` WHERE document_type = %s AND document_number = %s AND inssued_in = %s",
                    $participant_data['document_type'],
                    $participant_data['document_number'],
                    $participant_data['inssued_in']
                ));

                $participant_id = 0;
                if ($existing_participant_id) {
                    $participant_id = $existing_participant_id;
                    $wpdb->update(
                        $participants_table,
                        array_merge($participant_data, ['date_updated' => current_time('mysql')]),
                        array('id_participant' => $participant_id)
                    );
                } else {
                    $wpdb->insert(
                        $participants_table,
                        array_merge($participant_data, ['date_created' => current_time('mysql'), 'date_updated' => current_time('mysql')])
                    );
                    $participant_id = $wpdb->insert_id;
                }

                if ($participant_id) {
                    $relation_exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id_course_participant FROM `$course_participants_table` WHERE id_course = %d AND id_participant = %d",
                        $course_id,
                        $participant_id
                    ));

                    if ($relation_exists) {
                        $results['failed_count']++;
                        $results['detailed_results'][] = [
                            'row_number' => $row_number,
                            'status' => 'failed',
                            'data' => $participant_data,
                            'errors' => [__('This participant is already registered for this course.', 'woocertificatespackage')],
                        ];
                        continue;
                    }

                    $wpdb->insert(
                        $course_participants_table,
                        [
                            'id_course' => $course_id,
                            'id_participant' => $participant_id,
                            'date_created' => current_time('mysql'),
                            'date_updated' => current_time('mysql')
                        ]
                    );

                    if ($this->woocerti_issue_certificate($course_id)) {
                        $results['issued_count']++;
                        $results['detailed_results'][] = [
                            'row_number' => $row_number,
                            'status' => 'issued',
                            'data' => $participant_data,
                            'message' => __('Certificate issued successfully!', 'woocertificatespackage'),
                        ];
                    } else {
                        $results['failed_count']++;
                        $results['detailed_results'][] = [
                            'row_number' => $row_number,
                            'status' => 'failed',
                            'data' => $participant_data,
                            'errors' => [__('There are no certificates available for this course.', 'woocertificatespackage')],
                        ];
                    }
                } else {
                    $results['failed_count']++;
                    $results['detailed_results'][] = [
                        'row_number' => $row_number,
                        'status' => 'failed',
                        'data' => $participant_data,
                        'errors' => [__('Error saving participant data.', 'woocertificatespackage')],
                    ];
                }
            }

            if ($results['issued_count'] === 0) {
                $results['success'] = false;
                $results['general_messages'][] = __('No certificates were issued due to errors in all rows or no certificates being available for the course.', 'woocertificatespackage');

                // If none were issued, the detailed result is saved in a transient variable.
                set_transient('woocerti_bulk_results', $results, HOUR_IN_SECONDS);

                // Redirects back to the page with 'bulk' mode
                $redirect_url = add_query_arg(['mode' => 'bulk', 'course_id' => $course_id], wc_get_account_endpoint_url('issue-certificate'));
                wp_safe_redirect($redirect_url);
                exit;
            }

            $results['general_messages'][] = sprintf(__('Batch processing complete. %1$d certificates were issued. %2$d students had errors.', 'woocertificatespackage'), $results['issued_count'], $results['failed_count']);

            // If at least one was issued, the detailed result is saved for display on the page.
            set_transient('woocerti_bulk_results', $results, HOUR_IN_SECONDS);

            $redirect_url = add_query_arg(['mode' => 'bulk', 'course_id' => $course_id], wc_get_account_endpoint_url('issue-certificate'));
            wp_safe_redirect($redirect_url);
            exit;

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            $results['success'] = false;
            $errors = array(__('Error parsing the file.', 'woocertificatespackage'), sprintf(__('Error: %s', 'woocertificatespackage'), $e->getMessage()));
            set_transient('woocerti_form_errors', $errors, HOUR_IN_SECONDS);
        } catch (Exception $e) {
            $results['success'] = false;
            $errors = array(__('General error.', 'woocertificatespackage'), sprintf(__('Error: %s', 'woocertificatespackage'), $e->getMessage()));
            set_transient('woocerti_form_errors', $errors, HOUR_IN_SECONDS);
        }
    }

    /**
     * Finds an available certificate record for the course and subtracts 1 from the available quantity.
     *
     * @param int $course_id - The ID of the course to which the certificate belongs.
     * @return bool Returns true if the certificate was issued, false otherwise.
     */
    public function woocerti_issue_certificate($course_id) {
        global $wpdb;
        $certificates_table = $wpdb->prefix.'certificates';
        $courses_table = $wpdb->prefix.'courses';

        // 'quantity available > 0' is used to ensure that there are certificates to issue.
        $certificate_record = $wpdb->get_row($wpdb->prepare("
            SELECT c.id_certificate, c.quantity_available
            FROM `$certificates_table` AS c
            INNER JOIN `$courses_table` AS co ON c.id_course = co.id_course
            WHERE c.id_course = %d AND c.quantity_available > 0
            ORDER BY c.date_created ASC
            LIMIT 1
        ", $course_id));

        if ($certificate_record) {
            // Subtract 1 from the available amount of the certificate.
            $new_quantity = $certificate_record->quantity_available - 1;

            $wpdb->update(
                $certificates_table,
                array('quantity_available' => $new_quantity, 'date_updated' => current_time('mysql')),
                array('id_certificate' => $certificate_record->id_certificate)
            );

            return true;
        }

        return false;
    }

    /**
     * Retrieves and sorts the nationalities from the appropriate locale JSON file.
     *
     * @return array An associative array of two-letter country codes => country names.
     */
    private function get_sorted_nationalities() {
        $current_locale = get_locale();
        $lang_code = substr($current_locale, 0, 2);
        $locales_path = WOOCERTI_PLUGIN_DIR."public/assets/js/locales/{$lang_code}.json";

        // Fallback to 'en' if the current locale file doesn't exist.
        if (!file_exists($locales_path)) {
            $locales_path = WOOCERTI_PLUGIN_DIR.'public/assets/js/locales/en.json';
        }

        $nationalities = [];
        if (file_exists($locales_path)) {
            $json_data = file_get_contents($locales_path);
            $locale_data = json_decode($json_data, true);
            if (isset($locale_data['translation']['nationalities'])) {
                $nationalities = $locale_data['translation']['nationalities'];
                asort($nationalities);
            }
        }

        return $nationalities;
    }

    /**
     * Handle AJAX request to add a product to the cart and prepare for checkout redirect.
     * This function is only for logged-in users (wp_ajax_).
     */
    public function woocerti_add_to_cart_checkout() {
        if (!isset($_POST['add-to-cart']) || !isset($_POST['quantity'])) {
            wp_send_json_error(array('message' => __('Missing product ID or quantity.', 'woocertificatespackage')));
            wp_die();
        }

        $product_id = absint($_POST['add-to-cart']);
        $quantity = absint($_POST['quantity']);
        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

        if (!isset(WC()->cart)) {
            wc_maybe_load_cart();
        }

        WC()->cart->empty_cart(true);

        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            $quantity,
            0,
            array(),
            array('woocerti_course_id' => $course_id)
        );

        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => __('Product added to cart successfully.', 'woocertificatespackage'),
                'cart_item_key' => $cart_item_key
            ));
        } else {
            wp_send_json_error(array('message' => __('Error adding product to cart.', 'woocertificatespackage')));
        }

        wp_die();
    }

    // // Example usage for 'woocerti_issue_certificate'
    // if ($this->woocerti_issue_certificate($course_id)) {
    //     // Logic for a satisfactory answer
    //     // 'Certificate issued successfully.'
    // } else {
    //     // Logic for a negative answer
    //     // 'There are no certificates available for this course.'
    // }

}