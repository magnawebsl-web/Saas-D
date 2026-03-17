<?php
defined('ABSPATH') || exit;

function saasphere_enqueue_assets() {
    wp_enqueue_style('saasphere-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap', [], null);
    wp_enqueue_style('saasphere-icons', 'https://cdn.jsdelivr.net/npm/lucide-static@0.263.1/font/lucide.min.css', [], '0.263.1');
    wp_enqueue_style('saasphere-main', SAASPHERE_THEME_URI . '/assets/css/main.css', [], SAASPHERE_VERSION);
    wp_enqueue_style('saasphere-dashboard', SAASPHERE_THEME_URI . '/assets/css/dashboard.css', ['saasphere-main'], SAASPHERE_VERSION);
    wp_enqueue_style('saasphere-components', SAASPHERE_THEME_URI . '/assets/css/components.css', ['saasphere-main'], SAASPHERE_VERSION);
    wp_enqueue_style('saasphere-dark', SAASPHERE_THEME_URI . '/assets/css/dark-mode.css', ['saasphere-main'], SAASPHERE_VERSION);

    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
    wp_enqueue_script('sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], '1.15.0', true);
    wp_enqueue_script('saasphere-app', SAASPHERE_THEME_URI . '/assets/js/app.js', ['jquery', 'chart-js', 'sortable-js'], SAASPHERE_VERSION, true);
    wp_enqueue_script('saasphere-dashboard-js', SAASPHERE_THEME_URI . '/assets/js/dashboard.js', ['saasphere-app'], SAASPHERE_VERSION, true);
    wp_enqueue_script('saasphere-charts', SAASPHERE_THEME_URI . '/assets/js/charts.js', ['chart-js', 'saasphere-app'], SAASPHERE_VERSION, true);
    wp_enqueue_script('saasphere-kanban', SAASPHERE_THEME_URI . '/assets/js/kanban.js', ['sortable-js', 'saasphere-app'], SAASPHERE_VERSION, true);

    wp_localize_script('saasphere-app', 'SaaSphereCfg', [
        'ajaxUrl'   => admin_url('admin-ajax.php'),
        'restUrl'   => rest_url('saasphere/v1/'),
        'nonce'     => wp_create_nonce('saasphere_nonce'),
        'restNonce' => wp_create_nonce('wp_rest'),
        'userId'    => get_current_user_id(),
        'themeUrl'  => SAASPHERE_THEME_URI,
        'locale'    => get_locale(),
        'currency'  => get_option('saasphere_currency', 'EUR'),
        'dateFormat'=> get_option('saasphere_date_format', 'd/m/Y'),
    ]);
}
add_action('wp_enqueue_scripts', 'saasphere_enqueue_assets');
