<div class="woocerti-issued-list-header">
    <h2><?php echo esc_html($course_name); ?></h2>
</div>

<?php if (empty($students)) : ?>
    <p><?php echo __('No certificates were issued.', 'woocertificatespackage'); ?></p>
<?php else : ?>
    <div class="woocerti-certificate-summary">
        <h3 class="summary-title"><?php echo __('Certificate Summary', 'woocertificatespackage'); ?></h3>
        <div class="summary-cards-container">
            <div class="summary-card">
                <h4><?php echo __('Purchased', 'woocertificatespackage'); ?></h4>
                <p class="summary-value"><?php echo esc_html($certificate_summary['purchased']); ?></p>
            </div>
            <div class="summary-card">
                <h4><?php echo __('Issuedes', 'woocertificatespackage'); ?></h4>
                <p class="summary-value"><?php echo esc_html($certificate_summary['issued_count']); ?></p>
            </div>
            <div class="summary-card">
                <h4><?php echo __('Available', 'woocertificatespackage'); ?></h4>
                <p class="summary-value"><?php echo esc_html($certificate_summary['available']); ?></p>
            </div>
        </div>
    </div>
    <table class="woocommerce-MyAccount-issued-certificates-table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--row">
                    <span class="nobr">#</span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--full-name">
                    <span class="nobr"><?php echo __('Full Name', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--email">
                    <span class="nobr"><?php echo __('Email', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--document-number">
                    <span class="nobr"><?php echo __('Document Number', 'woocertificatespackage'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-issued-certificates-table__header woocommerce-MyAccount-issued-certificates-table__header--status">
                    <span class="nobr"><?php echo __('Issue Date', 'woocertificatespackage'); ?></span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $key => $student) : ?>
                <tr class="woocommerce-MyAccount-issued-certificates-table__row">
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--row" data-title="#">
                        <?php
                            $row_number = ($current_page - 1) * WOOCERTI_POSTS_PER_PAGE + $key + 1;
                            echo esc_html($row_number);
                        ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--full-name" data-title="<?php echo esc_attr(__('Full Name', 'woocertificatespackage')); ?>">
                        <?php echo esc_html(ucwords($student['full_name'])); ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--email" data-title="<?php echo esc_attr(__('Email', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($student['email']); ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--document-number" data-title="<?php echo esc_attr(__('Document Number', 'woocertificatespackage')); ?>">
                        <?php echo esc_html($student['document_number']); ?>
                    </td>
                    <td class="woocommerce-MyAccount-issued-certificates-table__cell woocommerce-MyAccount-issued-certificates-table__cell--status" data-title="<?php echo esc_attr(__('Status', 'woocertificatespackage')); ?>">
                        <?php echo esc_attr(wp_date('d/m/Y H:i', strtotime($student['date_created']))); ?>
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
    <p class="p-buttons">
        <?php
            // Check if a return URL was passed
            $return_url = isset($_GET['return_url']) ? esc_url($_GET['return_url']) : wc_get_account_endpoint_url('certificates');
        ?>
        <a href="<?php echo esc_url($return_url); ?>" class="woocommerce-button button btn-course woocerti-ml-0">
            <?php echo __('Return to Previous Page', 'woocertificatespackage'); ?>
        </a>
    </p>
<?php endif; ?>