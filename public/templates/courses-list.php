<h2><?php echo __('My Courses', 'woocertificatespackage'); ?></h2>
<p><a href="<?php echo esc_url($endpoint_url . '?action=create'); ?>" class="woocommerce-button button"><?php echo __('Add New Course', 'woocertificatespackage'); ?></a></p>

<?php if ($courses) : ?>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
            <tr>
                <th class="woocommerce-orders-table__header-name"><?php echo __('Course Name', 'woocertificatespackage'); ?></th>
                <th class="woocommerce-orders-table__header-status"><?php echo __('Status', 'woocertificatespackage'); ?></th>
                <th class="woocommerce-orders-table__header-actions"><?php echo __('Actions', 'woocertificatespackage'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $course) : ?>
                <tr>
                    <td data-title="<?php echo __('Course Name', 'woocertificatespackage'); ?>"><?php echo esc_html($course->course_name); ?></td>
                    <td data-title="<?php echo __('Status', 'woocertificatespackage'); ?>"><?php echo esc_html($course->status); ?></td>
                    <td class="woocommerce-orders-table__cell-actions">
                        <a href="<?php echo esc_url($endpoint_url . '?action=edit&course_id=' . $course->id_course); ?>" class="woocommerce-button button view"><?php echo __('Edit', 'woocertificatespackage'); ?></a>
                        <a href="<?php echo esc_url($endpoint_url . '?action=delete&course_id=' . $course->id_course . '&_wpnonce=' . wp_create_nonce('delete_course')); ?>" class="woocommerce-button button delete"><?php echo __('Delete', 'woocertificatespackage'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <div class="woocommerce-info woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocertificatespackage-message">
        <?php echo __('No courses found.', 'woocertificatespackage'); ?>
    </div>
<?php endif; ?>