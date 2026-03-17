<?php
defined('ABSPATH') || exit;

class SaaSphere_CRM {

    public function __construct() {
        add_action('wp_ajax_saasphere_crm_save_contact', [$this, 'save_contact']);
        add_action('wp_ajax_saasphere_crm_delete_contact', [$this, 'delete_contact']);
        add_action('wp_ajax_saasphere_crm_save_deal', [$this, 'save_deal']);
        add_action('wp_ajax_saasphere_crm_update_deal_stage', [$this, 'update_deal_stage']);
        add_action('wp_ajax_saasphere_crm_get_contacts', [$this, 'get_contacts_ajax']);
    }

    public static function get_contacts($args = []) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'saasphere_';
        $defaults = ['company_id' => 0, 'type' => '', 'status' => '', 'search' => '', 'assigned_to' => 0, 'page' => 1, 'per_page' => 20, 'orderby' => 'created_at', 'order' => 'DESC'];
        $args = wp_parse_args($args, $defaults);

        $where = "company_id = %d";
        $params = [$args['company_id']];

        if ($args['type']) { $where .= " AND type = %s"; $params[] = $args['type']; }
        if ($args['status']) { $where .= " AND status = %s"; $params[] = $args['status']; }
        if ($args['assigned_to']) { $where .= " AND assigned_to = %d"; $params[] = $args['assigned_to']; }
        if ($args['search']) {
            $where .= " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR organization LIKE %s)";
            $s = '%' . $args['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $allowed_orderby = ['created_at', 'first_name', 'last_name', 'email', 'score', 'organization'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ($args['page'] - 1) * $args['per_page'];

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}contacts WHERE $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
            array_merge($params, [$args['per_page'], $offset])
        ));

        $count_params = $params;
        $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}contacts WHERE $where", $count_params));

        return ['items' => $items, 'total' => $total, 'pages' => ceil($total / $args['per_page'])];
    }

    public static function get_contact($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}saasphere_contacts WHERE id = %d", $id));
    }

    public static function save($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'saasphere_contacts';
        $fields = [
            'company_id' => absint($data['company_id']),
            'type' => sanitize_text_field($data['type'] ?? 'client'),
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'mobile' => sanitize_text_field($data['mobile'] ?? ''),
            'job_title' => sanitize_text_field($data['job_title'] ?? ''),
            'organization' => sanitize_text_field($data['organization'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'country' => sanitize_text_field($data['country'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            'website' => esc_url_raw($data['website'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'source' => sanitize_text_field($data['source'] ?? ''),
            'tags' => sanitize_text_field($data['tags'] ?? ''),
            'score' => absint($data['score'] ?? 0),
            'assigned_to' => absint($data['assigned_to'] ?? get_current_user_id()),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
        ];

        if (!empty($data['id'])) {
            $wpdb->update($table, $fields, ['id' => absint($data['id'])]);
            $id = absint($data['id']);
            SaaSphere_Audit_Log::log('contact_updated', 'contact', $id, 'Contact mis a jour: ' . $fields['first_name'] . ' ' . $fields['last_name']);
        } else {
            $wpdb->insert($table, $fields);
            $id = $wpdb->insert_id;
            SaaSphere_Audit_Log::log('contact_created', 'contact', $id, 'Contact cree: ' . $fields['first_name'] . ' ' . $fields['last_name']);
        }
        return $id;
    }

    public static function delete($id) {
        global $wpdb;
        $contact = self::get_contact($id);
        if ($contact) {
            SaaSphere_Audit_Log::log('contact_deleted', 'contact', $id, 'Contact supprime: ' . $contact->first_name . ' ' . $contact->last_name);
        }
        return $wpdb->delete($wpdb->prefix . 'saasphere_contacts', ['id' => $id]);
    }

    public static function get_deals($args = []) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'saasphere_';
        $defaults = ['company_id' => 0, 'stage' => '', 'assigned_to' => 0, 'contact_id' => 0, 'page' => 1, 'per_page' => 50];
        $args = wp_parse_args($args, $defaults);

        $where = "d.company_id = %d";
        $params = [$args['company_id']];
        if ($args['stage']) { $where .= " AND d.stage = %s"; $params[] = $args['stage']; }
        if ($args['assigned_to']) { $where .= " AND d.assigned_to = %d"; $params[] = $args['assigned_to']; }
        if ($args['contact_id']) { $where .= " AND d.contact_id = %d"; $params[] = $args['contact_id']; }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, CONCAT(c.first_name,' ',c.last_name) as contact_name, c.organization 
             FROM {$prefix}deals d 
             LEFT JOIN {$prefix}contacts c ON d.contact_id = c.id 
             WHERE $where ORDER BY d.position ASC, d.updated_at DESC",
            $params
        ));
    }

    public static function save_deal_data($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'saasphere_deals';
        $fields = [
            'company_id' => absint($data['company_id']),
            'contact_id' => absint($data['contact_id'] ?? 0),
            'title' => sanitize_text_field($data['title']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'amount' => (float) ($data['amount'] ?? 0),
            'stage' => sanitize_text_field($data['stage'] ?? 'prospect'),
            'probability' => absint($data['probability'] ?? 0),
            'expected_close_date' => sanitize_text_field($data['expected_close_date'] ?? ''),
            'source' => sanitize_text_field($data['source'] ?? ''),
            'assigned_to' => absint($data['assigned_to'] ?? get_current_user_id()),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        ];

        if (!empty($data['id'])) {
            $old = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $data['id']));
            $wpdb->update($table, $fields, ['id' => absint($data['id'])]);
            if ($old && $old->stage !== $fields['stage']) {
                do_action('saasphere_deal_stage_changed', $data['id'], $fields['stage'], $fields['company_id']);
            }
            return absint($data['id']);
        } else {
            $wpdb->insert($table, $fields);
            return $wpdb->insert_id;
        }
    }

    public function save_contact() {
        check_ajax_referer('saasphere_nonce', 'nonce');
        if (!current_user_can('manage_crm')) wp_send_json_error('Permission refusee');
        $data = $_POST;
        $data['company_id'] = saasphere_get_company_id();
        $id = self::save($data);
        wp_send_json_success(['id' => $id]);
    }

    public function delete_contact() {
        check_ajax_referer('saasphere_nonce', 'nonce');
        if (!current_user_can('manage_crm')) wp_send_json_error('Permission refusee');
        $id = absint($_POST['id']);
        self::delete($id);
        wp_send_json_success();
    }

    public function save_deal() {
        check_ajax_referer('saasphere_nonce', 'nonce');
        if (!current_user_can('manage_crm')) wp_send_json_error('Permission refusee');
        $data = $_POST;
        $data['company_id'] = saasphere_get_company_id();
        $id = self::save_deal_data($data);
        wp_send_json_success(['id' => $id]);
    }

    public function update_deal_stage() {
        check_ajax_referer('saasphere_nonce', 'nonce');
        global $wpdb;
        $id = absint($_POST['deal_id']);
        $stage = sanitize_text_field($_POST['stage']);
        $company_id = saasphere_get_company_id();
        $wpdb->update($wpdb->prefix . 'saasphere_deals', ['stage' => $stage], ['id' => $id, 'company_id' => $company_id]);
        do_action('saasphere_deal_stage_changed', $id, $stage, $company_id);
        wp_send_json_success();
    }

    public function get_contacts_ajax() {
        check_ajax_referer('saasphere_nonce', 'nonce');
        $result = self::get_contacts([
            'company_id' => saasphere_get_company_id(),
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'page' => absint($_POST['page'] ?? 1),
            'per_page' => absint($_POST['per_page'] ?? 20),
        ]);
        wp_send_json_success($result);
    }

    public static function get_pipeline_stats($company_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'saasphere_';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT stage, COUNT(*) as count, COALESCE(SUM(amount),0) as total FROM {$prefix}deals WHERE company_id = %d GROUP BY stage",
            $company_id
        ), OBJECT_K);
    }
}
