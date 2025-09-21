<div class="woocerti-buy-certificates-header">
    <h2><?php echo __('Purchase of Certificates', 'woocertificatespackage'); ?></h2>
</div>

<div class="woocerti-course-details">
    <p><strong><?php echo __('Course Name', 'woocertificatespackage'); ?>:</strong> <?php echo esc_html($course->course_name); ?></p>
    <p><strong><?php echo __('Duration', 'woocertificatespackage'); ?>:</strong> <?php echo esc_html($course->academic_hours); ?> <?php echo __('hours', 'woocertificatespackage'); ?></p>
    <p><strong><?php echo __('Price', 'woocertificatespackage'); ?>:</strong> <?php echo wc_price($certificate_product_data['price']); ?></p>
</div>

<form action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post" class="woocerti-add-to-cart-form">
    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($certificate_product->get_id()); ?>" />
    <input type="hidden" name="course_id" value="<?php echo esc_attr($course->id_course); ?>" />
    <input type="hidden" name="woocerti_custom_price" value="<?php echo esc_attr($certificate_product_data['price']); ?>" />

    <div class="form-row form-row--three-fields">
        <p class="form-row-field quantity-input-wrapper">
            <label for="quantity"><?php echo __('Quantity', 'woocertificatespackage'); ?>:</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1" />
        </p>
    </div>

    <p class="p-buttons">
        <button type="submit" class="woocommerce-button button button-primary btn-course"><?php echo __('Add to Cart', 'woocertificatespackage'); ?></button>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('courses')); ?>" class="woocommerce-button button btn-course woocerti-back-button"><?php echo __('Back to Courses', 'woocertificatespackage'); ?></a>
    </p>
</form>

