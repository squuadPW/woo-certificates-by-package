<?php 
function woocerti_plugin_scripts(){
     $version = VERSIONS_EQ;
     wp_enqueue_style('woo-certi-style', plugins_url('woo-certificates-by-package') . '/public/assets/css/style.css', array(), $version, 'all');
     wp_enqueue_script('woo-certi-js', plugins_url('woo-certificates-by-package') . '/public/assets/js/main.js',array('jquery'), $version, true);
}

add_action('wp_enqueue_scripts', 'woocerti_plugin_scripts');