<div class="wrap woocerti-course-form">
    <h1><?php echo esc_html($form_title); ?></h1>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="course-form" novalidate="novalidate">
        <input type="hidden" name="action" value="woocerti_save_course_admin">
        <?php wp_nonce_field('woocerti_save_course_admin_data', 'woocerti_nonce'); ?>
        <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">

        <div class="form-section-card">
            <h2><?php echo esc_html(__('Course Details', 'woocertificatespackage')); ?></h2>

            <div class="form-group-row two-columns">
                <div class="form-group-item">
                    <label for="id_user">
                        <strong>
                            <?php echo esc_html(__('Assigned User (Alliance)', 'woocertificatespackage')); ?>
                            <span class="required" aria-hidden="true">*</span>
                        </strong>
                    </label>
                    <select id="id_user" name="id_user" class="regular-text">
                        <option value="" <?php selected($id_user, ''); ?>><?php echo esc_html(__('Select User', 'woocertificatespackage')); ?></option>
                        <?php foreach ($institutes as $institute) : ?>
                            <option value="<?php echo esc_attr($institute->ID); ?>" <?php selected($id_user, $institute->ID); ?>>
                                <?php echo esc_html($institute->display_name . ' - ' . $institute->user_email); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message"></span>
                </div>
                <div class="form-group-item">
                    <label for="tutor_instructor"><strong><?php echo esc_html(__('Tutor or Instructor', 'woocertificatespackage')); ?></strong></label>
                    <input type="text" name="tutor_instructor" id="tutor_instructor" value="<?php echo esc_attr($tutor_instructor); ?>" class="regular-text">
                </div>
            </div>

            <div class="form-group-row three-columns">
                <div class="form-group-item">
                    <label><strong><?php echo esc_html(__('Is the assigned user the tutor?', 'woocertificatespackage')); ?></strong></label>
                    <div class="is-tutor-checkbox-wrapper">
                        <input type="checkbox" id="is_tutor_checkbox" name="is_tutor" value="1" <?php checked(isset($_POST['is_tutor']), 1); ?>>
                        <label for="is_tutor_checkbox"><?php echo esc_html(__('Yes, the assigned user is the tutor', 'woocertificatespackage')); ?></label>
                    </div>
                </div>
                <div class="form-group-item">
                    <label for="status"><strong><?php echo esc_html(__('Status', 'woocertificatespackage')); ?></strong></label>
                    <select id="status" name="status" class="regular-text">
                        <option value="Pending" <?php selected($status, 'Pending'); ?>><?php echo esc_html(__('Awaiting Review', 'woocertificatespackage')); ?></option>
                        <option value="Approved" <?php selected($status, 'Approved'); ?>><?php echo esc_html(__('Approved', 'woocertificatespackage')); ?></option>
                        <option value="Rejected" <?php selected($status, 'Rejected'); ?>><?php echo esc_html(__('Rejected', 'woocertificatespackage')); ?></option>
                    </select>
                    <span class="error-message"></span>
                </div>
                <?php if ($course_id > 0) : ?>
                <div class="form-group-item">
                    <label for="course_code"><strong><?php echo esc_html(__('Course Code', 'woocertificatespackage')); ?></strong></label>
                    <input type="text" name="course_code" id="course_code" value="<?php echo esc_attr($code); ?>" class="regular-text" readonly="readonly">
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group-row">
                <div class="form-group-item">
                    <label for="course_name">
                        <strong>
                            <?php echo esc_html(__('Course Name', 'woocertificatespackage')); ?>
                            <span class="required" aria-hidden="true">*</span>
                        </strong>
                    </label>
                    <input type="text" name="course_name" id="course_name" value="<?php echo esc_attr($course_name); ?>" class="regular-text">
                    <span class="error-message"></span>
                </div>
            </div>
        </div>

        <div class="form-section-card">
            <h2><?php echo esc_html(__('Academic Details', 'woocertificatespackage')); ?></h2>

            <div class="form-group-row three-columns">
                <div class="form-group-item">
                    <label for="academic_hours">
                        <strong>
                            <?php echo esc_html(__('Academic Hours', 'woocertificatespackage')); ?>
                            <span class="required" aria-hidden="true">*</span>
                        </strong>
                    </label>
                    <input type="number" step="0.01" name="academic_hours" id="academic_hours" value="<?php echo esc_attr($academic_hours); ?>" min="0" class="regular-text">
                    <span class="error-message"></span>
                </div>
                <div class="form-group-item">
                    <label for="location"><strong><?php echo esc_html(__('Location', 'woocertificatespackage')); ?></strong></label>
                    <input type="text" name="location" id="location" value="<?php echo esc_attr($location); ?>" class="regular-text">
                </div>
                <div class="form-group-item">
                    <label for="course_date"><strong><?php echo esc_html(__('Course Date', 'woocertificatespackage')); ?></strong></label>
                    <input type="date" name="course_date" id="course_date" value="<?php echo esc_attr($course_date); ?>" class="regular-text">
                </div>
            </div>

            <div class="form-group-row">
                <div class="form-group-item">
                    <label for="academic_program"><strong><?php esc_html_e('Academic Program', 'woocertificatespackage'); ?></strong></label>
                    <textarea name="academic_program" id="academic_program" rows="5" class="large-text"><?php echo esc_textarea($academic_program); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-section-card">
            <h2><?php echo esc_html(__('Certification Fees', 'woocertificatespackage')); ?></h2>

            <div class="form-group-row three-columns">
                <div class="form-group-item">
                    <label for="price_per_student">
                        <strong>
                            <?php echo esc_html(__('Price per student', 'woocertificatespackage')); ?>
                            <span class="required" aria-hidden="true">*</span>
                        </strong>
                    </label>
                    <input type="number" step="0.01" name="price_per_student" id="price_per_student" value="<?php echo esc_attr($price_per_student); ?>" min="0" class="regular-text">
                    <span class="error-message"></span>
                </div>
                <div class="form-group-item">
                    <label for="certification_fee_type">
                        <strong>
                            <?php echo esc_html(__('Certification Fee Type', 'woocertificatespackage')); ?>
                            <span class="required" aria-hidden="true">*</span>
                        </strong>
                    </label>
                    <select id="certification_fee_type" name="certification_fee_type" class="regular-text">
                        <option value="Fixed" <?php selected($certification_fee_type, 'Fixed'); ?>><?php echo esc_html(__('Fixed', 'woocertificatespackage')); ?></option>
                        <option value="Percentage" <?php selected($certification_fee_type, 'Percentage'); ?>><?php echo esc_html(__('Percentage', 'woocertificatespackage')); ?></option>
                    </select>
                    <span class="error-message"></span>
                </div>
                <div class="form-group-item">
                    <label for="certification_fee_value">
                        <strong>
                            <?php echo esc_html(__('Certification Fee Value', 'woocertificatespackage')); ?>
                            <span class="required" aria-hidden="true">*</span>
                        </strong>
                    </label>
                    <input type="number" step="0.01" name="certification_fee_value" id="certification_fee_value" value="<?php echo esc_attr($certification_fee_value); ?>" min="0" class="regular-text">
                    <span class="error-message"></span>
                </div>
            </div>
        </div>

        <?php if ($course_id > 0) : ?>
        <div class="date-display-card">
            <p class="date-card-item">
                <strong><?php echo esc_html(__('Creation Date', 'woocertificatespackage')); ?></strong>
                <strong class="text-date">
                    <?php echo esc_attr(wp_date('d/m/Y', strtotime($date_created))); ?>
                    <?php echo esc_html(__('at', 'woocertificatespackage')); ?>
                    <?php echo esc_attr(wp_date('H:i', strtotime($date_created))); ?>
                </strong>
            </p>
            <p class="date-card-item">
                <strong>
                    <?php echo esc_html(__('Last Updated', 'woocertificatespackage')); ?>:
                </strong>
                <strong class="text-date">
                    <?php echo esc_attr(wp_date('d/m/Y', strtotime($date_updated))); ?>
                    <?php echo esc_html(__('at', 'woocertificatespackage')); ?>
                    <?php echo esc_attr(wp_date('H:i', strtotime($date_updated))); ?>
                </strong>
            </p>
        </div>
        <?php endif; ?>

        <p class="submit">
            <?php submit_button(__('Save changes', 'woocertificatespackage'), 'primary', 'submit', false); ?>
        </p>
    </form>
</div>