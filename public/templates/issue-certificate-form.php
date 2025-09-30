<div class="woocerti-issue-header">
    <h2><?php echo __('Issue Certificates for: ', 'woocertificatespackage').esc_html($course_name); ?></h2>
</div>
<?php
    $issue_certificate_url = wc_get_account_endpoint_url('issue-certificate');
    $base_url = wc_get_account_endpoint_url('issue-certificate');
    $unencoded_url = add_query_arg(array('mode' => $_GET['mode'], 'course_id' => $course_id), $base_url);
    $return_url = urlencode($unencoded_url);
?>

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
                        <select class="" name="single_student_document_type" id="single_student_document_type">
                            <option value="" selected="selected"><?php echo __('Select an option', 'woocertificatespackage'); ?></option>
                            <option value="passport"><?php echo __('Passport', 'woocertificatespackage'); ?></option>
                            <option value="identification_document"><?php echo __('Identification Document', 'woocertificatespackage'); ?></option>
                            <option value="ssn"><?php echo __('SSN', 'woocertificatespackage'); ?></option>
                        </select>
                        <span class="validation-message"></span>
                    </p>
                    <p class="form-row-field">
                        <label for="single_student_document_number"><?php echo __('Document Number', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="text" class="" name="single_student_document_number" id="single_student_document_number" />
                        <span class="validation-message"></span>
                    </p>
                    <p id="nationality-container" style="display: none;" class="form-row-field">
                        <label for="single_student_inssued_in"><?php echo __('Document issued in?', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <select class="" name="single_student_inssued_in" id="single_student_inssued_in">
                            <option value=""><?php echo __('Select country', 'woocertificatespackage'); ?></option>
                        </select>
                        <span class="validation-message"></span>
                    </p>
                </div>
                <div class="form-row">
                    <p class="form-row-field">
                        <label for="single_student_first_name"><?php echo __('First Name', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="text" class="" name="single_student_first_name" id="single_student_first_name" />
                        <span class="validation-message"></span>
                    </p>
                    <p class="form-row-field">
                        <label for="single_student_last_name"><?php echo __('Last Name', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="text" class="" name="single_student_last_name" id="single_student_last_name" />
                        <span class="validation-message"></span>
                    </p>
                </div>
                <div class="form-row">
                    <p class="form-row-field">
                        <label for="phone_number"><?php echo __('Phone Number', 'woocertificatespackage'); ?></label>
                        <input type="tel" class="" name="phone_number" id="phone_number" />
                        <span class="validation-message"></span>
                    </p>
                    <p class="form-row-field">
                        <label for="single_student_email"><?php echo __('Student Email', 'woocertificatespackage'); ?> <span class="required">*</span></label>
                        <input type="email" class="" name="single_student_email" id="single_student_email" />
                        <span class="validation-message"></span>
                    </p>
                </div>
                <p class="p-legends">
                    <span class="form-required-fields-note">
                        <?php echo __('Fields marked with * are required', 'woocertificatespackage'); ?>
                    </span>
                </p>
                <p class="p-buttons">
                    <button type="submit" class="woocommerce-button button button-primary btn-course woocerti-issue-submit"><?php echo __('Issue Certificate', 'woocertificatespackage'); ?></button>
                    <a class="woocommerce-button button btn-course"
                        href="<?php echo esc_url(add_query_arg(array('action' => 'list_issued', 'course_id' => $course_id, 'return_url' => $return_url), $issue_certificate_url)); ?>">
                        <?php echo __('View Issued Certificates', 'woocertificatespackage'); ?>
                    </a>
                </p>
            </form>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['mode']) && $_GET['mode'] === 'bulk') : ?>
        <?php
            // Check for general form errors stored in a transient
            $errors = get_transient('woocerti_form_errors');
            if ($errors && is_array($errors)) {
                echo '<ul class="woocerti-error-messages">';
                foreach ($errors as $error) {
                    echo '<li class="error-message">'.esc_html($error).'</li>';
                }
                echo '</ul>';
                delete_transient('woocerti_form_errors');
            }
        ?>
        <div class="woocerti-bulk-upload">
            <h3><?php echo __('Bulk Upload via Excel or CSV', 'woocertificatespackage'); ?></h3>
            <form method="post" action="" enctype="multipart/form-data" id="woocerti-bulk-upload-form">
                <div id="woocerti-response-message" style="display: none;"></div>
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
                    <?php echo __('The file must have a header row with the following columns:', 'woocertificatespackage'); ?>
                    <br>
                    <strong><?php echo __('Required:', 'woocertificatespackage'); ?></strong> "document_type", "document_number", "inssued_in", "first_name", "last_name", "email".
                    <br>
                    <strong><?php echo __('Optional:', 'woocertificatespackage'); ?></strong> "phone_number".
                    <br>
                    <strong><?php echo __('Columns:', 'woocertificatespackage'); ?></strong>
                    <ul>
                        <li>
                            <strong>document_type</strong>: <?= __('Type of student identification document.', 'woocertificatespackage'); ?>
                            <br>
                            <?= __('Allowed values:', 'woocertificatespackage'); ?>
                            <ul>
                                <li><strong>passport</strong>: <?= __('Use this value for passport-type ID documents. This only applies to United States passports.', 'woocertificatespackage'); ?></li>
                                <li><strong>ssn</strong>: <?= __('Use this value for your United States Social Security Number.', 'woocertificatespackage'); ?></li>
                                <li><strong>identification_document</strong>: <?= __("Use this value for any other type of identification document (such as an ID card, DNI, driver's license, etc.).", 'woocertificatespackage'); ?></li>
                            </ul>
                        </li>
                        <li><strong>document_number</strong>: <?= __('Student identification number.', 'woocertificatespackage'); ?></li>
                        <li>
                            <strong>inssued_in</strong>: <?= __('Country where the document was issued.', 'woocertificatespackage'); ?>
                            <ul>
                                <li><?= __('For documents of type "identification_document", enter the two-letter country code (ISO 3166-1 alpha-2) where the document was issued. For example, "US" for the United States or "MX" for Mexico.', 'woocertificatespackage'); ?></li>
                                <li><?= __('This field is optional if the Document Type is "passport" or "ssn", as the system will automatically fill it in with the value "US".', 'woocertificatespackage'); ?></li>
                                <li>
                                    <details class="woocerti-collapsible-info">
                                        <summary>
                                            <?php echo __('Click to see the valid values for "inssued_in"', 'woocertificatespackage'); ?>
                                        </summary>
                                        <div class="woocerti-collapsible-content">
                                            <!-- <p><?php echo __('For the "inssued_in" column, use the two-letter country code (ISO 3166-1 alpha-2) as shown below:', 'woocertificatespackage'); ?></p> -->
                                            <ul style="list-style-type: none; padding: 0; margin: 0; columns: 4; column-gap: 20px;">
                                                <?php foreach ($nationalities as $code => $country): ?>
                                                    <li><?php echo esc_html($country); ?>: <strong><?php echo esc_html($code); ?></strong></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </details>
                                </li>
                            </ul>
                        </li>
                        <li><strong>first_name</strong>: <?= __('Student names.', 'woocertificatespackage'); ?></li>
                        <li><strong>last_name</strong>: <?= __("Student's last name.", 'woocertificatespackage'); ?></li>
                        <li><strong>email</strong>: <?= __('Student email address.', 'woocertificatespackage'); ?></li>
                        <li><strong>phone_number</strong>: <?= __("Student contact phone number. This field is optional, but it's recommended to include the country code to ensure proper validation. For example, +1 for the United States or +52 for Mexico.", 'woocertificatespackage'); ?></li>
                    </ul>
                </p>
                <p class="form-row">
                    <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>" />
                    <?php wp_nonce_field('woocerti_issue_certificate_action', 'woocerti_issue_certificate_nonce'); ?>
                </p>
                <p class="p-buttons">
                    <button type="submit" class="woocommerce-button button button-primary btn-course woocerti-issue-submit">
                        <?php echo __('Upload and Issue', 'woocertificatespackage'); ?>
                    </button>
                    <a class="woocommerce-button button btn-course"
                        href="<?php echo esc_url(add_query_arg(array('action' => 'list_issued', 'course_id' => $course_id, 'return_url' => $return_url), $issue_certificate_url)); ?>">
                        <?php echo __('View Issued Certificates', 'woocertificatespackage'); ?>
                    </a>
                </p>
            </form>
            <?php
                // Get the bulk results from the transient
                $results = get_transient('woocerti_bulk_results');
                if ($results) {
                    // Convert the PHP array to a JSON string to pass to JavaScript
                    $results_json = json_encode($results);
                    echo '<div id="woocerti-bulk-results" data-results=\''.esc_attr($results_json).'\'>';
                    echo '</div>';
                    // Delete the transient so the message doesn't persist on refresh
                    delete_transient('woocerti_bulk_results');
                }
            ?>
        </div>
    <?php endif; ?>

</div>