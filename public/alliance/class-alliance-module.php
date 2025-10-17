<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to manage the logic of the 'Alliance' module.
 * This module is designed to be portable and does not rely on
 * direct Woocerti constants, but rather on injected configuration.
*/
class Alliance_Module {

    /**
     * @var array Configuration (paths, URLs, version assets) injected.
     */
    protected $config;

    /**
     * * @param array $config - Environment settings (base_url, base_dir).
     */
    public function __construct($config = []) {
        $this->config = wp_parse_args($config, [
            'base_url' => '',
            'base_dir' => '',
            'version_assets' => '',
            'posts_per_page' => 20,
        ]);

        $this->setup_hooks();
    }

    /**
     * Defines the WordPress hooks for this module.
     */
    protected function setup_hooks() {
        // Registrar los Endpoints (se dispara en 'init')
        add_action('init', array($this, 'add_alliance_endpoints'));
        // Add custom query variables to handle pagination.
        add_filter('query_vars', array($this, 'add_plugin_query_vars'));
        if ($this->current_user_has_role('alliance')) {
            // Add the links to the My Account menu
            add_filter('woocommerce_account_menu_items', array($this, 'add_alliance_account_links'), 100);
            // Render content for the 'alliances' endpoint
            add_action('woocommerce_account_alliances_endpoint', array($this, 'render_alliances_content'));
            // Render content for the 'invoices' endpoint
            add_action('woocommerce_account_invoices_endpoint', array($this, 'render_invoices_content'));
            // Enqueue scripts and styles.
            add_action('wp_enqueue_scripts', array($this, 'enqueue_public_edusystem_assets'));
        }
    }

    /**
     * Enqueues Alliance module-specific assets.
     * Loads them only if the user is on the 'alliances' or 'invoices' endpoints.
    */
    public function enqueue_public_edusystem_assets() {
        if (is_account_page()) {
            $is_alliance_endpoint = get_query_var('alliances') !== false;
            $is_invoices_endpoint = get_query_var('invoices') !== false;
            if ($is_alliance_endpoint || $is_invoices_endpoint) {
                if (!empty($this->config['base_url'])) {
                    wp_enqueue_style('edusystem-alliance-style', $this->config['base_url'] . 'assets/css/style.css', array(), $this->config['version_assets']);
                    wp_enqueue_script('edusystem-alliance-script', $this->config['base_url'] . 'assets/js/main.js', array('jquery'), $this->config['version_assets'], true);
                }
            }
        }
    }

    /**
     * Helper to check if the current user has the 'alliance' role.
     * @param string $role The role to check.
     * @return bool
    */
    protected function current_user_has_role($role) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user = wp_get_current_user();
        return in_array($role, (array) $user->roles);
    }

    /**
     * Registers the 'alliances' and 'invoices' endpoints.
     */
    public function add_alliance_endpoints() {
        add_rewrite_endpoint('alliances', EP_PAGES);
        add_rewrite_endpoint('invoices', EP_PAGES);
    }

    /**
     * Adds the links to the WooCommerce account menu.
     * * @param array $items The existing menu items.
     * @return array The menu items with the new links.
    */
    public function add_alliance_account_links($menu_links) {
        // Define new links.
        $new_links = array();

        if ($this->current_user_has_role('alliance')) {
            $new_links['alliances'] = __('Alliances', 'edusystem');
            $new_links['invoices'] = __('Invoices', 'edusystem');
        }

        $dashboard = array_slice($menu_links, 0, 1, true);
        $rest = array_slice($menu_links, 1, null, true);

        return array_merge($dashboard, $new_links, $rest);
    }

    /**
     * Displays content for the 'alliances' endpoint.
     */
    public function render_alliances_content() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'institutes';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE '%s'", $table_name));

        $alliance_data = array(
            'alliances' => [],
            'table_exist' => true
        );

        if ($table_exists === $table_name) {
            $alliance_data = $this->get_alliance_list_data();
        } else {
            error_log(__("Edusystem: The 'institutes' table does not exist. Make sure the template plugin is active.", 'edusystem'));
            $alliance_data = array(
                'alliances' => [],
                'table_exist' => false
            );
        }

        $this->render_template('alliance-table', $alliance_data);
    }

    /**
     * Recupera la lista de alianzas asociadas al usuario actual.
     * * @return array Array de objetos de alianza.
     */
    protected function get_alliance_list_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'institutes';
        $alliance_id = get_user_meta(get_current_user_id(), 'alliance_id', true);
        $endpoint_url = wc_get_account_endpoint_url('alliances');

        // Sorting logic: Default 'name' ASC
        $current_orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'name';
        $current_order = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'ASC';

        $allowed_orderby = array('name', 'status', 'created_at');
        if (!in_array($current_orderby, $allowed_orderby)) {
            $current_orderby = 'name';
        }
        if (!in_array($current_order, array('ASC', 'DESC'))) {
            $current_order = 'ASC';
        }

        // Pagination settings
        $posts_per_page = $this->config['posts_per_page'];
        $current_page = max(1, get_query_var('pageds')); // Gets the current page, default is 1
        $offset = ($current_page - 1) * $posts_per_page; // Calculates the offset for the query
        $search = "";

        if (isset($_GET['alliance_search']) && !empty($_GET['alliance_search'])) {
            $search = $_GET['alliance_search'];
            // Query to get the total number of alliances (for pagination)
            $total_alliances = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM `$table_name`
                WHERE alliance_id = %d
                    AND (UPPER(name) LIKE UPPER('%{$search}%') || UPPER(email) LIKE UPPER('%{$search}%'))
            ", $alliance_id));
            $total_pages = ceil($total_alliances / $posts_per_page);

            $results = $wpdb->get_results($wpdb->prepare("
                SELECT
                    name, email, level_id, status, created_at
                FROM {$table_name}
                WHERE
                    alliance_id = %d
                    AND (UPPER(name) LIKE UPPER('%{$search}%') || UPPER(email) LIKE UPPER('%{$search}%'))
                ORDER BY {$current_orderby} {$current_order}
                LIMIT %d OFFSET %d
            ", $alliance_id, $posts_per_page, $offset));
        } else {
            $alliance_id = 80;
            // Query to get the total number of alliances (for pagination)
            $total_alliances = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM `$table_name`
                WHERE alliance_id = %d
            ", $alliance_id));
            $total_pages = ceil($total_alliances / $posts_per_page);

            $results = $wpdb->get_results($wpdb->prepare("
                SELECT
                    name, email, level_id, status, created_at
                FROM {$table_name}
                WHERE
                    alliance_id = %d
                ORDER BY {$current_orderby} {$current_order}
                LIMIT %d OFFSET %d
            ", $alliance_id, $posts_per_page, $offset));
        }

        $data = array(
            'alliances' => $results,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'endpoint_url' => $endpoint_url,
            'current_orderby' => $current_orderby,
            'current_order' => $current_order,
            'posts_per_page' => $posts_per_page,
            'total_alliances' => $total_alliances,
            'search_term' => $search,
            'table_exist' => true
        );

        return $data;
    }

    /**
     * Displays content for the 'invoices' endpoint.
     */
    public function render_invoices_content() {
        echo '<h2>' . esc_html__('Listado de Facturas', 'edusystem') . '</h2>';
        // Aquí iría el código para mostrar el listado de facturas.
        echo '<p>' . esc_html__('Contenido de la página de Facturas.', 'edusystem') . '</p>';
    }

    /**
     * Example function to load an internal template.
     * Uses $this->config['base_dir'] for the path.
    */
    public function render_alliance_form() {
        $template_path = $this->config['base_dir'] . 'templates/alliance-form.php';

        if (file_exists($template_path)) {
            // Lógica para obtener datos...
            $data = ['message' => 'Módulo Alliance funcionando.'];
            extract($data);
            include($template_path);
        }
    }

    /**
     * Loads a module view template.
     *
     * @param string $template_name - Template file name (e.g. 'alliance-table').
     * @param array $data - Data to be passed to the template (becomes local variables).
     */
    protected function render_template($template_name, $data = []) {
        $template_path = $this->config['base_dir'] . 'templates/' . $template_name . '.php';

        if (file_exists($template_path)) {
            $module = $this;
            extract($data);
            include $template_path;
        } else {
            echo '<p>' . sprintf(esc_html__('Error: Template not found %s.', 'edusystem'), esc_html($template_name)) . '</p>';
        }
    }

    public function get_name_level($level_id) {
        $level = match ($level_id) {
            '1' => __('Primary', 'edusystem'),
            '2' => __('High School', 'edusystem'),
            default => "",
        };

        return $level;
    }

    public function get_name_status_alliance($status_id) {
        $status = match ($status_id) {
            '0' => __('Pending', 'edusystem'),
            '1' => __('Approved', 'edusystem'),
            '2' => __('Declined', 'edusystem'),
            default => '',
        };

        return $status;
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
}