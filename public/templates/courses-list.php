<div class="woocerti-courses-header">
    <h2><?php echo __('My Courses', 'woocertificatespackage'); ?></h2>
    <a href="<?php echo esc_url($endpoint_url.'?action=create'); ?>" class="woocommerce-button button button-primary"><?php echo __('Create Course', 'woocertificatespackage'); ?></a>
</div>
<?php if (empty($courses) && $current_page == 1) : ?>
    <p><?php echo __('You have not created any courses yet.', 'woocertificatespackage'); ?></p>
<?php elseif (empty($courses)) : ?>
    <p><?php echo __('No courses found for this page.', 'woocertificatespackage'); ?></p>
<?php else : ?>
    <table class="woocommerce-MyAccount-courses-table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--course-name">
                    <span class="nobr"><?php echo __('Course Name', 'woocertificatespackage'); ?></span>
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
                <th class="woocommerce-MyAccount-courses-table__header woocommerce-MyAccount-courses-table__header--status">
                    <span class="nobr"><?php echo __('Status', 'woocertificatespackage'); ?></span>
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
        if (isset($_GET['action'])) {
            $paginate_args['add_args'] = array('action' => $_GET['action']);
        }
    ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-border woocommerce-courses-pagination">
            <?php echo paginate_links($paginate_args); ?>
        </div>
    <?php } ?>
<?php endif; ?>