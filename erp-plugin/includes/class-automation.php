<?php
defined('ABSPATH') || exit;

class SaaSphere_Automation {

    public static function init() {
        add_action('saasphere_invoice_created', [__CLASS__, 'on_invoice_created'], 10, 2);
        add_action('saasphere_deal_stage_changed', [__CLASS__, 'on_deal_stage_changed'], 10, 3);
        add_action('saasphere_task_completed', [__CLASS__, 'on_task_completed'], 10, 2);
        add_action('saasphere_leave_requested', [__CLASS__, 'on_leave_requested'], 10, 2);
        add_action('saasphere_stock_low', [__CLASS__, 'on_stock_low'], 10, 2);
        add_action('saasphere_daily_cron', [__CLASS__, 'run_daily_automations']);
    }

    public static function create($data) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'saasphere_automations', [
            'company_id'     => $data['company_id'],
            'name'           => sanitize_text_field($data['name']),
            'description'    => sanitize_textarea_field($data['description'] ?? ''),
            'trigger_type'   => sanitize_text_field($data['trigger_type']),
            'trigger_config' => wp_json_encode($data['trigger_config'] ?? []),
            'action_type'    => sanitize_text_field($data['action_type']),
            'action_config'  => wp_json_encode($data['action_config'] ?? []),
            'is_active'      => 1,
            'created_by'     => get_current_user_id(),
        ]);
        return $wpdb->insert_id;
    }

    public static function get_active($company_id, $trigger_type = null) {
        global $wpdb;
        $where = "company_id = %d AND is_active = 1";
        $params = [$company_id];
        if ($trigger_type) { $where .= " AND trigger_type = %s"; $params[] = $trigger_type; }
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}saasphere_automations WHERE $where", $params
        ));
    }

    public static function execute($automation, $context = []) {
        global $wpdb;
        $config = json_decode($automation->action_config, true) ?: [];
        switch ($automation->action_type) {
            case 'send_notification':
                SaaSphere_Notifications::create([
                    'company_id' => $automation->company_id,
                    'user_id'    => $config['user_id'] ?? $context['user_id'] ?? 0,
                    'type'       => 'automation',
                    'message'    => self::parse_template($config['message'] ?? '', $context),
                    'link'       => $config['link'] ?? '',
                ]);
                break;
            case 'send_email':
                $to = $config['to'] ?? $context['email'] ?? '';
                if ($to) wp_mail($to, self::parse_template($config['subject'] ?? '', $context), self::parse_template($config['body'] ?? '', $context), ['Content-Type: text/html']);
                break;
            case 'update_field':
                if (isset($config['table'], $config['field'], $config['value'], $context['entity_id'])) {
                    $wpdb->update($wpdb->prefix . 'saasphere_' . $config['table'], [$config['field'] => $config['value']], ['id' => $context['entity_id']]);
                }
                break;
            case 'create_task':
                if (isset($config['title'])) {
                    $wpdb->insert($wpdb->prefix . 'saasphere_tasks', [
                        'company_id' => $automation->company_id, 'title' => self::parse_template($config['title'], $context),
                        'assigned_to' => $config['assigned_to'] ?? 0, 'priority' => $config['priority'] ?? 'medium', 'status' => 'todo',
                        'due_date' => date('Y-m-d', strtotime('+' . ($config['due_days'] ?? 7) . ' days')),
                    ]);
                }
                break;
        }
        $wpdb->update($wpdb->prefix . 'saasphere_automations', ['last_run' => current_time('mysql'), 'run_count' => $automation->run_count + 1], ['id' => $automation->id]);
    }

    private static function parse_template($template, $context) {
        foreach ($context as $key => $value) {
            if (is_string($value)) $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    public static function on_invoice_created($invoice_id, $company_id) {
        $automations = self::get_active($company_id, 'invoice_created');
        foreach ($automations as $auto) self::execute($auto, ['entity_id' => $invoice_id, 'entity_type' => 'invoice']);
    }

    public static function on_deal_stage_changed($deal_id, $new_stage, $company_id) {
        $automations = self::get_active($company_id, 'deal_stage_changed');
        foreach ($automations as $auto) {
            $config = json_decode($auto->trigger_config, true) ?: [];
            if (empty($config['stage']) || $config['stage'] === $new_stage) {
                self::execute($auto, ['entity_id' => $deal_id, 'entity_type' => 'deal', 'stage' => $new_stage]);
            }
        }
    }

    public static function on_task_completed($task_id, $company_id) {
        $automations = self::get_active($company_id, 'task_completed');
        foreach ($automations as $auto) self::execute($auto, ['entity_id' => $task_id, 'entity_type' => 'task']);
    }

    public static function on_leave_requested($leave_id, $company_id) {
        $automations = self::get_active($company_id, 'leave_requested');
        foreach ($automations as $auto) self::execute($auto, ['entity_id' => $leave_id, 'entity_type' => 'leave']);
    }

    public static function on_stock_low($product_id, $company_id) {
        $automations = self::get_active($company_id, 'stock_low');
        foreach ($automations as $auto) self::execute($auto, ['entity_id' => $product_id, 'entity_type' => 'product']);
    }

    public static function run_daily_automations() {
        global $wpdb;
        $overdue = $wpdb->get_results("SELECT i.*, c.name as company_name FROM {$wpdb->prefix}saasphere_invoices i JOIN {$wpdb->prefix}saasphere_companies c ON i.company_id = c.id WHERE i.status = 'pending' AND i.due_date < CURDATE()");
        foreach ($overdue as $invoice) {
            $wpdb->update($wpdb->prefix . 'saasphere_invoices', ['status' => 'overdue'], ['id' => $invoice->id]);
            SaaSphere_Notifications::create(['company_id' => $invoice->company_id, 'user_id' => $invoice->created_by ?: 1, 'type' => 'invoice_overdue', 'message' => sprintf('Facture %s en retard de paiement', $invoice->invoice_number), 'link' => '/dashboard/finance/invoice/' . $invoice->id]);
        }
    }
}

SaaSphere_Automation::init();
