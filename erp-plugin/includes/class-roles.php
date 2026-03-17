<?php
defined('ABSPATH') || exit;

class SaaSphere_Roles {
    
    public static function init() {
        add_action('init', [__CLASS__, 'register_capabilities']);
    }

    public static function create_roles() {
        add_role('saasphere_super_admin', __('Super Admin SaaSphere', 'saasphere-erp'), [
            'read' => true, 'manage_saasphere' => true, 'manage_companies' => true,
            'view_all_data' => true, 'manage_users' => true, 'manage_settings' => true,
            'manage_crm' => true, 'manage_finance' => true, 'manage_hr' => true,
            'manage_projects' => true, 'manage_inventory' => true, 'view_reports' => true,
            'manage_automations' => true, 'impersonate_users' => true,
        ]);

        add_role('saasphere_admin', __('Admin Entreprise', 'saasphere-erp'), [
            'read' => true, 'manage_saasphere' => true,
            'manage_crm' => true, 'manage_finance' => true, 'manage_hr' => true,
            'manage_projects' => true, 'manage_inventory' => true, 'view_reports' => true,
            'manage_users' => true, 'manage_settings' => true, 'manage_automations' => true,
        ]);

        add_role('saasphere_manager', __('Manager', 'saasphere-erp'), [
            'read' => true, 'manage_saasphere' => true,
            'manage_crm' => true, 'manage_finance' => true, 'manage_projects' => true,
            'view_reports' => true, 'manage_hr' => true,
        ]);

        add_role('saasphere_employee', __('Employé', 'saasphere-erp'), [
            'read' => true, 'manage_saasphere' => true,
            'view_crm' => true, 'view_projects' => true, 'manage_own_tasks' => true,
            'log_time' => true, 'request_leave' => true,
        ]);

        add_role('saasphere_client', __('Client', 'saasphere-erp'), [
            'read' => true, 'view_own_invoices' => true, 'view_own_projects' => true,
        ]);

        $admin = get_role('administrator');
        if ($admin) {
            $caps = ['manage_saasphere', 'manage_companies', 'view_all_data', 'manage_crm',
                'manage_finance', 'manage_hr', 'manage_projects', 'manage_inventory',
                'view_reports', 'manage_automations', 'manage_settings', 'impersonate_users'];
            foreach ($caps as $cap) $admin->add_cap($cap);
        }
    }

    public static function register_capabilities() {}

    public static function get_user_role($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        $role = get_user_meta($user_id, 'saasphere_role', true);
        if ($role) return $role;
        $user = get_userdata($user_id);
        if (!$user) return 'none';
        $role_map = [
            'administrator' => 'super_admin', 'saasphere_super_admin' => 'super_admin',
            'saasphere_admin' => 'admin', 'saasphere_manager' => 'manager',
            'saasphere_employee' => 'employee', 'saasphere_client' => 'client',
        ];
        foreach ($user->roles as $r) {
            if (isset($role_map[$r])) return $role_map[$r];
        }
        return 'employee';
    }

    public static function can($capability, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        $role = self::get_user_role($user_id);
        if ($role === 'super_admin') return true;
        $custom = get_user_meta($user_id, 'saasphere_permissions', true);
        if (is_array($custom) && in_array($capability, $custom)) return true;
        return user_can($user_id, $capability);
    }

    public static function is_super_admin($user_id = null) {
        return self::get_user_role($user_id) === 'super_admin';
    }
}
