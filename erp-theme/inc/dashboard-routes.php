<?php
defined('ABSPATH') || exit;

function saasphere_custom_rewrite_rules() {
    add_rewrite_rule('^dashboard/?$', 'index.php?saasphere_page=dashboard', 'top');
    add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?saasphere_page=dashboard&saasphere_module=$matches[1]', 'top');
    add_rewrite_rule('^dashboard/([^/]+)/([^/]+)/?$', 'index.php?saasphere_page=dashboard&saasphere_module=$matches[1]&saasphere_action=$matches[2]', 'top');
    add_rewrite_rule('^dashboard/([^/]+)/([^/]+)/([0-9]+)/?$', 'index.php?saasphere_page=dashboard&saasphere_module=$matches[1]&saasphere_action=$matches[2]&saasphere_id=$matches[3]', 'top');
}
add_action('init', 'saasphere_custom_rewrite_rules');

function saasphere_query_vars($vars) {
    $vars[] = 'saasphere_page';
    $vars[] = 'saasphere_module';
    $vars[] = 'saasphere_action';
    $vars[] = 'saasphere_id';
    return $vars;
}
add_filter('query_vars', 'saasphere_query_vars');

function saasphere_template_redirect() {
    $page = get_query_var('saasphere_page');
    if ($page !== 'dashboard') {
        return;
    }
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/login'));
        exit;
    }
    $module = get_query_var('saasphere_module', 'overview');
    $action = get_query_var('saasphere_action', 'index');
    $id     = get_query_var('saasphere_id', 0);

    $valid_modules = [
        'overview', 'crm', 'finance', 'hr', 'projects', 'inventory',
        'analytics', 'automation', 'settings', 'admin', 'clients',
        'reports', 'ai-assistant', 'notifications', 'profile'
    ];

    if (!in_array($module, $valid_modules)) {
        $module = 'overview';
    }

    set_query_var('saasphere_module', $module);
    set_query_var('saasphere_action', $action);
    set_query_var('saasphere_id', $id);

    get_template_part('templates/dashboard/layout');
    exit;
}
add_action('template_redirect', 'saasphere_template_redirect');

function saasphere_login_redirect() {
    add_rewrite_rule('^login/?$', 'index.php?saasphere_page=login', 'top');
    add_rewrite_rule('^register/?$', 'index.php?saasphere_page=register', 'top');
    add_rewrite_rule('^forgot-password/?$', 'index.php?saasphere_page=forgot-password', 'top');
}
add_action('init', 'saasphere_login_redirect');

function saasphere_auth_template_redirect() {
    $page = get_query_var('saasphere_page');
    $auth_pages = ['login', 'register', 'forgot-password'];

    if (!in_array($page, $auth_pages)) {
        return;
    }

    if (is_user_logged_in() && in_array($page, ['login', 'register'])) {
        wp_redirect(home_url('/dashboard'));
        exit;
    }

    get_template_part('templates/auth/' . $page);
    exit;
}
add_action('template_redirect', 'saasphere_auth_template_redirect');

function saasphere_activation_flush() {
    saasphere_custom_rewrite_rules();
    saasphere_login_redirect();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'saasphere_activation_flush');
