<?php
defined('ABSPATH') || exit;

function saasphere_format_currency($amount, $currency = null) {
    $currency = $currency ?: get_option('saasphere_currency', 'EUR');
    $symbols  = ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'MAD' => 'DH', 'XOF' => 'CFA'];
    $symbol   = $symbols[$currency] ?? $currency;
    return number_format((float) $amount, 2, ',', ' ') . ' ' . $symbol;
}

function saasphere_format_date($date, $format = null) {
    $format = $format ?: get_option('saasphere_date_format', 'd/m/Y');
    return date_i18n($format, strtotime($date));
}

function saasphere_get_initials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(mb_substr($part, 0, 1));
    }
    return $initials;
}

function saasphere_get_status_badge($status, $type = 'default') {
    $classes = [
        'active'    => 'ss-badge--success',
        'completed' => 'ss-badge--success',
        'paid'      => 'ss-badge--success',
        'pending'   => 'ss-badge--warning',
        'in_progress' => 'ss-badge--info',
        'overdue'   => 'ss-badge--danger',
        'cancelled' => 'ss-badge--danger',
        'draft'     => 'ss-badge--secondary',
        'inactive'  => 'ss-badge--secondary',
    ];
    $labels = [
        'active'    => __('Actif', 'saasphere'),
        'completed' => __('Terminé', 'saasphere'),
        'paid'      => __('Payé', 'saasphere'),
        'pending'   => __('En attente', 'saasphere'),
        'in_progress' => __('En cours', 'saasphere'),
        'overdue'   => __('En retard', 'saasphere'),
        'cancelled' => __('Annulé', 'saasphere'),
        'draft'     => __('Brouillon', 'saasphere'),
        'inactive'  => __('Inactif', 'saasphere'),
    ];
    $class = $classes[$status] ?? 'ss-badge--secondary';
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="ss-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
}

function saasphere_current_user_can_module($module) {
    $user = wp_get_current_user();
    if (in_array('administrator', $user->roles) || in_array('saasphere_super_admin', $user->roles)) {
        return true;
    }
    $permissions = get_user_meta($user->ID, 'saasphere_permissions', true);
    if (!is_array($permissions)) return false;
    return in_array($module, $permissions);
}

function saasphere_get_company_id($user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    return (int) get_user_meta($user_id, 'saasphere_company_id', true);
}

function saasphere_user_avatar($user_id, $size = 40) {
    $avatar_url = get_user_meta($user_id, 'saasphere_avatar', true);
    if ($avatar_url) {
        return '<img src="' . esc_url($avatar_url) . '" alt="" class="ss-avatar" width="' . $size . '" height="' . $size . '">';
    }
    $user = get_userdata($user_id);
    $name = $user ? $user->display_name : '?';
    $initials = saasphere_get_initials($name);
    $colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#06b6d4'];
    $color = $colors[$user_id % count($colors)];
    return '<div class="ss-avatar ss-avatar--initials" style="width:' . $size . 'px;height:' . $size . 'px;background:' . $color . ';font-size:' . ($size * 0.4) . 'px">' . esc_html($initials) . '</div>';
}

function saasphere_time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return sprintf(_n('il y a %d an', 'il y a %d ans', $diff->y, 'saasphere'), $diff->y);
    if ($diff->m > 0) return sprintf(_n('il y a %d mois', 'il y a %d mois', $diff->m, 'saasphere'), $diff->m);
    if ($diff->d > 0) return sprintf(_n('il y a %d jour', 'il y a %d jours', $diff->d, 'saasphere'), $diff->d);
    if ($diff->h > 0) return sprintf(_n('il y a %d heure', 'il y a %d heures', $diff->h, 'saasphere'), $diff->h);
    if ($diff->i > 0) return sprintf(_n('il y a %d minute', 'il y a %d minutes', $diff->i, 'saasphere'), $diff->i);
    return __('à l\'instant', 'saasphere');
}

function saasphere_pagination($total, $per_page, $current_page) {
    $total_pages = ceil($total / $per_page);
    if ($total_pages <= 1) return '';

    $html = '<div class="ss-pagination">';
    $html .= '<span class="ss-pagination__info">' . sprintf(__('%d - %d sur %d', 'saasphere'), (($current_page - 1) * $per_page) + 1, min($current_page * $per_page, $total), $total) . '</span>';
    $html .= '<div class="ss-pagination__nav">';
    if ($current_page > 1) {
        $html .= '<button class="ss-btn ss-btn--ghost ss-btn--sm" data-page="' . ($current_page - 1) . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"/></svg></button>';
    }
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i === $current_page ? ' ss-btn--primary' : ' ss-btn--ghost';
        $html .= '<button class="ss-btn ss-btn--sm' . $active . '" data-page="' . $i . '">' . $i . '</button>';
    }
    if ($current_page < $total_pages) {
        $html .= '<button class="ss-btn ss-btn--ghost ss-btn--sm" data-page="' . ($current_page + 1) . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"/></svg></button>';
    }
    $html .= '</div></div>';
    return $html;
}
