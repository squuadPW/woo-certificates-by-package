<div class="woocerti-issue-header">
    <h2><?php echo __('Issue Certificates for: ', 'woocertificatespackage').esc_html($course_name); ?></h2>
</div>

<div class="woocerti-issue-options">
    <?php if (isset($_GET['mode']) && $_GET['mode'] === 'single') : ?>
        <div class="woocerti-single-student">
            <h3><?php echo __('Student data', 'woocertificatespackage'); ?></h3>
            <form method="post" action="" id="issue-single-student-form">
                <div id="woocerti-response-message" style="display: none;"></div>
                <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>" />
                <?php wp_nonce_field('woocerti_issue_certificate_action', 'woocerti_issue_certificate_nonce'); ?>
                <div class="form-row form-row--three-fields">
                    <p class="form-row-field">
                        <label for="single_student_document_type"><?php echo __('Document Type', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <select class="input-text" name="single_student_document_type" id="single_student_document_type">
                            <option value="" selected="selected"><?php echo __('Select an option', 'woocertificatespackage'); ?></option>
                            <option value="passport"><?php echo __('Passport', 'woocertificatespackage'); ?></option>
                            <option value="identification_document"><?php echo __('Identification Document', 'woocertificatespackage'); ?></option>
                            <option value="ssn"><?php echo __('SSN', 'woocertificatespackage'); ?></option>
                        </select>
                        <span class="validation-message"></span>
                    </p>
                    <p class="form-row-field">
                        <label for="single_student_document_number"><?php echo __('Document Number', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="text" class="input-text" name="single_student_document_number" id="single_student_document_number" />
                        <span class="validation-message"></span>
                    </p>
                    <p id="nationality-container" style="display: none;" class="form-row-field">
                        <label for="single_student_inssued_in"><?php echo __('Document issued in?', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <select class="input-text" name="single_student_inssued_in" id="single_student_inssued_in">
                            <option value=""><?php echo __('Select country', 'woocertificatespackage'); ?></option>
                        </select>
                        <span class="validation-message"></span>
                    </p>
                </div>
                <div class="form-row">
                    <p class="form-row-field">
                        <label for="single_student_first_name"><?php echo __('First Name', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="text" class="input-text" name="single_student_first_name" id="single_student_first_name" />
                        <span class="validation-message"></span>
                    </p>
                    <p class="form-row-field">
                        <label for="single_student_last_name"><?php echo __('Last Name', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="text" class="input-text" name="single_student_last_name" id="single_student_last_name" />
                        <span class="validation-message"></span>
                    </p>
                </div>
                <div class="form-row">
                    <p class="form-row-field">
                        <label for="phone_number"><?php echo __('Phone Number', 'woocertificatespackage'); ?></label>
                        <input type="tel" class="input-text" name="phone_number" id="phone_number" />
                        <span class="validation-message"></span>
                    </p>
                    <p class="form-row-field">
                        <label for="single_student_email"><?php echo __('Student Email', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="email" class="input-text" name="single_student_email" id="single_student_email" />
                        <span class="validation-message"></span>
                    </p>
                </div>
                <p>
                    <span class="form-required-fields-note">
                        <?php echo __('Fields marked with * are required', 'woocertificatespackage'); ?>
                    </span>
                </p>
                <p class="p-buttons">
                    <button type="submit" class="woocommerce-button button woocerti-issue-submit"><?php echo __('Issue Certificate', 'woocertificatespackage'); ?></button>
                </p>
            </form>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['mode']) && $_GET['mode'] === 'bulk') : ?>
        <div class="woocerti-bulk-upload">
            <h3><?php echo __('Bulk Upload via Excel or CSV', 'woocertificatespackage'); ?></h3>
            <form method="post" action="" enctype="multipart/form-data">
                <p class="form-row form-row-wide">
                    <label for="student_list"><?php echo __('Select a file', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                    <div id="file-drop-area">
                        <p><?php echo __('Drag and drop your file here', 'woocertificatespackage'); ?> <br> <?php echo __('or', 'woocertificatespackage'); ?></p>
                        <button type="button" class="button woocerti-select-files-button"><?php echo __('Select your file', 'woocertificatespackage'); ?></button>
                    </div>
                    <input type="file" id="student_list" name="student_list" accept=".csv,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" style="display: none;">
                    <span class="file-name-display"></span>
                    <span class="validation-message"></span>
                </p>
                <p class="description">
                    <?php echo __('The file should have a header row and columns: "first_name", "last_name", "email", and "document_number".', 'woocertificatespackage'); ?>
                    <br>
                    <?php echo __('All fields are required.', 'woocertificatespackage'); ?>
                </p>
                <p class="form-row">
                    <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>" />
                    <?php wp_nonce_field('woocerti_issue_certificate_action', 'woocerti_issue_certificate_nonce'); ?>
                    <button type="submit" class="woocommerce-button button woocerti-issue-submit"><?php echo __('Upload and Issue', 'woocertificatespackage'); ?></button>
                </p>
            </form>
        </div>
    <?php endif; ?>

</div>