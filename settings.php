<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Defines asset versions to prevent caching issues.
 */
define('WOOCERTI_VERSION_ASSETS', '1.0.0');
define('WOOCERTI_ROLE_USER_ALIANZA', 'institutes');
define('WOOCERTI_CERTIFICATE_SLUG', 'certificado-academico-virtual');
define('WOOCERTI_POSTS_PER_PAGE', 20);
define('WOOCERTI_NAME_PRODUCT_DEFAULT', 'Virtual Certificate');
define('WOOCERTI_SLUG_PRODUCT_DEFAULT', sanitize_title(WOOCERTI_NAME_PRODUCT_DEFAULT));
define('WOOCERTI_NAME_CATEGORY_DEFAULT', 'Certificate');
define('WOOCERTI_SLUG_CATEGORY_DEFAULT', sanitize_title(WOOCERTI_NAME_CATEGORY_DEFAULT));