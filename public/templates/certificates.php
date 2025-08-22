<div class="woocommerce-certificates-page">
    <h2><?= __('My Certificates', 'woocertificatespackage'); ?></h2>
    <p><?= __('Here you will find a list of all your available certificates.', 'woocertificatespackage'); ?></p>

    <?php if (!empty($certificates)) : ?>
        <ul class="woocerti-certificates-list">
        <?php foreach ($certificates as $certificate) : ?>
            <li class="woocerti-certificate-item">
                <h3><?php echo esc_html($certificate['product_name']); ?></h3>
                <p><?= __('Click the link to view your certificate.', 'woocertificatespackage'); ?></p>
                <a href="<?php echo home_url('/certificado/'.$certificate['order_id']); ?>" class="button woocerti-certificate-link">
                    <?= __('View Certificate', 'woocertificatespackage'); ?>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?= __('You do not have any certificates available yet.', 'woocertificatespackage'); ?></p>
        <a href="<?= esc_url(home_url('/shop')); ?>" class="button wc-backward">
            <?= __('Buy Certificates', 'woocertificatespackage'); ?>
        </a>
    <?php endif; ?>
</div>