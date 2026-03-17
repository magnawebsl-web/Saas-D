<?php
defined('ABSPATH') || exit;

class SaaSphere_Company {

    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'saasphere_companies';
        $wpdb->insert($table, [
            'name'       => sanitize_text_field($data['name']),
            'slug'       => sanitize_title($data['name']),
            'email'      => sanitize_email($data['email'] ?? ''),
            'phone'      => sanitize_text_field($data['phone'] ?? ''),
            'address'    => sanitize_textarea_field($data['address'] ?? ''),
            'city'       => sanitize_text_field($data['city'] ?? ''),
            'country'    => sanitize_text_field($data['country'] ?? ''),
            'currency'   => sanitize_text_field($data['currency'] ?? 'EUR'),
            'plan'       => sanitize_text_field($data['plan'] ?? 'starter'),
            'is_active'  => 1,
            'created_by' => get_current_user_id(),
        ]);
        return $wpdb->insert_id;
    }

    public static function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}saasphere_companies WHERE id = %d", $id
        ));
    }

    public static function get_all($args = []) {
        global $wpdb;
        $defaults = ['page' => 1, 'per_page' => 20, 'search' => '', 'status' => ''];
        $args = wp_parse_args($args, $defaults);
        $where = '1=1';
        $params = [];
        if ($args['search']) {
            $where .= " AND (name LIKE %s OR email LIKE %s)";
            $params[] = '%' . $args['search'] . '%';
            $params[] = '%' . $args['search'] . '%';
        }
        if ($args['status'] === 'active') { $where .= " AND is_active = 1"; }
        elseif ($args['status'] === 'inactive') { $where .= " AND is_active = 0"; }

        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql = "SELECT * FROM {$wpdb->prefix}saasphere_companies WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($sql, $params));
        $total_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}saasphere_companies WHERE $where";
        $total = $params ? $wpdb->get_var($wpdb->prepare($total_sql, array_slice($params, 0, -2))) : $wpdb->get_var($total_sql);

        return ['items' => $items, 'total' => (int) $total, 'pages' => ceil($total / $args['per_page'])];
    }

    public static function update($id, $data) {
        global $wpdb;
        $allowed = ['name', 'email', 'phone', 'address', 'city', 'country', 'postal_code', 'website', 'tax_id', 'currency', 'timezone', 'plan', 'is_active', 'settings', 'logo_url'];
        $update = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) $update[$field] = $data[$field];
        }
        if (empty($update)) return false;
        return $wpdb->update($wpdb->prefix . 'saasphere_companies', $update, ['id' => $id]);
    }

    public static function delete($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'saasphere_companies', ['id' => $id]);
    }

    public static function get_stats($company_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'saasphere_';
        return [
            'contacts'  => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}contacts WHERE company_id = %d", $company_id)),
            'invoices'  => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}invoices WHERE company_id = %d", $company_id)),
            'projects'  => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}projects WHERE company_id = %d", $company_id)),
            'employees' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}employees WHERE company_id = %d", $company_id)),
            'revenue'   => (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_amount),0) FROM {$prefix}invoices WHERE company_id = %d AND status = 'paid'", $company_id)),
        ];
    }

    public static function get_users($company_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.user_login, u.user_email, u.display_name, um.meta_value as ss_role 
             FROM {$wpdb->users} u 
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'saasphere_company_id' AND um.meta_value = %d 
             LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'saasphere_role'
             ORDER BY u.display_name",
            $company_id
        ));
    }

    public static function switch_to($company_id) {
        $company = self::get($company_id);
        if (!$company) return false;
        update_user_meta(get_current_user_id(), 'saasphere_company_id', $company_id);
        return true;
    }
}
