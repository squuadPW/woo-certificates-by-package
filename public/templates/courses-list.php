<div class="woocerti-courses-header">
    <h2><?php echo __('My Courses', 'woocertificatespackage'); ?></h2>
    <p class="p-button-create">
        <a href="<?php echo esc_url($endpoint_url.'?action=create'); ?>" class="woocommerce-button button button-primary"><?php echo __('Create Course', 'woocertificatespackage'); ?></a>
    </p>
</div>
<?php
    if (!function_exists('woocerti_get_sort_link')) {
        function woocerti_get_sort_link($endpoint_url, $column, $current_orderby, $current_order) {
            if ($current_orderby === $column) {
                $new_order = $current_order === 'ASC' ? 'DESC' : 'ASC';
                $icon_class = $current_order === 'ASC' ? 'woocerti-sort-up' : 'woocerti-sort-down';
                $icon_html = '<i class="sort-icon '.$icon_class.'"></i>';
                $class = 'sorted ' . strtolower($current_order);
            } else {
                $new_order = 'ASC';
                $icon_html = '<i class="sort-icon woocerti-sort-both"></i>';
                $class = 'sortable';
            }

            $query_args = array_diff_key($_GET, array_flip(['orderby', 'order']));
            $query_args['orderby'] = $column;
            $query_args['order'] = $new_order;

            if (isset($query_args['pageds'])) {
                unset($query_args['pageds']);
            }

            $url = esc_url(add_query_arg($query_args, $endpoint_url));

            return array('url' => $url, 'icon' => $icon_html, 'class' => $class);
        }
    }

    $course_name_sort = woocerti_get_sort_link($endpoint_url, 'course_name', $current_orderby, $current_order);
    $status_sort = woocerti_get_sort_link($endpoint_url, 'status', $current_orderby, $current_order);
?>
<?php if (empty($courses) && $current_page == 1) : ?>
    <p><?php echo __('You have not created any courses yet.', 'woocertificatespackage'); ?></p>
<?php elseif (empty($courses)) : ?>
    <p><?php echo __('No courses found for this page.', 'woocertificatespackage'); ?></p>
<?php else : ?>
    <table class="woocommerce-MyAccount-courses-table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--course-name <?php echo esc_attr($course_name_sort['class']); ?>">
                    <a href="<?php echo $course_name_sort['url']; ?>">
                        <span class="nobr"><?php echo __('Course Name', 'woocertificatespackage'); ?></span>
                        <?php echo $course_name_sort['icon']; ?>
                    </a>
                </th>
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--academic-hours">
                    <span class="nobr"><?php echo __('Duration', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--price">
                    <span class="nobr"><?php echo __('Price per student', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--price-certificate">
                    <span class="nobr"><?php echo __('Certificate price', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--status <?php echo esc_attr($status_sort['class']); ?>">
                    <a href="<?php echo $status_sort['url']; ?>">
                        <span class="nobr"><?php echo __('Status', 'woocertificatespackage'); ?></span>
                        <?php echo $status_sort['icon']; ?>
                    </a>
                </th>
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--actions">
                    <span class="nobr"><?php echo __('Actions', 'woocertificatespackage'); ?></span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $course) : ?>
                <tr class="woocommerce-MyAccount-courses-table__row">
                    <td class="woocommerce-MyAccount-courses-table__cell woocommerce-MyAccount-courses-table__cell--course-name" data-title="<?php echo esc_attr(__('Course Name', 'woocertificatespackage')); ?>">
                        <?php if ($course->status === 'Draft') : ?>
                            <a href="<?php echo esc_url($endpoint_url.'?action=edit&course_id='.$course->id_course); ?>">
                                <?php echo esc_html($course->course_name); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($course->course_name); ?>
                        <?php endif; ?>
                    </td>
                    <td class="woocommerce-MyAccount-courses-table__cell woocommerce-MyAccount-courses-table__cell--academic-hours" data-title="<?php echo esc_attr(__('Duration', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($course->academic_hours); ?> <?php echo __('hours', 'woocertificatespackage'); ?>
                    </td>
                    <td class="woocommerce-MyAccount-courses-table__cell woocommerce-MyAccount-courses-table__cell--price" data-title="<?php echo esc_attr(__('Price per student', 'woocertificatespackage')); ?>">
                        <?php echo wc_price($course->price_per_student); ?>
                    </td>
                    <td class="woocommerce-MyAccount-courses-table__cell woocommerce-MyAccount-courses-table__cell--price-certificate" data-title="<?php echo esc_attr(__('Certificate price', 'woocertificatespackage')); ?>">
                        <?php
                            if ($course->certification_fee_type === 'Percentage') {
                                $price_certificate = ($course->price_per_student * $course->certification_fee_value) / 100;
                            } else {
                                $price_certificate = $course->certification_fee_value;
                            }
                            echo wc_price($price_certificate); ?>
                    </td>
                    <td class="woocommerce-MyAccount-courses-table__cell woocommerce-MyAccount-courses-table__cell--status" data-title="<?php echo esc_attr(__('Status', 'woocertificatespackage')); ?>">
                        <span class="woocommerce-MyAccount-courses-table__status woocerti-status-<?php echo esc_attr(strtolower($course->status)); ?>">
                            <?php
                                $status_class = strtolower($course->status);
                                switch ($course->status) {
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
                                    default:
                                        $status_label = esc_html($course->status);
                                        break;
                                }
                            ?>
                            <?php echo esc_html($status_label); ?>
                        </span>
                    </td>
                    <td class="woocommerce-MyAccount-courses-table__cell woocommerce-MyAccount-courses-table__cell--actions" data-title="<?php echo esc_attr(__('Actions', 'woocertificatespackage')); ?>">
                        <?php if ($course->status === 'Draft') : ?>
                            <a href="<?php echo esc_url($endpoint_url.'?action=edit&course_id='.$course->id_course); ?>" class="woocommerce-button button woocerti-edit-button"><?php echo __('Edit', 'woocertificatespackage'); ?></a>
                            <a href="<?php echo esc_url(wp_nonce_url($endpoint_url.'?action=delete&course_id='.$course->id_course, 'delete_course')); ?>" class="woocommerce-button button woocerti-delete-button"><?php echo __('Delete', 'woocertificatespackage'); ?></a>
                        <?php elseif ($course->status === 'Approved') : ?>
                            <a href="<?php echo esc_url($endpoint_url.'?action=buy&course_id='.$course->id_course); ?>" class="woocommerce-button button woocerti-buy-button"><?php echo __('Buy Certificates', 'woocertificatespackage'); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    // Generates pagination links
    if ($total_pages > 1) {
        $paginate_args = array(
            'base' => $endpoint_url.'%_%',
            'format' => '?pageds=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'type'=> 'list',
        );
        // If there are already parameters in the URL, use 'add_query_arg'
        $existing_args = array();
        if (isset($_GET['action'])) {
            $existing_args['action'] = $_GET['action'];
        }
        if (isset($_GET['orderby'])) {
            $existing_args['orderby'] = $_GET['orderby'];
        }
        if (isset($_GET['order'])) {
            $existing_args['order'] = $_GET['order'];
        }
        if (!empty($existing_args)) {
            $paginate_args['add_args'] = $existing_args;
        }
    ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-border woocommerce-courses-pagination">
            <?php echo paginate_links($paginate_args); ?>
        </div>
    <?php } ?>
<?php endif; ?>