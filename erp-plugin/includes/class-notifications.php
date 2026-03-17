<?php
defined('ABSPATH') || exit;

class SaaSphere_Notifications {

    public static function create($data) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'saasphere_notifications', [
            'company_id' => $data['company_id'],
            'user_id'    => $data['user_id'],
            'type'       => sanitize_text_field($data['type']),
            'title'      => sanitize_text_field($data['title'] ?? ''),
            'message'    => sanitize_text_field($data['message']),
            'link'       => esc_url_raw($data['link'] ?? ''),
            'data'       => isset($data['data']) ? wp_json_encode($data['data']) : null,
        ]);
        return $wpdb->insert_id;
    }

    public static function get_for_user($user_id, $unread_only = false, $limit = 20) {
        global $wpdb;
        $where = $unread_only ? "AND is_read = 0" : "";
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}saasphere_notifications WHERE user_id = %d $where ORDER BY created_at DESC LIMIT %d",
            $user_id, $limit
        ));
    }

    public static function mark_read($id, $user_id) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'saasphere_notifications',
            ['is_read' => 1, 'read_at' => current_time('mysql')],
            ['id' => $id, 'user_id' => $user_id]
        );
    }

    public static function mark_all_read($user_id) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'saasphere_notifications',
            ['is_read' => 1, 'read_at' => current_time('mysql')],
            ['user_id' => $user_id, 'is_read' => 0]
        );
    }

    public static function count_unread($user_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}saasphere_notifications WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }

    public static function notify_team($company_id, $data, $exclude_user = null) {
        $users = SaaSphere_Company::get_users($company_id);
        foreach ($users as $user) {
            if ($exclude_user && $user->ID == $exclude_user) continue;
            self::create(array_merge($data, ['company_id' => $company_id, 'user_id' => $user->ID]));
        }
    }

    public static function send_email_notification($user_id, $subject, $message) {
        $user = get_userdata($user_id);
        if (!$user) return false;
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
}
