<div class="woocerti-issued-list-header">
    <h2><?php echo __('Certificates Issued for: ', 'woocertificatespackage').esc_html($course_name); ?></h2>
</div>

<?php if (empty($students)) : ?>
    <p><?php echo __('No certificates were issued.', 'woocertificatespackage'); ?></p>
<?php else : ?>
    <table class="woocommerce-MyAccount-issued-certificates-table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--name">
                    <span class="nobr"><?php echo __('First Name', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--last-name">
                    <span class="nobr"><?php echo __('Last Name', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--email">
                    <span class="nobr"><?php echo __('Email', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--document-number">
                    <span class="nobr"><?php echo __('Document Number', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--status">
                    <span class="nobr"><?php echo __('Status', 'woocertificatespackage'); ?></span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student) : ?>
                <tr class="woocommerce-MyAccount-issued-certificates-table__row">
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--name" data-title="<?php echo esc_attr(__('First Name', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($student['first_name']); ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--last-name" data-title="<?php echo esc_attr(__('Last Name', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($student['last_name']); ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--email" data-title="<?php echo esc_attr(__('Email', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($student['email']); ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--document-number" data-title="<?php echo esc_attr(__('Document Number', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($student['document_number']); ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--status" data-title="<?php echo esc_attr(__('Status', 'woocertificatespackage')); ?>">
                        <?php
                            if ($student['status'] === 'issued') {
                                echo '<span class="status-issued">'.__('Issued', 'woocertificatespackage').'</span>';
                            } else {
                                echo '<span class="status-failed">'.__('Failed', 'woocertificatespackage').'</span>';
                            }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="return-link">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('certificates')); ?>"><?php echo __('Return to My Certificates', 'woocertificatespackage'); ?></a>
    </p>
<?php endif; ?>