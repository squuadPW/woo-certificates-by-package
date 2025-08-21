<?php 
function woocerti_plugin_scripts(){
     $version = VERSIONS_ES;
     wp_enqueue_style('woo-certi-style', plugins_url('woo-certificates-by-package') . '/public/assets/css/style.css', array(), $version, 'all');
     wp_enqueue_script('woo-certi-js', plugins_url('woo-certificates-by-package') . '/public/assets/js/main.js',array('jquery'), $version, true);
}

add_action('wp_enqueue_scripts', 'woocerti_plugin_scripts');

function woocerti_link( $menu_links ) {
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'certificates' => __('Certificates','woocertificatespackage') )
	+ array_slice( $menu_links, 5, NULL, true );
	return $menu_links;
}

add_filter ( 'woocommerce_account_menu_items', 'woocerti_link', 40 );

// register permalink endpoint
function woocerti_add_endpoint() {
	add_rewrite_endpoint( 'certificates', EP_PAGES );
}
add_action( 'init', 'woocerti_add_endpoint' );

// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
function woocerti_my_account_endpoint_content() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $certificate_slug = 'certificado-academico-virtual';
    $certificate_product_id = get_page_by_path( $certificate_slug, OBJECT, 'product' )->ID;

    if ( ! $certificate_product_id ) {
        echo '<p>' . __( 'El producto de certificado no pudo ser encontrado.', 'woocertificatespackage' ) . '</p>';
        return;
    }

    $user_id = get_current_user_id();

    // Obtener los pedidos completados del usuario.
    $customer_orders = wc_get_orders( array(
        'customer' => $user_id,
        'status'   => 'completed',
        'limit'    => -1,
    ) );

    $certificates = array();

    // Iterar sobre los pedidos para encontrar el producto de certificado.
    if ( $customer_orders ) {
        foreach ( $customer_orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                $product_id = $item->get_product_id();

                if ( $product_id == $certificate_product_id ) {
                    $certificates[] = array(
                        'product_name' => $item->get_name(),
                        'order_id'     => $order->get_id(),
                    );
                }
            }
        }
    }

    // Cargar el archivo de plantilla con los datos.
    $template_file = WCBP_PLUGIN_DIR . 'public/templates/certificates.php';

    if ( file_exists( $template_file ) ) {
        // Incluye el archivo de la plantilla y pasa la variable $certificates.
        include $template_file;
    } else {
        echo '<p>' . __( 'No se pudo encontrar el archivo de plantilla para los certificados.', 'woocertificatespackage' ) . '</p>';
    }
}
add_action( 'woocommerce_account_certificates_endpoint', 'woocerti_my_account_endpoint_content' );

