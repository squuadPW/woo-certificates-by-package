/**
 * JavaScript logic for the public-facing side of the WooCommerce Certificates by Package plugin.
 */
jQuery(document).ready(function($) {
    console.log('Woo Certificates public script loaded.');

    var form = $('#course-form');
    if (form.length) {
        // Function to validate a single field and show/hide error messages
        function validateField(field) {
            var isValid = true;
            var errorMessage = '';
            var fieldId = field.attr('id');
            var fieldValue = field.val();
            var fieldType = field.attr('type');

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
                field.addClass('input-error');
                field.closest('.form-row-field').find('.validation-message').text(errorMessage).show();
            }
            return isValid;
        }

        // Validate fields on change event
        form.find('input, select').on('change', function() {
            validateField($(this));
        });

        // Validate all fields on form submission
        form.on('submit', function(e) {
            var isFormValid = true;
            form.find('input[required], select[required]').each(function() {
                if (!validateField($(this))) {
                    isFormValid = false;
                }
            });

            if (!isFormValid) {
                e.preventDefault();
            }
        });

        console.log("woocerti_data: ",woocerti_data);

        // Toggle tutor name based on checkbox state
        $('#is_my_name').on('change', function() {
            var tutorInput = $('#tutor_instructor');
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
            $('#certification_fee_value').val('');
            $('#certification_fee_value').removeClass('input-error');
            $('#certification_fee_value').closest('.form-row-field').find('.validation-message').text('').hide();
        });
    }
});