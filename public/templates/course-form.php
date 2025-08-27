<h2><?php echo esc_html($form_title); ?></h2>
<form method="post" action="<?php echo esc_url($endpoint_url); ?>" id="course-form">
    <?php wp_nonce_field('save_course_data', 'course_nonce'); ?>
    <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">

    <div class="form-row">
        <p class="form-row-field">
            <label for="course_name"><?php echo __('Course Name', 'woocertificatespackage'); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="text" id="course_name" name="course_name" value="<?php echo esc_attr($course_name); ?>" maxlength="300">
            <span class="validation-message"></span>
        </p>
    </div>
    <div class="form-row">
         <p class="row-check">
            <input type="checkbox" id="is_my_name" name="is_my_name">
            <label for="is_my_name" class="tutor-checkbox-label"><?php echo __('I am the tutor or instructor', 'woocertificatespackage'); ?></label>
        </p>
    </div>

    <div class="form-row">
        <p class="form-row-field">
            <label for="tutor_instructor"><?php echo __('Tutor or Instructor', 'woocertificatespackage'); ?></label>
            <input type="text" id="tutor_instructor" name="tutor_instructor" value="<?php echo esc_attr($tutor_instructor); ?>">
        </p>
        <p class="form-row-field">
            <label for="academic_hours"><?php echo __('Academic Hours', 'woocertificatespackage'); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="number" step="0.01" id="academic_hours" name="academic_hours" value="<?php echo esc_attr($academic_hours); ?>" min="0">
            <span class="validation-message"></span>
        </p>
    </div>

    <div class="form-row">
        <p class="form-row-field">
            <label for="location"><?php echo __('Location', 'woocertificatespackage'); ?></label>
            <input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>">
        </p>
        <p class="form-row-field">
            <label for="course_date"><?php echo __('Date', 'woocertificatespackage'); ?></label>
            <input type="date" id="course_date" class="input-text input-field" name="course_date" value="<?php echo esc_attr($course_date); ?>" min="<?php echo date('Y-m-d'); ?>">
            <span class="validation-message"></span>
        </p>
    </div>

    <div class="form-row">
        <p class="form-row-field">
            <label for="academic_program"><?php echo __('Academic Program', 'woocertificatespackage'); ?></label>
            <textarea id="academic_program" name="academic_program"><?php echo esc_textarea($academic_program); ?></textarea>
        </p>
    </div>

    <div class="form-row form-row--three-fields">
        <p class="form-row-field">
            <label for="price_per_student"><?php echo __('Price per student', 'woocertificatespackage'); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="number" step="0.01" id="price_per_student" name="price_per_student" value="<?php echo esc_attr($price_per_student); ?>" min="0">
            <span class="validation-message"></span>
        </p>
        <p class="form-row-field">
            <label for="certification_fee_type"><?php echo __('Certification Fee Type', 'woocertificatespackage'); ?> <span class="required" aria-hidden="true">*</span></label>
            <select id="certification_fee_type" name="certification_fee_type" class="input-text input-field">
                <option value="Fixed" <?php selected($certification_fee_type, 'Fixed'); ?>><?php echo __('Fixed', 'woocertificatespackage'); ?></option>
                <option value="Percentage" <?php selected($certification_fee_type, 'Percentage'); ?>><?php echo __('Percentage', 'woocertificatespackage'); ?></option>
            </select>
            <span class="validation-message"></span>
        </p>
        <p class="form-row-field">
            <label for="certification_fee_value"><?php echo __('Certification Fee Value', 'woocertificatespackage'); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="number" step="0.01" id="certification_fee_value" name="certification_fee_value" value="<?php echo esc_attr($certification_fee_value); ?>" min="0">
            <span class="validation-message"></span>
        </p>
    </div>
    <p>
        <span class="form-required-fields-note">
            <?php echo __('Fields marked with * are required', 'woocertificatespackage'); ?>
        </span>
    </p>
    <p class="p-buttons">
        <input type="submit" class="woocommerce-button button" name="save_course" value="<?php echo esc_attr(__('Save Course', 'woocertificatespackage')); ?>">
        <input type="submit" class="woocommerce-button button" name="save_draft" value="<?php echo esc_attr($submit_label); ?>">
    </p>
</form>