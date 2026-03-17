<?php
defined('ABSPATH') || exit;

function saasphere_ajax_search() {
    check_ajax_referer('saasphere_nonce', 'nonce');
    $query = sanitize_text_field($_POST['query'] ?? '');
    $company_id = saasphere_get_company_id();
    if (!$company_id || strlen($query) < 2) wp_send_json_error();

    global $wpdb;
    $results = [];

    $contacts = $wpdb->get_results($wpdb->prepare(
        "SELECT id, CONCAT(first_name, ' ', last_name) as title, email as subtitle, 'contact' as type FROM {$wpdb->prefix}saasphere_contacts WHERE company_id = %d AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s) LIMIT 5",
        $company_id, "%{$query}%", "%{$query}%", "%{$query}%"
    ));
    foreach ($contacts as $c) $results[] = $c;

    $invoices = $wpdb->get_results($wpdb->prepare(
        "SELECT id, invoice_number as title, CONCAT(total_amount, ' €') as subtitle, 'invoice' as type FROM {$wpdb->prefix}saasphere_invoices WHERE company_id = %d AND invoice_number LIKE %s LIMIT 5",
        $company_id, "%{$query}%"
    ));
    foreach ($invoices as $i) $results[] = $i;

    $projects = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name as title, status as subtitle, 'project' as type FROM {$wpdb->prefix}saasphere_projects WHERE company_id = %d AND name LIKE %s LIMIT 5",
        $company_id, "%{$query}%"
    ));
    foreach ($projects as $p) $results[] = $p;

    wp_send_json_success($results);
}
add_action('wp_ajax_saasphere_search', 'saasphere_ajax_search');

function saasphere_ajax_toggle_dark_mode() {
    check_ajax_referer('saasphere_nonce', 'nonce');
    $mode = sanitize_text_field($_POST['mode'] ?? 'light');
    update_user_meta(get_current_user_id(), 'saasphere_dark_mode', $mode);
    wp_send_json_success();
}
add_action('wp_ajax_saasphere_toggle_dark_mode', 'saasphere_ajax_toggle_dark_mode');

function saasphere_ajax_get_notifications() {
    check_ajax_referer('saasphere_nonce', 'nonce');
    global $wpdb;
    $company_id = saasphere_get_company_id();

    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}saasphere_notifications WHERE company_id = %d AND user_id = %d AND is_read = 0 ORDER BY created_at DESC LIMIT 20",
        $company_id, get_current_user_id()
    ));

    wp_send_json_success($notifications);
}
add_action('wp_ajax_saasphere_get_notifications', 'saasphere_ajax_get_notifications');

function saasphere_ajax_mark_notification_read() {
    check_ajax_referer('saasphere_nonce', 'nonce');
    global $wpdb;
    $id = absint($_POST['notification_id'] ?? 0);
    $wpdb->update(
        $wpdb->prefix . 'saasphere_notifications',
        ['is_read' => 1, 'read_at' => current_time('mysql')],
        ['id' => $id, 'user_id' => get_current_user_id()],
        ['%d', '%s'],
        ['%d', '%d']
    );
    wp_send_json_success();
}
add_action('wp_ajax_saasphere_mark_notification_read', 'saasphere_ajax_mark_notification_read');

function saasphere_ajax_dashboard_stats() {
    check_ajax_referer('saasphere_nonce', 'nonce');
    if (!saasphere_current_user_can_module('overview')) wp_send_json_error('Permission refusée');

    global $wpdb;
    $company_id = saasphere_get_company_id();
    $month_start = date('Y-m-01');
    $month_end   = date('Y-m-t');

    $revenue = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(total_amount), 0) FROM {$wpdb->prefix}saasphere_invoices WHERE company_id = %d AND status = 'paid' AND paid_date BETWEEN %s AND %s",
        $company_id, $month_start, $month_end
    ));

    $new_clients = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}saasphere_contacts WHERE company_id = %d AND type = 'client' AND created_at BETWEEN %s AND %s",
        $company_id, $month_start, $month_end . ' 23:59:59'
    ));

    $active_projects = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}saasphere_projects WHERE company_id = %d AND status IN ('active', 'in_progress')",
        $company_id
    ));

    $pending_invoices = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(total_amount), 0) FROM {$wpdb->prefix}saasphere_invoices WHERE company_id = %d AND status = 'pending'",
        $company_id
    ));

    $monthly_revenue = $wpdb->get_results($wpdb->prepare(
        "SELECT MONTH(paid_date) as month, COALESCE(SUM(total_amount), 0) as total FROM {$wpdb->prefix}saasphere_invoices WHERE company_id = %d AND status = 'paid' AND YEAR(paid_date) = YEAR(CURDATE()) GROUP BY MONTH(paid_date) ORDER BY month",
        $company_id
    ));

    wp_send_json_success([
        'revenue'          => (float) $revenue,
        'new_clients'      => (int) $new_clients,
        'active_projects'  => (int) $active_projects,
        'pending_invoices' => (float) $pending_invoices,
        'monthly_revenue'  => $monthly_revenue,
    ]);
}
add_action('wp_ajax_saasphere_dashboard_stats', 'saasphere_ajax_dashboard_stats');
