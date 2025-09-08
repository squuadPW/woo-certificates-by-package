<div class="woocerti-certificates-header">
    <h2><?php echo __('My Certificates', 'woocertificatespackage'); ?></h2>
</div>

<?php if (empty($certificates_by_course)) : ?>
    <p><?php echo __('You have no certificates available for any courses yet.', 'woocertificatespackage'); ?></p>
<?php else : ?>
    <table class="woocommerce-MyAccount-certificates-table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="woocommerce-MyAccount-certificates-table__header woocommerce-MyAccount-certificates-table__header--course-name">
                    <span class="nobr"><?php echo __('Course Name', 'woocertificatespackage'); ?></span>
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
                <tr class="woocommerce-MyAccount-certificates-table__row">
                    <td class="woocommerce-MyAccount-certificates-table__cell woocommerce-MyAccount-certificates-table__cell--course-name" data-title="<?php echo esc_attr(__('Course Name', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($certificate_data->course_name); ?>
                    </td>
                    <td class="woocommerce-MyAccount-certificates-table__cell woocommerce-MyAccount-certificates-table__cell--quantity-purchased" data-title="<?php echo esc_attr(__('Total Purchased', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($certificate_data->quantity_purchased); ?>
                    </td>
                    <td class="woocommerce-MyAccount-certificates-table__cell woocommerce-MyAccount-certificates-table__cell--total-issued" data-title="<?php echo esc_attr(__('Total Issued', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($certificate_data->total_issued); ?>
                    </td>
                    <td class="woocommerce-MyAccount-certificates-table__cell woocommerce-MyAccount-certificates-table__cell--actions" data-title="<?php echo esc_attr(__('Actions', 'woocertificatespackage')); ?>">
                        <?php if ($certificate_data->total_issued !== $certificate_data->quantity_purchased) : ?>
                            <a href="#" class="woocommerce-button button woocerti-issue-button"><?php echo __('Issue Certificate', 'woocertificatespackage'); ?></a>
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
        if (isset($_GET['action'])) {
            $paginate_args['add_args'] = array('action' => $_GET['action']);
        }
    ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-border woocommerce-certificates-pagination">
            <?php echo paginate_links($paginate_args); ?>
        </div>
    <?php } ?>
<?php endif; ?>