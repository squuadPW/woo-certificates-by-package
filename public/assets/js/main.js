/**
 * JavaScript logic for the public-facing side of the WooCommerce Certificates by Package plugin.
 */
jQuery(document).ready(function($) {
    const courseForm = $('#course-form');
    // var form = $('#course-form');
    if (courseForm.length) {
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

    // Handle course deletion confirmation
    $('.woocommerce-MyAccount-courses-table').on('click', '.woocerti-delete-button', function(e) {
        if (!confirm(woocerti_data.deleteConfirmText)) {
            e.preventDefault();
        }
    });

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
    // Custom validation for the "Issue Certificate" form
    //----------------------------------------------------//
    const issueForm = $('#issue-single-student-form');
    if (issueForm.length) {

        // Function to validate a single field
        function validateFieldIssue(field) {
            let isValid = true;
            let errorMessage = '';
            const fieldId = field.attr('id');
            const fieldValue = field.val();
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

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
                case 'single_student_inssued_in':
                    if ($.trim(fieldValue) === '') {
                        isValid = false;
                        errorMessage = woocerti_data.messages.field_is_required;
                    }
                    break;
                case 'single_student_document_type':
                    if ($.trim(fieldValue) === '') {
                        isValid = false;
                        errorMessage = woocerti_data.messages.document_type_required;
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

        // Validate on field change
        issueForm.find('input').on('change', function() {
            validateFieldIssue($(this));
        });

        // Validate on form submission
        issueForm.on('submit', function(e) {
            let formIsValid = true;
            issueForm.find('input').each(function() {
                if (!validateFieldIssue($(this))) {
                    formIsValid = false;
                }
            });

            if (!formIsValid) {
                e.preventDefault();
            }
        });
    }

    //----------------------------------------------------//
    // Drag and Drop for Bulk Upload
    //----------------------------------------------------//
    const fileDropArea = $('#file-drop-area');
    const fileInput = $('#student_list');
    const fileNameDisplay = $('.file-name-display');
    const selectFilesButton = $('.woocerti-select-files-button');

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
                validateFileSelection(fileInput); // Validate when dropping the file
            }
        });

        // Handle click on the custom select files button
        selectFilesButton.on('click', function() {
            fileInput.click();
        });

        // Handle file selection from the dialog
        fileInput.on('change', function() {
            updateFileName(this.files[0]);
            validateFileSelection(fileInput); // Validate when selecting file
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

        // Attach validation to the bulk upload form submission
        const bulkUploadForm = $('.woocerti-bulk-upload form');
        if (bulkUploadForm.length) {
            bulkUploadForm.on('submit', function(e) {
                // Validate the file input specifically
                if (!validateFileSelection(fileInput)) {
                    e.preventDefault();
                }
            });
        }
    }

    //----------------------------------------------------//
    // Lógica para mostrar/ocultar el campo "Issued In?"
    //----------------------------------------------------//
    const documentTypeSelect = $('#single_student_document_type');
    const nationalityContainer = $('#nationality-container');

    if (documentTypeSelect.length && nationalityContainer.length) {
        const toggleNationalityField = () => {
            const selectedValue = documentTypeSelect.val();
            if (selectedValue === 'identification_document') {
                nationalityContainer.show();
            } else {
                nationalityContainer.hide();
            }
        };

        documentTypeSelect.on('change', toggleNationalityField);

        toggleNationalityField();
    }

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

    const phoneInput = document.querySelector("#phone_number");
    if (phoneInput) {
        const iti = window.intlTelInput(phoneInput, {
            nationalMode: true,
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

        phoneInput.addEventListener("blur", function() {
            if (phoneInput.value.trim()) {
                if (iti.isValidNumber()) {
                    console.log("Número de teléfono válido:", iti.getNumber());
                } else {
                    console.log("Número de teléfono inválido");
                }
            }
        });

        // function handleChange(){
        //     $("#number_phone_hidden").val(iti_number_phone.getNumber());
        // }

        // phoneInput.addEventListener('change',handleChange);
        // phoneInput.addEventListener('keyup', handleChange);
    }

    // console.log("WOOCERTI_COUNTRIES: ",WOOCERTI_COUNTRIES);
});