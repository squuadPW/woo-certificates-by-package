<div class="woocerti-certificates-header">
    <h2><?php echo __('My Certificates', 'woocertificatespackage'); ?></h2>
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
?>
<?php if (empty($certificates_by_course) && $current_page == 1) : ?>
    <p><?php echo __('You have no certificates available for any courses yet.', 'woocertificatespackage'); ?></p>
<?php elseif (empty($certificates_by_course)) : ?>
    <p><?php echo __('No certificates found for this page.', 'woocertificatespackage'); ?></p>
<?php else : ?>
    <table class="woocommerce-MyAccount-certificates-table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="woocommerce-MyAccount-certificates-table__header woocommerce-MyAccount-certificates-table__header--course-name <?php echo esc_attr($course_name_sort['class']); ?>">
                    <a href="<?php echo $course_name_sort['url']; ?>">
                        <span class="nobr"><?php echo __('Course Name', 'woocertificatespackage'); ?></span>
                        <?php echo $course_name_sort['icon']; ?>
                    </a>
                </th>
                <th class="woocommerce-MyAccount-certificates-table__header woocommerce-MyAccount-certificates-table__header--quantity-purchased">
                    <span class="nobr"><?php echo __('Total Purchased', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-certificates-table__header woocommerce-MyAccount-certificates-table__header--total-issued">
                    <span class="nobr"><?php echo __('Total Issued', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-certificates-table__header woocommerce-MyAccount-certificates-table__header--actions">
                    <span class="nobr"><?php echo __('Actions', 'woocertificatespackage'); ?></span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($certificates_by_course as $certificate_data) : ?>
                <?php
                    $issue_certificate_url = wc_get_account_endpoint_url('issue-certificate');
                ?>
                <tr class="woocommerce-MyAccount-certificates-table__row">
                    <td class="woocommerce-MyAccount-certificates-table__cell woocommerce-MyAccount-certificates-table__cell--course-name" data-title="<?php echo esc_attr(__('Course Name', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($certificate_data->course_name); ?>
                    </td>
                    <td class="woocommerce-MyAccount-certificates-table__cell woocommerce-MyAccount-certificates-table__cell--quantity-purchased" data-title="<?php echo esc_attr(__('Total Purchased', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($certificate_data->quantity_purchased); ?>
                    </td>
                    <td class="woocommerce-MyAccount-certificates-table__cell woocommerce-MyAccount-certificates-table__cell--total-issued" data-title="<?php echo esc_attr(__('Total Issued', 'woocertificatespackage')); ?>">
                        <?php if ($certificate_data->total_issued > 0) : ?>
                            <a href="<?php echo esc_url(add_query_arg(array('action' => 'list_issued', 'course_id' => $certificate_data->id_course), $issue_certificate_url)); ?>">
                                <?php echo esc_html($certificate_data->total_issued); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($certificate_data->total_issued); ?>
                        <?php endif; ?>
                    </td>
                    <td class="woocommerce-MyAccount-certificates-table__cell woocerti-actions" data-title="<?php echo esc_attr(__('Actions', 'woocertificatespackage')); ?>">
                        <?php if ($certificate_data->total_issued !== $certificate_data->quantity_purchased) : ?>
                            <div class="woocerti-dropdown">
                                <button class="woocommerce-button button woocerti-issue-button"><?php echo __('Issue Certificate', 'woocertificatespackage'); ?></button>
                                <div class="woocerti-dropdown-content">
                                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('issue-certificate').'?course_id='.$certificate_data->id_course.'&mode=single'); ?>">
                                        <?php echo __('Per student', 'woocertificatespackage'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('issue-certificate').'?course_id='.$certificate_data->id_course.'&mode=bulk'); ?>">
                                        <?php echo __('Bulk upload', 'woocertificatespackage'); ?>
                                    </a>
                                </div>
                            </div>
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
            'type' => 'list',
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
        <div class="woocommerce-pagination woocommerce-pagination--without-border woocommerce-certificates-pagination">
            <?php echo paginate_links($paginate_args); ?>
        </div>
    <?php } ?>
<?php endif; ?>