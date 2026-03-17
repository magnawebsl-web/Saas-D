<?php
defined('ABSPATH') || exit;

class SaaSphere_Audit_Log {

    public static function log($action, $entity_type = null, $entity_id = null, $description = '', $old_values = null, $new_values = null) {
        global $wpdb;
        $company_id = function_exists('saasphere_get_company_id') ? saasphere_get_company_id() : 0;
        $wpdb->insert($wpdb->prefix . 'saasphere_audit_log', [
            'company_id'  => $company_id,
            'user_id'     => get_current_user_id(),
            'action'      => sanitize_text_field($action),
            'entity_type' => $entity_type ? sanitize_text_field($entity_type) : null,
            'entity_id'   => $entity_id ? absint($entity_id) : null,
            'description' => sanitize_text_field($description),
            'old_values'  => $old_values ? wp_json_encode($old_values) : null,
            'new_values'  => $new_values ? wp_json_encode($new_values) : null,
            'ip_address'  => SaaSphere_Security::get_client_ip(),
            'user_agent'  => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);
        return $wpdb->insert_id;
    }

    public static function get_logs($args = []) {
        global $wpdb;
        $defaults = ['company_id' => 0, 'user_id' => 0, 'entity_type' => '', 'entity_id' => 0, 'action' => '', 'page' => 1, 'per_page' => 50, 'date_from' => '', 'date_to' => ''];
        $args = wp_parse_args($args, $defaults);
        $where = '1=1';
        $params = [];

        if ($args['company_id']) { $where .= " AND a.company_id = %d"; $params[] = $args['company_id']; }
        if ($args['user_id']) { $where .= " AND a.user_id = %d"; $params[] = $args['user_id']; }
        if ($args['entity_type']) { $where .= " AND a.entity_type = %s"; $params[] = $args['entity_type']; }
        if ($args['entity_id']) { $where .= " AND a.entity_id = %d"; $params[] = $args['entity_id']; }
        if ($args['action']) { $where .= " AND a.action = %s"; $params[] = $args['action']; }
        if ($args['date_from']) { $where .= " AND a.created_at >= %s"; $params[] = $args['date_from']; }
        if ($args['date_to']) { $where .= " AND a.created_at <= %s"; $params[] = $args['date_to'] . ' 23:59:59'; }

        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql = "SELECT a.*, u.display_name as user_name FROM {$wpdb->prefix}saasphere_audit_log a LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID WHERE $where ORDER BY a.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        return $wpdb->get_results($params ? $wpdb->prepare($sql, $params) : $sql);
    }
}
