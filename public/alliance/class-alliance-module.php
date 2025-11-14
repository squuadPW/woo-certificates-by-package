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
            add_action('wp_ajax_get_list_fee_alliance',  array($this, 'ajax_get_list_fee_alliance'));
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
                    if ($is_invoices_endpoint) {
                        wp_deregister_style('flatpicker-css');
                        wp_deregister_script('flatpickr-js');
                        wp_deregister_script('flatpickr-js-es');
                        wp_enqueue_style('edusystem-flatpickr-style', $this->config['base_url'] . 'assets/css/flatpickr.min.css', array(), '4.6.13');
                        wp_enqueue_script('edusystem-flatpickr-script', $this->config['base_url'] . 'assets/js/flatpickr.js', array('jquery'), '4.6.13', true);
                        wp_enqueue_script('edusystem-flatpickr-js-es', $this->config['base_url'] . 'assets/js/flatpickr-es.js', array('jquery'), '4.6.13', true);
                        wp_enqueue_script('edusystem-alliance-script', $this->config['base_url'] . 'assets/js/main.js', array('jquery', 'edusystem-flatpickr-script', 'edusystem-flatpickr-js-es'), $this->config['version_assets'], true);
                    } else {
                        wp_enqueue_script('edusystem-alliance-script', $this->config['base_url'] . 'assets/js/main.js', array('jquery'), $this->config['version_assets'], true);
                    }

                    $start_date = date('m/d/Y', strtotime('today'));

                    $data_to_pass = array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('fee_alliance'),
                        'alliance_id' => get_user_meta(get_current_user_id(), 'alliance_id', true),
                        'messages' => array(
                            'show_payments' => __('Show payments', 'edusystem'),
                            'show_orders' => __('Show orders', 'edusystem'),
                            'start_date' => $start_date,
                            'not_records' => __('There are not records', 'edusystem'),
                            'payment_id' => __('Payment ID', 'edusystem'),
                            'customer' => __('Customer', 'edusystem'),
                            'fee' => __('Fee', 'edusystem'),
                            'created' => __('Created', 'edusystem'),
                            'status' => __('Status', 'edusystem'),
                            'month' => __('Month', 'edusystem'),
                            'amount' => __('Amount', 'edusystem'),
                            'total_orders' => __('Total orders', 'edusystem'),
                            'update_data' => __('Update data', 'edusystem')
                        ),
                        'wc_format_params' => array(
                            'currency_format_num_decimals' => absint(get_option('woocommerce_price_num_decimals', 2)),
                            'currency_format_symbol' => get_woocommerce_currency_symbol(),
                            'currency_format_decimal_sep' => wc_get_price_decimal_separator(),
                            'currency_format_thousand_sep' => wc_get_price_thousand_separator(),
                            'currency_format' => get_woocommerce_price_format(),
                        )
                    );
                    wp_localize_script('edusystem-alliance-script', 'edusystem_alliance_data', $data_to_pass);
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
            $search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['alliance_search'])) . '%';
            // Query to get the total number of alliances (for pagination)
            $total_alliances = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM `$table_name`
                WHERE alliance_id = %d
                    AND (UPPER(name) LIKE UPPER(%s) || UPPER(email) LIKE UPPER(%s))
            ", $alliance_id, $search, $search));
            $total_alliances = (int) $total_alliances;
            $total_pages = ceil($total_alliances / $posts_per_page);

            $results = $wpdb->get_results($wpdb->prepare("
                SELECT
                    name, email, level_id, status, created_at
                FROM {$table_name}
                WHERE
                    alliance_id = %d
                    AND (UPPER(name) LIKE UPPER(%s) || UPPER(email) LIKE UPPER(%s))
                ORDER BY {$current_orderby} {$current_order}
                LIMIT %d OFFSET %d
            ", $alliance_id, $search, $search, $posts_per_page, $offset));
        } else {
            // Query to get the total number of alliances (for pagination)
            $total_alliances = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM `$table_name`
                WHERE alliance_id = %d
            ", $alliance_id));
            $total_alliances = (int) $total_alliances;
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
            'search_term' => $_GET['alliance_search'],
            'table_exist' => true
        );

        return $data;
    }

    /**
     * Displays content for the 'invoices' endpoint.
     */
    public function render_invoices_content() {
        $data = $this->get_list_fee_alliance_data([
            'filter' => 'this-month',
            'custom' => date('m/d/Y')
        ]);

        $start_date = date('m/d/Y', strtotime('today'));

        $cards = [
            [
                'title' => __('Balance', 'edusystem'),
                'value' => $data['current_invoice']['total'],
                'icon' => 'dashicons-chart-pie',
                'id' => 'card-alliance-balance',
                'visible' => true
            ], [
                'title' => __('Total Paid', 'edusystem'),
                'value' => $data['transactions']['total_paid'],
                'icon' => 'dashicons-money-alt',
                'id' => 'card-alliance-paid',
                'visible' => true
            ], [
                'title' => __('Pending Payment', 'edusystem'),
                'value' => $data['transactions']['total_pending'],
                'icon' => 'dashicons-info-outline',
                'id' => 'card-alliance-pending',
                'visible' => true
            ], [
                'title' => __('Orders', 'edusystem'),
                'value' => $data['current_invoice']['total'],
                'icon' => 'dashicons-editor-ul',
                'id' => 'card-alliance-orders',
                'visible' => true
            ], [
                'title' => __('Transactions', 'edusystem'),
                'value' => $data['transactions']['total'],
                'icon' => 'dashicons-list-view',
                'id' => 'card-alliance-transactions',
                'visible' => false
            ],
        ];

        $optionsFilter = [
            [
                'value' => 'today',
                'label' => __('Today', 'edusystem'),
                'selected' => false
            ], [
                'value' => 'yesterday',
                'label' => __('yesterday', 'edusystem'),
                'selected' => false
            ], [
                'value' => 'this-week',
                'label' => __('This week', 'edusystem'),
                'selected' => false
            ], [
                'value' => 'last-week',
                'label' => __('Last week', 'edusystem'),
                'selected' => false
            ], [
                'value' => 'this-month',
                'label' => __('This month', 'edusystem'),
                'selected' => true
            ], [
                'value' => 'last-month',
                'label' => __('Last month', 'edusystem'),
                'selected' => false
            ], [
                'value' => 'custom',
                'label' => __('Custom', 'edusystem'),
                'selected' => false
            ]
        ];

        $invoice_data = array(
            'cards' => $cards,
            'current_invoice' => $data['current_invoice'],
            'transactions' => $data['transactions'],
            'optionsFilter' => $optionsFilter,
            'start_date' => $start_date,
        );

        $this->render_template('invoice-table', $invoice_data);
    }

    public function get_list_fee_alliance_data(array $args = []): array {
        $default_filter = 'this-month';
        $default_custom = date('m/d/Y');

        $filter = isset($args['filter']) ? sanitize_text_field($args['filter']) : $default_filter;
        $custom = isset($args['custom']) ? sanitize_text_field($args['custom']) : $default_custom;

        $alliance_id = isset($args['alliance_id']) ? $args['alliance_id'] : get_user_meta(get_current_user_id(), 'alliance_id', true);

        $transactions = [];

        $dates = $this->get_dates_search($filter, $custom);
        $current_invoice = $this->get_invoices_alliances($dates[0], $dates[1], $alliance_id);

        $pending  = $this->get_transactions_alliances($dates[0], $dates[1], $alliance_id, 0);
        $complete = $this->get_transactions_alliances($dates[0], $dates[1], $alliance_id, 1);

        $total_pending_raw  = (float) ($pending['total'] ?? 0);
        $total_complete_raw = (float) ($complete['total'] ?? 0);
        $total_combined_raw = $total_pending_raw + $total_complete_raw;

        $transactions['total_pending'] = wc_price($total_pending_raw);
        $transactions['total_paid'] = wc_price($total_complete_raw);
        $transactions['total'] = wc_price($total_combined_raw);
        $transactions['orders'] = array_merge($pending['orders'] ?? [], $complete['orders'] ?? []);

        $current_invoice['total'] = wc_price((float) ($current_invoice['total'] ?? 0));

        return [ 'status' => 'success', 'current_invoice' => $current_invoice, 'transactions' => $transactions, 'dates' => $dates];
    }

    public function ajax_get_list_fee_alliance() {
        check_ajax_referer('fee_alliance', 'nonce');

        if (!$this->current_user_has_role('alliance')) {
            wp_send_json_error(['message' => __('No tienes permisos para acceder a esta información.', 'edusystem')], 403);
        }

        // Recoge parámetros de $_POST (o $_REQUEST) y pásalos al método de datos
        $args = [
            'filter' => isset($_POST['filter']) ? wp_unslash($_POST['filter']) : null,
            'custom' => isset($_POST['custom']) ? wp_unslash($_POST['custom']) : null,
            'alliance_id' => isset($_POST['alliance_id']) ? (int) $_POST['alliance_id'] : null
        ];

        $data = $this->get_list_fee_alliance_data($args);

        wp_send_json_success($data);
    }

    /**
     * Obtains data on alliance payment rates/fees over a date range.
     *
     * @param string $start_date_str - Start date (any format, e.g. '10/22/2025').
     * @param string $end_date_str - End date (any format).
     * @param int|string $alliance_id_arg - The alliance ID to be consulted (must be sanitized/validated before passing).
     * @return array ['total' => float, 'orders' => array]
     */
    protected function get_invoices_alliances($start_date_str, $end_date_str, $alliance_id_arg) {
        global $wpdb;
        $table_student_payments = $wpdb->prefix . 'student_payments';
        $alliance_id = absint($alliance_id_arg);

        if (empty($alliance_id)) {
            return ['total' => 0.00, 'orders' => []];
        }

        $date_clause = '';
        $prepare_args = [$alliance_id];

        if (!empty($start_date_str) && !empty($end_date_str)) {
            // Convert and sanitize input dates to MySQL format (YYYY-MM-DD)
            $start_date = date('Y-m-d', strtotime(sanitize_text_field($start_date_str)));
            $end_date = date('Y-m-d', strtotime(sanitize_text_field($end_date_str)));

            $date_clause = " AND date_payment BETWEEN %s AND %s ";
            $prepare_args[] = $start_date;
            $prepare_args[] = $end_date;
        }

        $sql = "SELECT *
            FROM {$table_student_payments}
            WHERE status_id = 1
            AND JSON_CONTAINS(`alliances`, JSON_OBJECT('id', %d))
            {$date_clause}
            ORDER BY date_payment DESC";

        $payments = $wpdb->get_results($wpdb->prepare($sql, $prepare_args));

        $data_fees = [];
        $total = 0.00;

        foreach ($payments as $payment) {
            // Initialize the fee_amount
            $fee_amount = 0.00;

            foreach (json_decode($payment->alliances, true) as $alliance) {
                if ((string) $alliance['id'] === (string) $alliance_id) {
                    $fee_amount = (float) $alliance['calculated_fee_amount'];
                    break;
                }
            }
            // Cargar el objeto WC_Order
            $order = wc_get_order( $payment->order_id );

            if ($order) {
                array_push($data_fees, [
                    'order_id' => $order->get_id(),
                    'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'fee' => $fee_amount,
                    'created_at' => $payment->date_payment
                ]);
            }

            $total += $fee_amount;
        }

        return ['total' => $total, 'orders' => $data_fees];
    }

    /**
     * Calculates the date range (start and end) based on a predefined filter or a custom range.
     *
     * @param string $filter_key - Filter key ('today', 'this-month', 'custom', etc.).
     * @param string $custom_date_range_str - Custom date range (e.g. '10/22/2025 to 11/22/2025').
     * @return array Array with [start_date (Y-m-d), end_date (Y-m-d)]. Returns [false, false] on error.
     */
    protected function get_dates_search(string $filter_key, string $custom_date_range_str = '') {
        $filter = sanitize_text_field($filter_key);
        $custom = sanitize_text_field($custom_date_range_str);

        $start = '';
        $end = '';

        // Output date format for the database (Year-Month-Day)
        $output_date_format = 'm/d/Y';
        $start_date = date($output_date_format, strtotime('today'));
        $dt_start = DateTime::createFromFormat($output_date_format, $start_date);

        try {
            [$start_time, $end_time] = match ($filter) {
                'today' => [$dt_start->getTimestamp(), $dt_start->getTimestamp()],
                'yesterday' => [strtotime('-1 days'), strtotime('-1 days')],
                'tomorrow' => [strtotime('+1 days'), strtotime('+1 days')],
                // Weeks: Monday to Sunday.
                'this-week' => [strtotime('this week monday'), strtotime('this week sunday')],
                'last-week' => [strtotime('last week monday'), strtotime('last week sunday')],
                // Months: First day to Last day.
                'this-month' => [strtotime('first day of this month'), strtotime('last day of this month')],
                'last-month' => [strtotime('first day of last month'), strtotime('last day of last month')],

                'custom' => $this->handle_custom_date_range($custom),
                // Default value if $filter does not match any key
                default => [false, false],
            };

            // Final conversion to Y-m-d format
            if ($start_time !== false && $end_time !== false) {
                $start = wp_date($output_date_format, $start_time);
                $end = wp_date($output_date_format, $end_time);
            } else {
                return [false, false];
            }

        } catch (Throwable $e) {
            error_log(__("edusystem: Error calculating date range:", 'edusystem') . $e->getMessage());
            return [false, false];
        }

        return [$start, $end];
    }

    /**
     * Helper to handle custom date range logic (MM/DD/YYYY to MM/DD/YYYY).
     *
     * @param string $custom - Custom date range.
     * @param string $output_date_format - Departure date format.
     * @return array [timestamp_start, timestamp_end] or [false, false] in case of error.
     */
    protected function handle_custom_date_range(string $custom): array {
        $custom_input_format = 'm/d/Y';
        $date = str_replace([' to ', ' a '], ',', $custom);
        $date_array = explode(',', $date);

        $start_str = trim($date_array[0]);
        $end_str = isset($date_array[1]) ? trim($date_array[1]) : $start_str;

        // Parse the start date
        $dt_start = DateTime::createFromFormat($custom_input_format, $start_str);

        // Parse the end date
        $dt_end = DateTime::createFromFormat($custom_input_format, $end_str);

        if ($dt_start && $dt_end) {
            // We return the timestamps. wp_date() will format them in the main method.
            return [$dt_start->getTimestamp(), $dt_end->getTimestamp()];
        }

        return [false, false];
    }

    /**
     * Obtiene las transacciones de pago para la alianza dentro de un rango de fechas y un estado específico.
     * * @param string $start_date_str  Fecha de inicio (esperada en formato Y-m-d).
     * @param string $end_date_str    Fecha de fin (esperada en formato Y-m-d).
     * @param int|string $alliance_id_arg ID de la alianza a consultar.
     * @param int $status_id_arg      ID del estado de la transacción (0 por defecto).
     * @return array ['total' => float, 'transactions' => array]
     */
    protected function get_transactions_alliances($start_date_str, $end_date_str, $alliance_id_arg, $status_id_arg = 0) {
        global $wpdb;
        $table_alliances_payments = $wpdb->prefix . 'alliances_payments';

        $alliance_id = absint($alliance_id_arg);
        $status = absint($status_id_arg);

        if (empty($alliance_id)) {
            return ['total' => 0.00, 'orders' => []];
        }

        $start_datetime = date('Y-m-d 00:00:00', strtotime(sanitize_text_field($start_date_str)));
        $end_datetime = date('Y-m-d 23:59:59', strtotime(sanitize_text_field($end_date_str)));

        $sql = "SELECT * FROM {$table_alliances_payments}
            WHERE alliance_id = %d
            AND status_id = %d
            AND created_at BETWEEN %s AND %s
            ORDER BY created_at DESC";

        $prepare_args = [
            $alliance_id,
            $status,
            $start_datetime,
            $end_datetime
        ];

        $transactions = $wpdb->get_results($wpdb->prepare($sql, $prepare_args));

        $data_fees = [];
        $total = 0.00;

        foreach ($transactions as $transaction) {
            $amount = (float) $transaction->amount;

            array_push($data_fees, [
                'status' => $this->get_name_payment_institute_status(absint($transaction->status_id)),
                'month' => sanitize_text_field($transaction->month),
                'amount' => $amount,
                'total_orders' => absint($transaction->total_orders),
                'created_at' => $transaction->created_at
            ]);

            $total += $amount;
        }

        return ['total' => $total, 'orders' => $data_fees];
    }

    /**
     * Traduce el ID de estado de pago de instituto a una cadena legible y traducible.
     * * @param int|string $status_id ID del estado (0 o cualquier otro).
     * @return string Nombre del estado traducido.
     */
    public function get_name_payment_institute_status($status_id) {
        $status_id = (string) $status_id;

        $status = match ($status_id) {
            '0' => esc_html__('Pending', 'edusystem'),
            default => esc_html__('Paid', 'edusystem'),
        };

        return $status;
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