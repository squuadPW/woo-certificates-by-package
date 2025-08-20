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
    $template_file = WCBP_PLUGIN_DIR . 'public/templates/certificates.php';

    if ( file_exists( $template_file ) ) {
        // Usa require_once para cargar la plantilla
        require_once $template_file;
    } else {
        // Muestra un mensaje de error si el archivo no existe
        echo '<p>' . __( 'No se pudo encontrar el archivo de plantilla para los certificados.', 'woocertificatespackage' ) . '</p>';
    }
}
add_action( 'woocommerce_account_certificates_endpoint', 'woocerti_my_account_endpoint_content' );
