/**
 * JavaScript logic for the admin-facing side of the WooCommerce Certificates by Package plugin.
 */
jQuery(document).ready(function($) {
    const form = $('#course-form');
    const assignedUserSelect = $('#id_user');
    const isTutorCheckbox = $('#is_tutor_checkbox');
    const tutorInstructorInput = $('#tutor_instructor');

    /**
     * Manages the dynamic behavior of the 'Tutor or instructor' field when interacting with the user.
     */
    function handleDynamicTutorChanges() {
        if (isTutorCheckbox.is(':checked')) {
            const selectedUserText = assignedUserSelect.find('option:selected').text().trim();
            const userName = selectedUserText.split(' - ')[0];
            tutorInstructorInput.val(userName).prop('readonly', true).css('background-color', '#f0f0f0');
        } else {
            // If the checkbox is unchecked, we do not reset the value, we just make it editable.
            tutorInstructorInput.prop('readonly', false).css('background-color', '#ffffff');
        }
    }

    /**
     * Validates the initial state of the form when the page loads (edit mode).
     */
    function setInitialFormState() {
        // We get the name of the assigned user from the select field.
        const selectedUserText = assignedUserSelect.find('option:selected').text().trim();
        const selectedUserName = selectedUserText.split(' - ')[0];

        // We get the current value of the tutor field.
        const tutorName = tutorInstructorInput.val().trim();

        // We compare the values.
        if (selectedUserName === tutorName && selectedUserName !== '') {
            // If they match, we mark the checkbox and make the tutor field read-only.
            isTutorCheckbox.prop('checked', true);
            tutorInstructorInput.prop('readonly', true).css('background-color', '#f0f0f0');
        } else {
            // If they do not match or the user field is empty, the tutor field must be editable.
            tutorInstructorInput.prop('readonly', false).css('background-color', '#ffffff');
        }
    }

    // Function to validate a single field and show/hide error messages
    function validateField(field) {
        let isValid = true;
        let errorMessage = '';
        let fieldId = field.attr('id');
        let fieldValue = field.val();

        // Reset error state
        field.removeClass('error-field');
        field.closest('.form-group-item').find('.error-message').text('').hide();

        // Validation logic based on field ID
        switch (fieldId) {
            case 'id_user':
                if (fieldValue === '') {
                    isValid = false;
                    errorMessage = woocerti_data.messages.assigned_user_required;
                }
                break
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
                var academicHours = parseFloat(fieldValue);
                if (isNaN(academicHours) || academicHours <= 0) {
                    isValid = false;
                    errorMessage = woocerti_data.messages.academic_hours_invalid;
                }
                break;
            case 'course_date':
                if (fieldValue !== '') {
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    var courseDate = new Date(fieldValue);
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
            case 'status':
                if (fieldValue !== 'Pending' && fieldValue !== 'Approved' && fieldValue !== 'Rejected') {
                    isValid = false;
                    errorMessage = woocerti_data.messages.status_invalid;
                }
                break;
            case 'price_per_student':
                var pricePerStudent = parseFloat(fieldValue);
                if (isNaN(pricePerStudent) || pricePerStudent <= 0) {
                    isValid = false;
                    errorMessage = woocerti_data.messages.price_per_student_invalid;
                }
                break;
            case 'certification_fee_value':
                var certFeeValue = parseFloat(fieldValue);
                if (isNaN(certFeeValue) || certFeeValue <= 0) {
                    isValid = false;
                    errorMessage = woocerti_data.messages.certification_fee_value_invalid;
                }
                break;
        }

        // Show error message if validation failed
        if (!isValid) {
            field.addClass('error-field');
            field.closest('.form-group-item').find('.error-message').text(errorMessage).show();
        }
        return isValid;
    }

    /**
     * Main function that validates the entire form upon submission.
     * @returns {boolean} - true if the form is valid, false otherwise.
     */
    function validateFormOnSubmit() {
        let isFormValid = true;
        // Validation for 'Assigned User (Alliance)'
        isFormValid &= validateField(assignedUserSelect);
        // Validation for 'Course Name'
        isFormValid &= validateField($('#course_name'));
        // Validation for 'Academic Hours'
        isFormValid &= validateField($('#academic_hours'));
        // Validation for 'Price per student'
        isFormValid &= validateField($('#price_per_student'));
        // Validation for 'Certification Fee Value'
        isFormValid &= validateField($('#certification_fee_value'));
        // Validation for 'Certification Fee Type'
        isFormValid &= validateField($('#certification_fee_type'));
        // Validation for 'Status'
        isFormValid &= validateField($('#status'));

        return isFormValid;
    }

    // Validate the form upon submission.
    form.on('submit', function(event) {
        if (!validateFormOnSubmit()) {
            event.preventDefault();
        }
    });

    // Listen for changes in the rate type to reset the value.
    $('#certification_fee_type').on('change', function () {
        validateField($(this));
        $('#certification_fee_value').val(0);
        $('#certification_fee_value').removeClass('error-field');
        $('#certification_fee_value').closest('.form-group-item').find('.error-message').text('').hide();
    });

    // Listen to change events for dynamic tutor functionality.
    isTutorCheckbox.on('change', handleDynamicTutorChanges);
    assignedUserSelect.on('change', handleDynamicTutorChanges);

    // Field validation when the value changes.
    $('#id_user').on('change', function () {
        validateField($(this));
    });
    $('#course_name').on('input', function () {
        validateField($(this));
    });
    $('#academic_hours').on('input', function () {
        validateField($(this));
    });
    $('#price_per_student').on('input', function () {
        validateField($(this));
    });
    $('#certification_fee_value').on('input', function () {
        validateField($(this));
    });
    $('#status').on('change', function () {
        validateField($(this));
    })

    // Execute the initial state function on page load.
    setInitialFormState();

    // Confirmation to delete the course
    $('body').on('click', '.delete-course-link', function(e) {
        if (!confirm(woocerti_data.deleteConfirmText)) {
            e.preventDefault();
        }
    });

});