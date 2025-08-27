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
		add_action('admin_menu', array($this, 'add_admin_menu_page'));
		// Enqueue admin scripts and styles.
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
	}

	/**
	 * Adds an admin menu page for the plugin.
	 */
	public function add_admin_menu_page() {
		add_menu_page(
			'Woo Certificates',
			'Certificates Woo',
			'manage_options',
			'woocerti-certificates',
			array($this, 'render_admin_page'),
			'dashicons-tickets-alt',
			6
		);
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