<?php
    $initial_total = $certificate_product_data['price'] * 1;
?>

<div class="woocerti-buy-certificates-card-wrapper">
    <div class="woocerti-buy-card">
        <div class="woocerti-buy-certificates-header">
            <h2><?php echo __('Purchase of Certificates', 'woocertificatespackage'); ?></h2>
        </div>

        <div class="woocerti-course-details">
            <p class="course-name-detail"><strong><?php echo __('Course Name', 'woocertificatespackage'); ?>:</strong> <span class="large-text"><?php echo esc_html($course->course_name); ?></span></p>
            <p><strong><?php echo __('Duration', 'woocertificatespackage'); ?>:</strong> <?php echo esc_html($course->academic_hours); ?> <?php echo __('hours', 'woocertificatespackage'); ?></p>
        </div>
        <form action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post" id="woocerti-add-to-cart-form" class="woocerti-add-to-cart-form">
            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($certificate_product->get_id()); ?>" />
            <input type="hidden" name="course_id" value="<?php echo esc_attr($course->id_course); ?>" />
            <input type="hidden" id="single_unit_price" value="<?php echo esc_attr($certificate_product_data['price']); ?>" />
            <input type="hidden" name="woocerti_custom_price" value="<?php echo esc_attr($certificate_product_data['price']); ?>" />

            <div class="form-row form-row--two-fields">
                <p class="form-row-field">
                    <label for="quantity" class="large-label"><?php echo __('Quantity', 'woocertificatespackage'); ?>:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" class="large-input" />
                </p>
                <p class="form-row-field total-display-wrapper">
                    <label for="woocerti_total" class="large-label"><?php echo __('Total', 'woocertificatespackage'); ?>:</label>
                    <span id="woocerti_total" class="large-text total-value"><?php echo wc_price($initial_total); ?></span>
                </p>
            </div>

            <p class="p-buttons">
                <button type="submit" class="woocommerce-button button button-primary btn-course woocerti-checkout-button woocerti-ml-0">
                    <?php echo __('Finalize Purchase', 'woocertificatespackage'); ?>
                </button>
            </p>
        </form>
    </div>
</div>