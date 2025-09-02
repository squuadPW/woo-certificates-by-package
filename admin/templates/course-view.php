<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Get the course ID and action from the URL
$course_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

// Instantiate the main class to get the course data
$woorceti_admin = new Woocerti_Admin();
$course_details = $woorceti_admin->get_course_details($course_id);

// If the course is not found, display an error
if (!$course_details) {
    wp_die(__('Course not found.', 'woocertificatespackage'));
}

// Assign course details to variables for easy use in the view.
foreach ($course_details as $key => $value) {
    $$key = $value;
}

?>

<div class="wrap woocerti-course-form">
    <h1>
        <?php echo __('Course', 'woocertificatespackage').': '.$course_name; ?>
    </h1>

    <div class="form-section-card">
        <div class="card-title">
            <h2><?php echo __('Course Details', 'woocertificatespackage'); ?></h2>
        </div>
        <div class="card-content view-course">
            <div class="form-group-row two-columns">
                <div class="form-group-item">
                    <label><strong><?php echo __('Course Name', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html($course_name); ?></p>
                </div>
                <div class="form-group-item">
                    <label><strong><?php echo __('Course Code', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html($code); ?></p>
                </div>
            </div>

            <div class="form-group-row three-columns">
                <div class="form-group-item">
                    <label><strong><?php echo __('Status', 'woocertificatespackage'); ?></strong></label>
                    <p><?php
                        $status_label = '';
                        switch ($status) {
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
                                $status_label = esc_html($status);
                                break;
                        }
                        echo esc_html($status_label);
                    ?></p>
                </div>
                <div class="form-group-item">
                    <label><strong><?php echo __('Tutor or Instructor', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html($tutor_instructor); ?></p>
                </div>
                <div class="form-group-item">
                    <label><strong><?php echo __('Assigned User (Alliance)', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html(get_the_author_meta('display_name', $id_user).' - '.get_the_author_meta('user_email', $id_user)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section-card">
        <div class="card-title">
            <h2><?php echo __('Academic Details', 'woocertificatespackage'); ?></h2>
        </div>
        <div class="card-content view-course">
            <div class="form-group-row three-columns">
                <div class="form-group-item">
                    <label><strong><?php echo __('Academic Hours', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html($academic_hours); ?></p>
                </div>
                <div class="form-group-item">
                    <label><strong><?php echo __('Location', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html($location); ?></p>
                </div>
                <div class="form-group-item">
                    <label><strong><?php echo __('Course Date', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html(wp_date('d/m/Y', strtotime($course_date))); ?></p>
                </div>
            </div>

            <div class="form-group-row">
                <div class="form-group-item">
                    <label><strong><?php echo __('Academic Program', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html_e($academic_program); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section-card">
        <div class="card-title">
            <h2><?php echo __('Certification Fees', 'woocertificatespackage'); ?></h2>
        </div>
        <div class="card-content view-course">
            <div class="form-group-row three-columns">
                <div class="form-group-item">
                    <label><strong><?php echo __('Price per student', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo wc_price($price_per_student); ?></p>
                </div>
                <div class="form-group-item">
                    <label><strong><?php echo __('Certification Fee Type', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo __($certification_fee_type, 'woocertificatespackage'); ?></p>
                </div>
                <div class="form-group-item">
                    <label><strong><?php echo __('Certification Fee Value', 'woocertificatespackage'); ?></strong></label>
                    <p><?php echo esc_html($certification_fee_value); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="date-display-card">
        <p class="date-card-item">
            <strong><?php echo esc_html(__('Creation Date', 'woocertificatespackage')); ?></strong>
            <strong class="text-date">
                <?php echo esc_html(wp_date('d/m/Y', strtotime($date_created))); ?>
                <?php echo esc_html(__('at', 'woocertificatespackage')); ?>
                <?php echo esc_html(wp_date('H:i', strtotime($date_created))); ?>
            </strong>
        </p>
        <p class="date-card-item">
            <strong>
                <?php echo esc_html(__('Last Updated', 'woocertificatespackage')); ?>:
            </strong>
            <strong class="text-date">
                <?php echo esc_html(wp_date('d/m/Y', strtotime($date_updated))); ?>
                <?php echo esc_html(__('at', 'woocertificatespackage')); ?>
                <?php echo esc_html(wp_date('H:i', strtotime($date_updated))); ?>
            </strong>
        </p>
    </div>

    <p class="submit">
        <a href="<?php echo esc_url(admin_url('admin.php?page=woocerti-courses')); ?>" class="button button-secondary">
            <?php echo __('Back to Courses', 'woocertificatespackage'); ?>
        </a>
    </p>
</div>

