<?php
/**
 * Plugin Name: SaaSphere ERP
 * Plugin URI: https://saasphere.io
 * Description: Plateforme de gestion d'entreprise complète - ERP, CRM, Finance, RH, Projets, Inventaire, IA.
 * Version: 1.0.0
 * Author: SaaSphere
 * Author URI: https://saasphere.io
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 8.0
 * Text Domain: saasphere-erp
 * Domain Path: /languages
 * License: GPL v2 or later
 */

defined('ABSPATH') || exit;

define('SAASPHERE_ERP_VERSION', '1.0.0');
define('SAASPHERE_ERP_FILE', __FILE__);
define('SAASPHERE_ERP_DIR', plugin_dir_path(__FILE__));
define('SAASPHERE_ERP_URL', plugin_dir_url(__FILE__));

final class SaaSphere_ERP {
    private static $instance = null;
    private $modules = [];

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once SAASPHERE_ERP_DIR . 'includes/class-database.php';
        require_once SAASPHERE_ERP_DIR . 'includes/class-roles.php';
        require_once SAASPHERE_ERP_DIR . 'includes/class-security.php';
        require_once SAASPHERE_ERP_DIR . 'includes/class-company.php';
        require_once SAASPHERE_ERP_DIR . 'includes/class-notifications.php';
        require_once SAASPHERE_ERP_DIR . 'includes/class-audit-log.php';
        require_once SAASPHERE_ERP_DIR . 'includes/class-automation.php';

        require_once SAASPHERE_ERP_DIR . 'modules/crm/class-crm.php';
        require_once SAASPHERE_ERP_DIR . 'modules/finance/class-finance.php';
        require_once SAASPHERE_ERP_DIR . 'modules/hr/class-hr.php';
        require_once SAASPHERE_ERP_DIR . 'modules/projects/class-projects.php';
        require_once SAASPHERE_ERP_DIR . 'modules/inventory/class-inventory.php';

        require_once SAASPHERE_ERP_DIR . 'api/class-rest-api.php';
    }

    private function init_hooks() {
        register_activation_hook(SAASPHERE_ERP_FILE, [$this, 'activate']);
        register_deactivation_hook(SAASPHERE_ERP_FILE, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function init() {
        load_plugin_textdomain('saasphere-erp', false, dirname(plugin_basename(SAASPHERE_ERP_FILE)) . '/languages');

        $this->modules['crm']       = new SaaSphere_CRM();
        $this->modules['finance']   = new SaaSphere_Finance();
        $this->modules['hr']        = new SaaSphere_HR();
        $this->modules['projects']  = new SaaSphere_Projects();
        $this->modules['inventory'] = new SaaSphere_Inventory();

        SaaSphere_Roles::init();
    }

    public function register_rest_routes() {
        $api = new SaaSphere_REST_API();
        $api->register_routes();
    }

    public function activate() {
        SaaSphere_Database::create_tables();
        SaaSphere_Roles::create_roles();
        SaaSphere_Database::seed_demo_data();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function get_module($name) {
        return $this->modules[$name] ?? null;
    }
}

function saasphere_erp() {
    return SaaSphere_ERP::instance();
}

saasphere_erp();
