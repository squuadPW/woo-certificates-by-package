<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class to handle the admin-facing side of the plugin.
 */
class Woocerti_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add admin menu page.
		add_action('admin_menu', array($this, 'add_plugin_menu_pages'));
		// Enqueue admin scripts and styles.
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        // Handle form submission for saving/updating courses from the admin panel.
        add_action('admin_post_woocerti_save_course_admin', array($this, 'handle_admin_course_save'));
        // Handle form delete courses from the admin panel.
        add_action('admin_post_woocerti_delete_course_admin', array($this, 'handle_admin_course_delete'));
	}

    /**
	 * Adds main and sub-menu pages for the plugin.
	 */
	public function add_plugin_menu_pages() {
		// Create the main menu 'Courses'.
		add_menu_page(
			__('Courses', 'woocertificatespackage'),
			__('Courses', 'woocertificatespackage'),
			'manage_options',
            'woocerti-courses',
			array($this, 'render_courses_list_page'),
			'dashicons-clipboard',
			6
		);

		// Create the 'List' submenu.
		add_submenu_page(
			'woocerti-courses',
			__('List Courses', 'woocertificatespackage'),
			__('List Courses', 'woocertificatespackage'),
			'manage_options',
			'woocerti-courses',
			array($this, 'render_courses_list_page')
		);

		// Crea el submenú 'Agregar'.
		add_submenu_page(
			'woocerti-courses',
			__('Add course', 'woocertificatespackage'),
			__('Add course', 'woocertificatespackage'),
			'manage_options',
			'woocerti-add-course',
			array($this, 'render_add_course_page')
		);

		// // Create the main menu 'Certificates'.
		// add_menu_page(
		// 	'Woo Certificates',
		// 	'Certificates Woo',
		// 	'manage_options',
		// 	'woocerti-certificates',
		// 	array($this, 'render_admin_page'),
		// 	'dashicons-tickets-alt',
		// 	6
		// );
	}

    /**
     * Renders the content of the 'Courses List' admin page.
     */
    public function render_courses_list_page() {
        // Instantiate the custom WP_List_Table class
        $courses_table = new Woocerti_Courses_List_Table();
        // Prepare the data (this runs the SQL query, pagination, etc.)
        $courses_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html(__('Courses List', 'woocertificatespackage')); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=woocerti-add-course')); ?>" class="page-title-action">
                <?php echo esc_html(__('Add course', 'woocertificatespackage')); ?>
            </a>
            <hr class="wp-header-end">
            <?php
                // Displays confirmation messages if they exist in the URL
                if (isset($_GET['message'])) {
                    if ($_GET['message'] === 'deleted') {
                        echo '<div class="notice notice-success is-dismissible"><p>'.esc_html(__('Course deleted successfully.', 'woocertificatespackage')).'</p></div>';
                    } elseif ($_GET['message'] === 'error') {
                        echo '<div class="notice notice-error is-dismissible"><p>'.esc_html(__('Error deleting course.', 'woocertificatespackage')).'</p></div>';
                    }
                }
            ?>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <?php
                // Display the views/tabs
                $courses_table->views();
                // Display the table itself
                $courses_table->display();
                ?>
            </form>
        </div>
        <?php
    }

	/**
	 * Renders the content of the 'Add course' admin page.
	 */
	public function render_add_course_page() {
        global $wpdb;
        $table_name = $wpdb->prefix.'courses';

        // Default values
        $course_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $form_title = __('Add course', 'woocertificatespackage');

        // Form variables with default values
        $id_user = '';
        $course_name = '';
        $tutor_instructor = '';
        $is_tutor = 0;
        $academic_hours = '';
        $location = '';
        $course_date = '';
        $academic_program = '';
        $price_per_student = '';
        $certification_fee_type = 'Fixed';
        $certification_fee_value = '';
        $status = 'Pending';
        $code = '';
        $date_created = '';
        $date_updated = '';
        $institutes = get_users(array('role' => 'institutes', 'orderby' => 'display_name', 'order' => 'ASC'));

        // If you are editing a course, upload the data
        if ($course_id > 0) {
            $course_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id_course = %d", $course_id));

            if ($course_data) {
                $form_title = __('Edit Course', 'woocertificatespackage').': '.esc_html($course_data->course_name);
                $id_user = $course_data->id_user;
                $course_name = $course_data->course_name;
                $tutor_instructor = $course_data->tutor_instructor;
                $is_tutor = $course_data->is_tutor;
                $academic_hours = $course_data->academic_hours;
                $location = $course_data->location;
                $course_date = $course_data->course_date;
                $academic_program = $course_data->academic_program;
                $price_per_student = $course_data->price_per_student;
                $certification_fee_type = $course_data->certification_fee_type;
                $certification_fee_value = $course_data->certification_fee_value;
                $status = $course_data->status;
                $code = $course_data->code;
                $date_created = $course_data->date_created;
                $date_updated = $course_data->date_updated;
            } else {
                // Redirect if the ID is invalid
                wp_die(__('Course not found.', 'woocertificatespackage'));
            }
        }

        // Include the form template file, passing all variables
        include WOOCERTI_PLUGIN_DIR.'admin/templates/course-form.php';
    }

    /**
     * Handles the form submission for saving/updating a course from the admin panel.
     */
    public function handle_admin_course_save() {
        global $wpdb;
        // Check nonce for security
        if (!isset($_POST['woocerti_nonce']) || !wp_verify_nonce($_POST['woocerti_nonce'], 'woocerti_save_course_admin_data')) {
            wp_die(__('Security check failed.', 'woocertificatespackage'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocertificatespackage'));
        }

        $table_name = $wpdb->prefix.'courses';
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $user_id_assigned = isset($_POST['id_user']) ? intval($_POST['id_user']) : 0;

        // Sanitize and validate form data
        $course_data = array(
            'course_name' => sanitize_text_field($_POST['course_name']),
            'tutor_instructor' => sanitize_text_field($_POST['tutor_instructor']),
            'academic_hours' => floatval($_POST['academic_hours']),
            'location' => sanitize_text_field($_POST['location']),
            'course_date' => sanitize_text_field($_POST['course_date']),
            'academic_program' => sanitize_textarea_field($_POST['academic_program']),
            'price_per_student' => floatval($_POST['price_per_student']),
            'certification_fee_type' => sanitize_text_field($_POST['certification_fee_type']),
            'certification_fee_value' => floatval($_POST['certification_fee_value']),
            'status' => sanitize_text_field($_POST['status']),
            'date_updated' => current_time('mysql'),
        );
        if ($course_data['status'] === 'Pending') {
            $status = 'Pending';
        } else {
            $status = 'all';
        }

        if ($course_id > 0) {
            // Update an existing course
            $wpdb->update($table_name, $course_data, array('id_course' => $course_id, 'id_user' => $user_id_assigned));
            $redirect_url = admin_url('admin.php?page=woocerti-courses&status='.$status.'&message=updated');
        } else {
            // Generate a unique code and insert a new course
            $user_info = get_userdata($user_id_assigned);
            $user_initials = strtoupper(substr($user_info->user_firstname, 0, 1).substr($user_info->user_lastname, 0, 1));
            $user_initials .= $user_id_assigned;
            $course_name_parts = explode(' ', $course_data['course_name']);
            $course_acronym = '';
            foreach ($course_name_parts as $part) {
                $course_acronym .= strtoupper(substr($part, 0, 1));
            }
            $unique_id = strtoupper(wp_generate_password(6, false, false));
            $course_code = 'C-'.$course_acronym.'-'.$user_initials.'-'.$unique_id;

            $course_data['code'] = $course_code;
            $course_data['id_user'] = $user_id_assigned;
            $course_data['date_created'] = current_time('mysql');

            $wpdb->insert($table_name, $course_data);
            $redirect_url = admin_url('admin.php?page=woocerti-courses&status='.$status.'&message=created');
        }

        // Redirects to the course list page
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Manages course deletion from the admin panel.
     */
    public function handle_admin_course_delete() {
        global $wpdb;
        $table_name = $wpdb->prefix.'courses';

        // Security check: nonce and permissions.
        if (!isset($_GET['woocerti_nonce']) || !wp_verify_nonce($_GET['woocerti_nonce'], 'woocerti_delete_course_nonce') || !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to do this.', 'woocertificatespackage'));
        }

        // Get the course ID and validate that it is a number.
        $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
        if ($course_id === 0) {
            wp_die(__('Invalid course ID.', 'woocertificatespackage'));
        }

        // The course status is updated to 'Declined' to perform a partial deletion.
        $updated = $wpdb->update($table_name, array('status' => 'Declined'), array('id_course' => $course_id));

        // Redirect with a success or error message.
        if ($updated !== false) {
            $message = urlencode(__('Course deleted successfully.', 'woocertificatespackage'));
            $redirect_url = add_query_arg(array('message' => 'deleted'), admin_url('admin.php?page=woocerti-courses'));
        } else {
            $message = urlencode(__('Error deleting course.', 'woocertificatespackage'));
            $redirect_url = add_query_arg(array('message' => 'error'), admin_url('admin.php?page=woocerti-courses'));
        }

        wp_redirect(esc_url_raw($redirect_url));
        exit;
    }

	/**
	 * Renders the content of the admin page.
	 */
	public function render_admin_page() {
		?>
		<div class="wrap">
			<h1>Certificate Panel</h1>
			<p>This is where the administrator options and tools will go.</p>
		</div>
		<?php
	}

	/**
	 * Enqueues admin CSS and JavaScript files.
	 */
	public function enqueue_admin_assets() {
		// Enqueue CSS file.
		wp_enqueue_style('woocerti-admin-style', WOOCERTI_PLUGIN_URL.'admin/assets/css/style.css', array(), WOOCERTI_VERSION_ASSETS);
		// Enqueue JS file.
		wp_enqueue_script('woocerti-admin-script', WOOCERTI_PLUGIN_URL.'admin/assets/js/main.js', array('jquery'), WOOCERTI_VERSION_ASSETS, true);
        // Pass messages from PHP to JavaScript
        $data_to_pass = array(
            'messages' => array(
                'assigned_user_required' => __('You must assign a user to continue.', 'woocertificatespackage'),
                'course_name_required' => __('The course name is required.', 'woocertificatespackage'),
                'course_name_maxlength' => __('The course name cannot exceed 300 characters.', 'woocertificatespackage'),
                'academic_hours_invalid' => __('Academic hours must be a number greater than zero.', 'woocertificatespackage'),
                'course_date_past' => __('The date cannot be earlier than the current one.', 'woocertificatespackage'),
                'certification_fee_type_invalid' => __('Please select a valid fare type.', 'woocertificatespackage'),
                'status_invalid' => __('Please select a valid status.', 'woocertificatespackage'),
                'price_per_student_invalid' => __('The price must be a number greater than zero.', 'woocertificatespackage'),
                'certification_fee_value_invalid' => __('The rate value must be a number greater than zero.', 'woocertificatespackage'),
            ),
            'deleteConfirmText' => __('Are you sure you want to delete this course? This action cannot be undone.', 'woocertificatespackage'),
        );
        wp_localize_script('woocerti-admin-script', 'woocerti_data', $data_to_pass);
	}
}

/**
 * Handles plugin activation logic.
 */
class Woocerti_Activator {
    public static function activate() {
        // It runs only once when the plugin is activated.
        flush_rewrite_rules();
        self::create_tables();
        self::create_default_category();
    }
    /**
     * Creates the necessary database tables for the plugin.
     */
    public static function create_tables() {
        global $wpdb;
        // Set the character set and collation
        $charset_collate = $wpdb->get_charset_collate();

        // Define the SQL query to create the table Courses
        $sql_courses = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}courses` (
            `id_course` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_user` BIGINT(20) UNSIGNED NOT NULL,
            `code` VARCHAR(255) NOT NULL,
            `course_name` VARCHAR(300) NOT NULL,
            `academic_hours` DECIMAL(10, 2) NOT NULL,
            `tutor_instructor` VARCHAR(255) NULL,
            `location` VARCHAR(255) NULL,
            `course_date` DATE NULL,
            `description` TEXT NULL,
            `academic_program` TEXT NULL,
            `price_per_student` DECIMAL(10, 2) NOT NULL,
            `certification_fee_type` VARCHAR(50) NOT NULL,
            `certification_fee_value` DECIMAL(10, 2) NOT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
            `date_created` DATETIME NOT NULL,
            `date_updated` DATETIME NOT NULL,
            PRIMARY KEY (`id_course`),
            KEY `idx_id_user` (`id_user`)
        ) $charset_collate;";

        // Define the SQL query to create the table Certificates
        $sql_certificates = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}certificates` (
            `id_certificate` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_wc_order` BIGINT(20) UNSIGNED NOT NULL,
            `id_course` BIGINT(20) UNSIGNED NOT NULL,
            `quantity_purchased` INT(11) UNSIGNED NOT NULL,
            `quantity_available` INT(11) UNSIGNED NOT NULL,
            `date_created` DATETIME NOT NULL,
            `date_updated` DATETIME NOT NULL,
            PRIMARY KEY (`id_certificate`),
            KEY `idx_id_wc_order` (`id_wc_order`),
            KEY `idx_id_course` (`id_course`)
        ) $charset_collate;";

        // Include the upgrade.php file to use the dbDelta() function
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');

        // Use dbDelta to create the table. It's safe and handles updates.
        dbDelta($sql_courses);
        dbDelta($sql_certificates);
    }
    /**
     * Creates the default "Certificates" category for WooCommerce.
     */
    public static function create_default_category() {
        // Check if WooCommerce is active.
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Define the details of the category.
        $cat_name = 'Certificate';
        $cat_slug = 'certificate';
        $taxonomy = 'product_cat';

        // Check if the category already exists.
        if (!term_exists($cat_name, $taxonomy)) {
            // Create the category if it doesn't exist.
            wp_insert_term(
                $cat_name,
                $taxonomy,
                array(
                    'slug' => $cat_slug,
                    'description' => __('Category for all products that are digital certificates.', 'woocertificatespackage'),
                )
            );
        }
    }
}

/**
 * Handles plugin deactivation logic.
 */
class Woocerti_Deactivator {
	public static function deactivate() {
		// Deactivation code here (e.g., cleaning up data).
	}
}