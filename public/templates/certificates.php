<div class="woocommerce-certificates-page">
    <h2><?= __( 'Mis Certificados', 'woocertificatespackage' ); ?></h2>
    <p><?= __( 'Aquí encontrarás una lista de todos tus certificados disponibles.', 'woocertificatespackage' ); ?></p>

    <?php if ( ! empty( $certificates ) ) : ?>
        <ul class="wcbp-certificates-list">
        <?php foreach ( $certificates as $certificate ) : ?>
            <li class="wcbp-certificate-item">
                <h3><?php echo esc_html( $certificate['product_name'] ); ?></h3>
                <p><?= __( 'Haz clic en el enlace para ver tu certificado.', 'woocertificatespackage' ); ?></p>
                <a href="<?php echo home_url( '/certificado/' . $certificate['order_id'] ); ?>" class="button wcbp-certificate-link">
                    <?= __( 'Ver Certificado', 'woocertificatespackage' ); ?>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?= __( 'Aún no tienes certificados disponibles.', 'woocertificatespackage' ); ?></p>
    <?php endif; ?>

</div>