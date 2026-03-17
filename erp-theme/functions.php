<?php
defined('ABSPATH') || exit;

define('SAASPHERE_VERSION', '1.0.0');
define('SAASPHERE_THEME_DIR', get_template_directory());
define('SAASPHERE_THEME_URI', get_template_directory_uri());

require_once SAASPHERE_THEME_DIR . '/inc/theme-setup.php';
require_once SAASPHERE_THEME_DIR . '/inc/enqueue.php';
require_once SAASPHERE_THEME_DIR . '/inc/dashboard-routes.php';
require_once SAASPHERE_THEME_DIR . '/inc/helpers.php';
require_once SAASPHERE_THEME_DIR . '/inc/customizer.php';
require_once SAASPHERE_THEME_DIR . '/inc/ajax-handlers.php';
