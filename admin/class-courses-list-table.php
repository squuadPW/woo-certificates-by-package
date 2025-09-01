<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class to manage the courses table in the administration panel.
 */
class Woocerti_Courses_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'course',
            'plural' => 'courses',
            'ajax' => false,
        ]);
    }

    /**
     * Defines the columns of the table.
     *
     * @return array
     */
    public function get_columns() {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'course_name' => __('Course Name', 'woocertificatespackage'),
            'tutor_instructor' => __('Tutor', 'woocertificatespackage'),
            'status' => __('Status', 'woocertificatespackage'),
            'price_per_student' => __('Price', 'woocertificatespackage'),
            'date_created' => __('Creation Date', 'woocertificatespackage'),
        ];
        return $columns;
    }

    /**
     * Defines the columns that can be sorted.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = [
            'course_name' => ['course_name', false],
            'status' => ['status', false],
            'date_created' => ['date_created', false],
        ];
        return $sortable_columns;
    }

    /**
     * Returns an array of views available on the table.
     *
     * @return array
     */
    public function get_views() {
        global $wpdb;
        $table_name = $wpdb->prefix.'courses';

        $total_courses = $wpdb->get_var("SELECT COUNT(id_course) FROM {$table_name} WHERE status != 'Draft' AND status != 'Declined'");
        $pending_courses = $wpdb->get_var("SELECT COUNT(id_course) FROM {$table_name} WHERE status = 'Pending'");

        $current_status = isset($_GET['status']) ? $_GET['status'] : 'all';

        $views = [
            'pending' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                admin_url('admin.php?page=woocerti-courses&status=Pending'),
                ($current_status === 'Pending' ? 'current' : ''),
                __('Awaiting Review', 'woocertificatespackage'),
                $pending_courses
            ),
            'all' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                admin_url('admin.php?page=woocerti-courses'),
                ($current_status === 'all' ? 'current' : ''),
                __('All', 'woocertificatespackage'),
                $total_courses
            ),
        ];

        return $views;
    }

    /**
     * Prepare the data for the table.
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix.'courses';

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];
        $per_page = WOOCERTI_POSTS_PER_PAGE;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Sorting parameters
        $orderby = (isset($_GET['orderby']) && in_array($_GET['orderby'], array_keys($this->get_sortable_columns()))) ? $_GET['orderby'] : 'date_created';
        $order = (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) ? $_GET['order'] : 'desc';

        // Status filtering logic
        $status_filter = '';
        if (isset($_GET['status']) && $_GET['status'] !== 'all') {
            $status = sanitize_text_field($_GET['status']);
            $status_filter = $wpdb->prepare(" AND status = %s", $status);
        } else {
            // The 'all' view excludes 'Draft' and 'Declined' courses
            $status_filter = " AND status != 'Draft' AND status != 'Declined'";
        }
        // Base query to obtain the items
        $sql = "SELECT * FROM {$table_name} WHERE 1=1{$status_filter}";
        $sql .= " ORDER BY {$orderby} {$order}";
        $sql .= " LIMIT %d OFFSET %d";
        $this->items = $wpdb->get_results(
            $wpdb->prepare($sql, $per_page, $offset)
        );

        // Gets the total number of items for pagination
        $total_items = $wpdb->get_var("SELECT COUNT(id_course) FROM {$table_name} WHERE 1=1{$status_filter}");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    /**
     * Displays the data for each column.
     *
     * @param object $item - The object of the current course.
     * @param string $column_name - The name of the column.
     * @return string
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'course_name':
            case 'tutor_instructor':
                return esc_html($item->$column_name);
            case 'date_created':
                return esc_attr(wp_date('d/m/Y', strtotime($item->$column_name))).' '.__('at', 'woocertificatespackage').' '.esc_attr(wp_date('H:i', strtotime($item->$column_name)));
            case 'price_per_student':
                return wc_price($item->price_per_student);
            case 'status':
                $status_label = '';
                switch ($item->status) {
                    case 'Draft':
                        $status_label = __('Draft', 'woocertificatespackage');
                        break;
                    case 'Pending':
                        $status_label = __('Awaiting Review', 'woocertificatespackage');
                        break;
                    case 'Approved':
                        $status_label = __('Approved', 'woocertificatespackage');
                        break;
                    case 'Rejected':
                        $status_label = __('Rejected', 'woocertificatespackage');
                        break;
                    case 'Completed':
                        $status_label = __('Completed', 'woocertificatespackage');
                        break;
                    case 'Declined':
                        $status_label = __('Deleted', 'woocertificatespackage');
                        break;
                    default:
                        $status_label = esc_html($item->status);
                        break;
                }
                return esc_html($status_label);
            default:
                return print_r($item, true);
        }
    }

    /**
     * Defines the `course_name` column with action links.
     *
     * @param object $item - The object of the current course.
     * @return string
     */
    public function column_course_name($item) {
        $delete_args = array(
            'action' => 'woocerti_delete_course_admin',
            'course_id' => $item->id_course,
        );

        // Create the URL with the security nonce
        $delete_url = wp_nonce_url(
            add_query_arg($delete_args, admin_url('admin-post.php')),
            'woocerti_delete_course_nonce',
            'woocerti_nonce'
        );

        $actions = array(
            'edit' => sprintf('<a href="%s">%s</a>', esc_url(add_query_arg('id', $item->id_course, admin_url('admin.php?page=woocerti-add-course&action=edit'))), __('Edit', 'woocertificatespackage')),
            'delete' => sprintf('<a href="%s" class="delete-course-link">%s</a>', esc_url($delete_url), __('Delete', 'woocertificatespackage')),
        );

        return sprintf('%1$s %2$s', esc_html($item->course_name), $this->row_actions($actions));
    }

    /**
     * Defines the checkbox column `cb`.
     *
     * @param object $item - The object of the current course.
     * @return string
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item->id_course
        );
    }
}