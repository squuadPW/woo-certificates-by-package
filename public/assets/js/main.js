/**
 * JavaScript logic for the public-facing side of the WooCommerce Certificates by Package plugin.
 */
jQuery(document).ready(function($) {
    var form = $('#course-form');
    if (form.length) {
        if ($('#certification_fee_type').val() === 'Percentage') {
            $('#certification_fee_value').attr('max', '100');
        } else {
            $('#certification_fee_value').removeAttr('max');
        }

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

        // Validate on form submission
        form.on('submit', function(e) {
            var clickedButton = $(document.activeElement);
            var fieldsToValidate = [];
            var isFormValid = true;

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
                if (!validateField(fieldsToValidate[i])) {
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
});