/**
 * JavaScript logic for the public-facing side of the WooCommerce Certificates by Package plugin.
 */
jQuery(document).ready(function($) {
    const currentUrl = window.location.href;
    if (currentUrl.includes('issue-certificate')) {
        const $certificatesMenuItem = $('.woocommerce-MyAccount-navigation-link--certificates');
        $('.woocommerce-MyAccount-navigation-link').removeClass('is-active');
        $certificatesMenuItem.addClass('is-active');
    }

    const courseForm = $('#course-form');
    if (courseForm.length) {
        const $referenceInput = $('#course_name');

        if ($referenceInput.length) {
            const referenceStyles = window.getComputedStyle($referenceInput[0]);

            const $dateInput = $('#course_date');
            const $selectField = $('#certification_fee_type');

            const propertiesToCopy = [
                'padding', 'border', 'border-radius', 'background-color', 'color', 'font-size', 'line-height'
            ];

            if ($dateInput.length) {
                propertiesToCopy.forEach(prop => {
                    $dateInput[0].style.setProperty(prop, referenceStyles.getPropertyValue(prop), 'important');
                });
            }

            if ($selectField.length) {
                propertiesToCopy.forEach(prop => {
                    $selectField[0].style.setProperty(prop, referenceStyles.getPropertyValue(prop), 'important');
                });
            }

        }

        if ($('#certification_fee_type').val() === 'Percentage') {
            $('#certification_fee_value').attr('max', '100');
        } else {
            $('#certification_fee_value').removeAttr('max');
        }

        // Function to validate a single field and show/hide error messages
        function validateFieldCourse(field) {
            let isValid = true;
            let errorMessage = '';
            const fieldId = field.attr('id');
            const fieldValue = field.val();

            // Reset error state
            field.removeClass('input-error');
            field.closest('.form-row-field').find('.validation-message').text('').hide();

            // Validation logic based on field ID
            switch (fieldId) {
                case 'course_name':
                    if ($.trim(fieldValue) === '') {
                        isValid = false;
                        errorMessage = woocerti_data.messages.course_name_required;
                    } else if (fieldValue.length > 300) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.course_name_maxlength;
                    }
                    break;
                case 'academic_hours':
                    const academicHours = parseFloat(fieldValue);
                    if (isNaN(academicHours) || academicHours <= 0) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.academic_hours_invalid;
                    }
                    break;
                case 'course_date':
                    if (fieldValue !== '') {
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        const courseDate = new Date(fieldValue);
                        if (courseDate < today) {
                            isValid = false;
                            errorMessage = woocerti_data.messages.course_date_past;
                        }
                    }
                    break;
                case 'certification_fee_type':
                    if (fieldValue !== 'Fixed' && fieldValue !== 'Percentage') {
                        isValid = false;
                        errorMessage = woocerti_data.messages.certification_fee_type_invalid;
                    }
                    break;
                case 'price_per_student':
                    const pricePerStudent = parseFloat(fieldValue);
                    if (isNaN(pricePerStudent) || pricePerStudent <= 0) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.price_per_student_invalid;
                    }
                    break;
                case 'certification_fee_value':
                    const certFeeValue = parseFloat(fieldValue);
                    if (isNaN(certFeeValue) || certFeeValue <= 0) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.certification_fee_value_invalid;
                    }
                    break;
            }

            // Show error message if validation failed
            if (!isValid) {
                field.addClass('input-error');
                field.closest('.form-row-field').find('.validation-message').text(errorMessage).show();
            }
            return isValid;
        }

        // Validate fields on change event
        courseForm.find('input, select').on('change', function() {
            validateFieldCourse($(this));
        });

        // Validate on form submission
        courseForm.on('submit', function(e) {
            let clickedButton = $(document.activeElement);
            let fieldsToValidate = [];
            let isFormValid = true;

            // Determine which fields to validate based on the clicked button
            if (clickedButton.attr('name') === 'save_draft') {
                // For drafts, only validate the course name
                fieldsToValidate.push($('#course_name'));
            } else {
                // For a full save, validate all required fields
                fieldsToValidate.push($('#course_name'), $('#academic_hours'), $('#price_per_student'), $('#certification_fee_type'), $('#certification_fee_value'));
            }

            // Perform validation on the selected fields
            for (var i = 0; i < fieldsToValidate.length; i++) {
                if (!validateFieldCourse(fieldsToValidate[i])) {
                    isFormValid = false;
                }
            }

            if (isFormValid && clickedButton.attr('name') !== 'save_draft') {
                let type_fee = $('#certification_fee_type').val();
                let value_fee = parseFloat($('#certification_fee_value').val());
                if (type_fee === 'Percentage' && value_fee > 100) {
                    isFormValid = false;
                    $('#certification_fee_value').addClass('input-error');
                    $('#certification_fee_value').closest('.form-row-field').find('.validation-message').text(woocerti_data.messages.is_percentage_rate_valid).show();
                }
            }

            if (!isFormValid) {
                e.preventDefault();
            }
        });

        // Toggle tutor name based on checkbox state
        $('#is_my_name').on('change', function() {
            let tutorInput = $('#tutor_instructor');
            if ($(this).is(':checked')) {
                // Check if user data is available
                if (typeof woocerti_data !== 'undefined' && woocerti_data.hasOwnProperty('user_name')) {
                    tutorInput.val(woocerti_data.user_name);
                    tutorInput.prop('readonly', true);
                }
            } else {
                tutorInput.val('');
                tutorInput.prop('readonly', false);
            }
        });

        // Reset the certification fee value when the fee type changes
        $('#certification_fee_type').on('change', function() {
            let elem = $(this);
            $('#certification_fee_value').val(0);
            $('#certification_fee_value').removeClass('input-error');
            $('#certification_fee_value').closest('.form-row-field').find('.validation-message').text('').hide();

            if (elem.val() === 'Percentage') {
                $('#certification_fee_value').attr('max', '100');
            } else {
                $('#certification_fee_value').removeAttr('max');
            }
        });
    }

    // Add CSS class for fields on focus
    $(document).on('focusin', '.input-field', function() {
        $(this).closest('.form-row-field').addClass('focus');
    }).on('focusout', '.input-field', function() {
        $(this).closest('.form-row-field').removeClass('focus');
    });

    let courseToDeleteData = {};
    const deleteModal = $('#modal-woorceti-confirm-delete-course');
    const confirmDeleteBtn = $('#woorceti-confirm-delete-course');
    const redirectUrl = deleteModal.find('.content-footer a').data('redirect-url');

    $('.woocerti-delete-ajax-trigger').on('click', function(e) {
        e.preventDefault();
        courseToDeleteData.id = $(this).data('course-id');
        courseToDeleteData.nonce = $(this).data('nonce');
        courseToDeleteData.redirectUrl = $(this).data('redirect-url');
        confirmDeleteBtn.attr('href', '#'); // Evitar navegación predeterminada
        deleteModal.show();
    });

    confirmDeleteBtn.on('click', function(e) {
        e.preventDefault();

        if (!courseToDeleteData.id || !courseToDeleteData.nonce) {
            alert('Error: Missing course data for deletion.');
            return;
        }

        deleteModal.hide();

        // Llamada AJAX al nuevo endpoint
        $.ajax({
            type: 'POST',
            url: woocerti_data.ajax_url,
            data: {
                action: 'woocerti_delete_course_ajax',
                course_id: courseToDeleteData.id,
                nonce: courseToDeleteData.nonce
            },
            success: function(response) {
                if (response.success && response.data.redirect_url) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert(response.data.message || 'Error deleting the course.');
                    window.location.href = courseToDeleteData.redirectUrl;
                }
            },
            error: function(xhr, status, error) {
                alert('An unknown server error occurred.');
                window.location.href = courseToDeleteData.redirectUrl;
            }
        });
    });

    $('.modal-close-woorceti, #btn-cancel-woorceti-modal').on('click', function() {
        deleteModal.hide();
    });

    // Global variable to store the course deletion URL
    // let deleteCourseUrl = '';

    // $('.woocommerce-MyAccount-courses-table').on('click', '.woocerti-delete-button', function(e) {
    //     e.preventDefault();
    //     deleteCourseUrl = $(this).attr('href');
    //     $('#woorceti-confirm-delete-course').attr('href', deleteCourseUrl);
    //     $('#modal-woorceti-confirm-delete-course').show();
    // });

    // $('body').on('click', '#btn-cancel-woorceti-modal', function(e) {
    //     e.preventDefault();
    //     $('#modal-woorceti-confirm-delete-course').hide();
    //     deleteCourseUrl = '';
    //     $('#woorceti-confirm-delete-course').attr('href', '#');
    // });

    // $('body').on('click', '.modal-close-woorceti', function(e) {
    //     e.preventDefault();
    //     $('#modal-woorceti-confirm-delete-course').hide();
    //     deleteCourseUrl = '';
    //     $('#woorceti-confirm-delete-course').attr('href', '#');
    // });

    // Handles the button click to show/hide the dropdown
    $('.woocerti-dropdown .woocerti-issue-button').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.woocerti-dropdown').find('.woocerti-dropdown-content').toggle();
    });

    // Closes the dropdown if the user clicks outside of it
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.woocerti-dropdown').length) {
            $('.woocerti-dropdown-content').hide();
        }
    });

    //----------------------------------------------------//
    // intlTelInput Initialization
    //----------------------------------------------------//
    if (typeof woocerti_data !== 'undefined' && woocerti_data.nationalities) {
        const nationalityDropdown = $("#single_student_inssued_in");
        if (nationalityDropdown) {
            const nationalities = woocerti_data.nationalities;
            if (nationalityDropdown.length) {
                const nationalitiesArray = Object.entries(nationalities);
                nationalitiesArray.sort((a, b) => {
                    const nameA = a[1].toUpperCase();
                    const nameB = b[1].toUpperCase();
                    if (nameA < nameB) {
                        return -1;
                    }
                    if (nameA > nameB) {
                        return 1;
                    }
                    return 0;
                });

                nationalitiesArray.forEach(([code, name]) => {
                    nationalityDropdown.append('<option value="'+code.toUpperCase()+'">'+name+' - '+code.toUpperCase()+'</option>');
                });
            }
        }
    }

    //----------------------------------------------------//
    // Custom validation for the "Issue Certificate" form
    //----------------------------------------------------//
    const issueForm = $('#issue-single-student-form');
    const responseMessageContainer = $('#woocerti-response-message');
    const submitButton = issueForm.find('.woocerti-issue-submit');
    const documentTypeSelect = $('#single_student_document_type');
    const nationalityContainer = $('#nationality-container');
    const formRowFields = $('.form-row--three-fields .form-row-field');
    const phoneInput = document.querySelector("#phone_number");
    let iti = null;

    /**
     * Función para mostrar mensajes de WooCommerce (éxito/error/info) vía AJAX.
     * @param {Array} messages - Un array de strings con los mensajes a mostrar.
     * @param {string} type - 'success', 'error', o 'notice'.
     */
    function showMessageIsuue(messages, type) {
        const $container = $('#woocerti-response-message');
        $container.empty()
            .removeClass('woocommerce-message woocommerce-error woocommerce-info')
            .hide();

        if (!messages || messages.length === 0) {
            return;
        }

        let htmlContent = '';

        if (type === 'success') {
            $container.addClass('woocommerce-message');
            htmlContent = `<p>${messages.join('</p><p>')}</p>`;
        } else if (type === 'error') {
            $container.addClass('woocommerce-error');
            htmlContent = `<ul class="woocommerce-error-list">
                            <li>${messages.join('</li><li>')}</li>
                        </ul>`;
        } else if (type === 'notice') {
            $container.addClass('woocommerce-info');
            htmlContent = `<p>${messages.join('</p><p>')}</p>`;
        }

        $container.html(htmlContent).slideDown(300);

        $('html, body').animate({
            scrollTop: $container.offset().top - 100
        }, 500);
    }

    if (issueForm.length) {
        const $referenceInput = $('#single_student_first_name');

        if ($referenceInput.length) {
            const referenceStyles = window.getComputedStyle($referenceInput[0]);

            const $documentTypeInput = $('#single_student_document_type');
            const $inssuedInField = $('#single_student_inssued_in');

            const propertiesToCopy = [
                'padding', 'border', 'border-radius', 'background-color', 'color', 'font-size', 'line-height'
            ];

            if ($documentTypeInput.length) {
                propertiesToCopy.forEach(prop => {
                    $documentTypeInput[0].style.setProperty(prop, referenceStyles.getPropertyValue(prop), 'important');
                });
            }

            if ($inssuedInField.length) {
                propertiesToCopy.forEach(prop => {
                    $inssuedInField[0].style.setProperty(prop, referenceStyles.getPropertyValue(prop), 'important');
                });
            }

        }
        // Initialize intl-tel-input for the phone number field
        if (phoneInput && typeof woocerti_data.iti_phone !== 'undefined') {
            iti = window.intlTelInput(phoneInput, {
                initialCountry: "auto",
                separateDialCode: true,
                strictMode: true,
                loadUtils: () => import(woocerti_data.woocerti_plugin_url + 'public/assets/js/libs/utils.js'),
                hiddenInput: (telInputName) => ({
                    phone: "phone_full",
                    country: "country_code"
                }),
                geoIpLookup: callback => {
                    fetch("https://ipapi.co/json")
                        .then(res => res.json())
                        .then(data => callback(data.country_code))
                        .catch(() => callback("us"));
                },
                allowPhonewords: true,
                i18n: {
                    searchPlaceholder: woocerti_data.iti_phone.search_placeholder,
                    noCountrySelected: woocerti_data.iti_phone.no_country_selected,
                    countryListAriaLabel: woocerti_data.iti_phone.country_list_aria_label,
                    clearSearchAriaLabel: woocerti_data.iti_phone.clear_search_aria_label,
                    zeroSearchResults: woocerti_data.iti_phone.zero_search_results,
                }
            });
        }

        // Function for print Error return submit
        function printError(field, message) {
            field.addClass('input-error');
            field.closest('.form-row-field').find('.validation-message').text(message).show();
        }

        // Function to validate a single field
        function validateFieldIssue(field) {
            let isValid = true;
            let errorMessage = '';
            const fieldId = field.attr('id');
            const fieldValue = field.val();
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            const documentType = $('#single_student_document_type').val();

            // Reset error state
            field.removeClass('input-error');
            field.closest('.form-row-field').find('.validation-message').text('').hide();

            // Validation logic based on field ID
            switch (fieldId) {
                case 'single_student_first_name':
                case 'single_student_last_name':
                case 'single_student_document_number':
                    if ($.trim(fieldValue) === '') {
                        isValid = false;
                        errorMessage = woocerti_data.messages.field_is_required;
                    }
                    break;
                case 'single_student_document_type':
                    const allowedTypes = ['passport', 'identification_document', 'ssn'];
                    if ($.trim(fieldValue) === '' || !allowedTypes.includes(fieldValue)) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.document_type_required;
                    }
                    break;
                case 'single_student_inssued_in':
                    if (documentType === 'identification_document') {
                        let trimmedValue = (typeof fieldValue === 'string') ? $.trim(fieldValue) : '';
                        if (trimmedValue === '' || (typeof woocerti_data.nationalities !== 'object' || !woocerti_data.nationalities.hasOwnProperty(trimmedValue.toUpperCase()))) {
                            isValid = false;
                            errorMessage = woocerti_data.messages.field_is_required;
                        }
                    }
                    break;
                case 'single_student_email':
                    if ($.trim(fieldValue) === '') {
                        isValid = false;
                        errorMessage = woocerti_data.messages.field_is_required;
                    } else if (!emailRegex.test(fieldValue)) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.email_invalid;
                    }
                    break;
                case 'phone_number':
                    if ($.trim(fieldValue) !== '' && iti && !iti.isValidNumber()) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.phone_number_invalid;
                    }
                    break;
            }

            // Show or hide the error message
            if (!isValid) {
                field.addClass('input-error');
                field.closest('.form-row-field').find('.validation-message').text(errorMessage).show();
            } else {
                field.removeClass('input-error');
                field.closest('.form-row-field').find('.validation-message').text('').hide();
            }

            return isValid;
        }

        if (documentTypeSelect.length && nationalityContainer.length) {
            const toggleNationalityField = () => {
                const selectedValue = documentTypeSelect.val();
                // Reset the 'issued in' field
                $('#single_student_inssued_in').val('');
                if (selectedValue === 'identification_document') {
                    // Show the field if 'identification_document' is selected
                    nationalityContainer.show();
                } else {
                    // Hide the field for other values
                    nationalityContainer.hide();
                }

                // Update the CSS to manage the last-child margin
                updateFieldClasses(selectedValue === 'identification_document');
            };

            documentTypeSelect.on('change', toggleNationalityField);

            // Function to update CSS classes dynamically
            function updateFieldClasses(isThreeFieldsVisible) {
                formRowFields.removeClass('no-margin-right');
                if (!isThreeFieldsVisible) {
                    // If only two fields are visible, add a class to the second one
                    if (formRowFields.length > 1) {
                        formRowFields.eq(1).addClass('no-margin-right');
                    }
                }
            }

            toggleNationalityField();
        }

        // Validate on field change
        issueForm.find('input, select').on('change', function() {
            validateFieldIssue($(this));
        });

        // Validate on form submission
        issueForm.on('submit', function(e) {
            e.preventDefault();
            let formIsValid = true;

            // Set the value of the 'issued in' field before validation
            const documentType = $('#single_student_document_type').val();
            if (documentType !== 'identification_document') {
                $('#single_student_inssued_in').val('US').attr('required', false);
            } else {
                $('#single_student_inssued_in').attr('required', true);
            }

            // Validate all required fields
            const requiredFields = [
                'single_student_document_type',
                'single_student_document_number',
                'single_student_first_name',
                'single_student_last_name',
                'single_student_email',
                'phone_number'
            ];

            requiredFields.forEach(function(fieldId) {
                const field = $('#' + fieldId);
                if (!validateFieldIssue(field)) {
                    formIsValid = false;
                }
            });

            if (!formIsValid) {
                // If the validation on the client fails, the request is not sent.
                return;
            }

            // Displays the charging status and disables the button
            submitButton.text(woocerti_data.text_forms.txt_btn_issuing).prop('disabled', true);
            responseMessageContainer.fadeOut();

            const formData = issueForm.serialize();
            const ajaxData = formData + '&action=woocerti_issue_single_certificate';

            $.post(woocerti_data.ajax_url, ajaxData, function(resp) {
                submitButton.prop('disabled', false).text(woocerti_data.text_forms.btn_submit_issue_certificate);

                if (resp.success) {
                    issueForm.trigger('reset');
                    showMessageIsuue([resp.data.message], 'success');
                } else {
                    if (resp.data && resp.data.messages) {
                        showMessageIsuue([resp.data.messages], 'error');
                    }
                    if (resp.data && resp.data.validations) {
                        Object.entries(resp.data.validations).forEach(([key, value]) => {
                            const field = $('#' + key);
                            printError(field, value);
                        });
                    }
                }
            }).fail(function() {
                submitButton.prop('disabled', false).text(woocerti_data.text_forms.btn_submit_issue_certificate);
                showMessageIsuue([woocerti_data.messages.ajax_error], 'error');
            });
        });
    }

    //----------------------------------------------------//
    // Lógica para el formulario de carga masiva
    //----------------------------------------------------//
    const bulkUploadForm = $('#woocerti-bulk-upload-form');
    const fileDropArea = $('#file-drop-area');
    const fileInput = $('#student_list');
    const fileNameDisplay = $('.file-name-display');
    const selectFilesButton = $('.woocerti-select-files-button');

    if (bulkUploadForm.length) {
        if (fileDropArea.length && fileInput.length) {
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileDropArea.on(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            // Highlight drop area when file is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                fileDropArea.on(eventName, function() {
                    fileDropArea.addClass('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileDropArea.on(eventName, function() {
                    fileDropArea.removeClass('dragover');
                });
            });

            // Handle dropped files
            fileDropArea.on('drop', function(e) {
                const droppedFiles = e.originalEvent.dataTransfer.files;
                if (droppedFiles.length > 0) {
                    fileInput.prop('files', droppedFiles);
                    updateFileName(droppedFiles[0]);
                    validateFileSelection(fileInput);
                }
            });

            // Handle click on the custom select files button
            selectFilesButton.on('click', function() {
                fileInput.click();
            });

            // Handle file selection from the dialog
            fileInput.on('change', function() {
                updateFileName(this.files[0]);
                validateFileSelection(fileInput);
            });

            // Helper function to update the file name display
            function updateFileName(file) {
                if (file) {
                    fileNameDisplay.text(woocerti_data.messages.selected_file + file.name);
                } else {
                    fileNameDisplay.text('');
                }
            }

            // Function to validate file selection for bulk upload
            function validateFileSelection(fileField) {
                let isValid = true;
                let errorMessage = '';
                const maxFileSize = 5 * 1024 * 1024; // 5 MB in bytes

                if (!fileField[0].files || fileField[0].files.length === 0) {
                    isValid = false;
                    errorMessage = woocerti_data.messages.file_is_empty;
                } else {
                    const file = fileField[0].files[0];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    const fileMimeType = file.type;
                    // Define the allowed extensions and MIME types.
                    const allowedExtensions = ['csv', 'xls', 'xlsx'];
                    const allowedMimeTypes = [
                        'text/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ];

                    if (file.size > maxFileSize) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.file_size_exceeded;
                    }
                    if (isValid && (!allowedExtensions.includes(fileExtension) || !allowedMimeTypes.includes(fileMimeType))) {
                        isValid = false;
                        errorMessage = woocerti_data.messages.file_type_invalid;
                    }
                }

                const validationMessageSpan = fileField.nextAll('.validation-message:first');
                if (!isValid) {
                    fileField.addClass('input-error');
                    fileDropArea.addClass('input-error');
                    validationMessageSpan.text(errorMessage).show();
                } else {
                    fileField.removeClass('input-error');
                    fileDropArea.removeClass('input-error');
                    validationMessageSpan.text('').hide();
                }
                return isValid;
            }

            bulkUploadForm.on('submit', function(e) {
                // Validate the file input specifically
                if (!validateFileSelection(fileInput)) {
                    e.preventDefault();
                }
            });
        }
    }

    // Check for bulk upload results
    const bulkResultsContainer = $('#woocerti-bulk-results');
    if (bulkResultsContainer.length) {
        try {
            const results = JSON.parse(bulkResultsContainer.attr('data-results'));
            if (results.general_messages && results.general_messages.length > 0) {
                const messageType = results.success ? 'success' : 'error';
                showMessageIsuue([results.general_messages], messageType);
            }

            if (results.detailed_results && results.detailed_results.length > 0) {
                // Check if the HTML for the results table exists
                let resultsTableContainer = $('#woocerti-issued-certificates-table');
                if (resultsTableContainer.length === 0) {
                    // If the table doesn't exist, create it dynamically
                    const tableHtml = `
                        <table id="woocerti-issued-certificates-table" class="woocommerce-MyAccount-issued-certificates-table shop_table_responsive my_account_orders">
                            <thead>
                                <tr>
                                    <th class="woocommerce-MyAccount-issued-certificates-table__header--document-number"><span>${woocerti_data.text_results.document_number}</span></th>
                                    <th class="woocommerce-MyAccount-issued-certificates-table__header--name"><span>${woocerti_data.text_results.first_name}</span></th>
                                    <th class="woocommerce-MyAccount-issued-certificates-table__header--last-name"><span>${woocerti_data.text_results.last_name}</span></th>
                                    <th class="woocommerce-MyAccount-issued-certificates-table__header--email"><span>${woocerti_data.text_results.email}</span></th>
                                    <th class="woocommerce-MyAccount-issued-certificates-table__header--status"><span>${woocerti_data.text_results.status}</span></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    `;
                    bulkResultsContainer.append(tableHtml);
                    resultsTableContainer = $('#woocerti-issued-certificates-table');
                }

                const tableBody = resultsTableContainer.find('tbody');
                tableBody.empty(); // Clear previous results

                // Populate the table with the detailed results
                results.detailed_results.forEach(result => {
                    const isFailed = result.status === 'failed';
                    const statusClass = result.status === 'issued' ? 'status-issued' : 'status-failed';
                    const statusText = result.status === 'issued' ? woocerti_data.text_results.issued : woocerti_data.text_results.failed;
                    const row = `
                        <tr>
                            <td>${result.data.document_number}</td>
                            <td>${result.data.first_name}</td>
                            <td>${result.data.last_name}</td>
                            <td>${result.data.email}</td>
                            <td>
                                <span class="${statusClass}">
                                    ${statusText}
                                    ${isFailed ? '<i class="fa fa-info-circle woocerti-toggle-details"></i>' : ''}
                                </span>
                            </td>
                        </tr>
                        ${isFailed ? `
                        <tr class="woocerti-error-details" style="display: none;">
                            <td colspan="5">
                                <div class="error-detail-content">
                                    <strong${woocerti_data.text_results.error_details}</strong>
                                    <ul>
                                        ${result.errors.map(error => `<li>${error}</li>`).join('')}
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        ` : ''}
                    `;
                    tableBody.append(row);
                });
                // Add the listener for the click
                tableBody.on('click', '.woocerti-toggle-details', function() {
                    // Select the parent of the row and then the next row
                    const errorRow = $(this).closest('tr').next('.woocerti-error-details');
                    errorRow.toggle();
                });
            }
        } catch (error) {
            showMessageIsuue([woocerti_data.messages.bulk_results_error], 'error');
        }
    }

    const $buyForm = $('#woocerti-add-to-cart-form');
    if ($buyForm.length) {
        const $quantityInput = $('#quantity');
        const $totalDisplay = $('#woocerti_total');
        const unitPrice = parseFloat($('#single_unit_price').val());
        const checkoutUrl = woocerti_data.checkout_url;
        console.log("checkoutUrl: ",checkoutUrl);

        // Function to update the total
        const updatePriceTotal = () => {
            const quantity = parseInt($quantityInput.val()) || 1;
            // Ensure quantity is positive
            if (quantity < 1) {
                $quantityInput.val(1);
            }
            const total = unitPrice * quantity;
            let formattedTotal = total.toFixed(2).replace('.', ',');
            $totalDisplay.text(`$${formattedTotal}`);

            if (woocerti_data.currency_symbol) {
                formattedTotal = total.toLocaleString('es-ES', {
                    style: 'currency',
                    currency: woocerti_data.currency_code
                });
                $totalDisplay.text(formattedTotal);
            } else {
                $totalDisplay.text(total.toFixed(2));
            }
        };

        // Event listener for quantity change
        $quantityInput.on('change keyup', updatePriceTotal);

        // Intercept form submission to redirect to checkout
        $buyForm.on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = $form.serialize();

            $.ajax({
                type: 'POST',
                url: woocerti_data.ajax_url,
                data: formData + '&action=woocerti_add_to_cart_checkout',
                success: function(response) {
                    if (response.success) {
                        window.location.href = checkoutUrl;
                    } else {
                        console.error("AJAX Error Response: ", response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        });

        // Ensure initial total is displayed correctly on page load
        updatePriceTotal();
    }

    /**
     * Simulates the behavior of PHP sprintf() to replace positional placeholders (%1$d, %2$s).
     */
    function sprintf(format, ...args) {
        return format.replace(/%(\d+\$)?(d|s|x)/g, function (match, index, type) {
            if (index) {
                const position = parseInt(index.substring(0, index.length - 1)) - 1;
                return typeof args[position] != 'undefined' ? args[position] : match;
            } else {
                return match;
            }
        });
    }

    const $customUploadBtn = $('#woocerti-custom-upload-btn');
    const $fileInput = $('#woocerti_user_logo');
    const $fileNameDisplay = $('#woocerti-selected-file-name');
    const $uploadText = $('#woocerti-upload-text');
    const $saveLogoBtn = $('#woocerti_logo_submit');
    const $errorDisplay = $('#woocerti-logo-error');
    const $previewContainer = $('#woocerti-logo-preview-container');
    const $previewImg = $('#woocerti-logo-preview');
    const $previewPlaceholder = $('#woocerti-preview-placeholder');

    if ($fileInput.length) {
        $customUploadBtn.on('click', function(e) {
            e.preventDefault();
            $fileInput.trigger('click');
        });

        $fileInput.on('change', function() {
            const files = this.files;
            const requiredDimension = 512;
            const allowedMimeType = 'image/png';
            $errorDisplay.text('');

            $fileNameDisplay.text('');
            $uploadText.text(woocerti_data.messages.choose_png);
            $saveLogoBtn.prop('disabled', true);
            $previewImg.attr('src', '#').hide();
            $previewPlaceholder.show();
            $previewContainer.hide();

            if (files.length === 0) {
                return;
            }

            const file = files[0];

            if (file.type !== allowedMimeType) {
                $errorDisplay.text(woocerti_data.messages.invalid_type_png);
                $(this).val('');
                return;
            }

            $previewContainer.show();

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                $previewImg.attr('src', e.target.result).show();
                $previewPlaceholder.hide();
                img.onload = function() {
                    const width = this.width;
                    const height = this.height;

                    // if (width !== requiredDimension || height !== requiredDimension) {
                    //     const errorMessage = sprintf(
                    //         woocerti_data.messages.dimension_error_format,
                    //         requiredDimension,
                    //         requiredDimension,
                    //         width,
                    //         height
                    //     );
                    //     $errorDisplay.text(errorMessage);
                    //     $fileInput.val('');
                    //     $saveLogoBtn.prop('disabled', true);
                    //     $fileNameDisplay.text('');
                    //     $uploadText.text(woocerti_data.messages.choose_png);
                    // } else {
                        $errorDisplay.text('');
                        $fileNameDisplay.text(file.name);
                        $uploadText.text(woocerti_data.messages.file_selected);
                        $saveLogoBtn.prop('disabled', false);
                    // }
                };
                img.src = e.target.result;
            };

            reader.readAsDataURL(file);
        });

    }

});