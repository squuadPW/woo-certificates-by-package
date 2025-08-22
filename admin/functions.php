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
		// Activation code here (e.g., creating database tables).
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