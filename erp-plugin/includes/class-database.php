<?php
defined('ABSPATH') || exit;

class SaaSphere_Database {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'saasphere_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = [];

        $tables[] = "CREATE TABLE {$prefix}companies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(50),
            address TEXT,
            city VARCHAR(100),
            country VARCHAR(100),
            postal_code VARCHAR(20),
            website VARCHAR(255),
            logo_url VARCHAR(500),
            tax_id VARCHAR(100),
            currency VARCHAR(10) DEFAULT 'EUR',
            timezone VARCHAR(50) DEFAULT 'Europe/Paris',
            plan VARCHAR(50) DEFAULT 'starter',
            plan_expires_at DATETIME,
            is_active TINYINT(1) DEFAULT 1,
            settings LONGTEXT,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_slug (slug),
            KEY idx_active (is_active)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}contacts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            type ENUM('client', 'prospect', 'lead', 'supplier', 'partner') DEFAULT 'client',
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(50),
            mobile VARCHAR(50),
            job_title VARCHAR(200),
            organization VARCHAR(255),
            address TEXT,
            city VARCHAR(100),
            country VARCHAR(100),
            postal_code VARCHAR(20),
            website VARCHAR(255),
            notes TEXT,
            avatar_url VARCHAR(500),
            source VARCHAR(100),
            tags TEXT,
            score INT DEFAULT 0,
            assigned_to BIGINT UNSIGNED,
            status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
            last_contacted_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_type (type),
            KEY idx_status (status),
            KEY idx_email (email),
            KEY idx_assigned (assigned_to)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}deals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            contact_id BIGINT UNSIGNED,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            amount DECIMAL(15,2) DEFAULT 0,
            currency VARCHAR(10) DEFAULT 'EUR',
            stage ENUM('prospect', 'qualified', 'proposal', 'negotiation', 'won', 'lost') DEFAULT 'prospect',
            probability INT DEFAULT 0,
            expected_close_date DATE,
            actual_close_date DATE,
            source VARCHAR(100),
            assigned_to BIGINT UNSIGNED,
            position INT DEFAULT 0,
            lost_reason TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_contact (contact_id),
            KEY idx_stage (stage),
            KEY idx_assigned (assigned_to)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}invoices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            contact_id BIGINT UNSIGNED,
            invoice_number VARCHAR(50) NOT NULL,
            type ENUM('invoice', 'quote', 'credit_note', 'proforma') DEFAULT 'invoice',
            status ENUM('draft', 'sent', 'pending', 'paid', 'partial', 'overdue', 'cancelled') DEFAULT 'draft',
            issue_date DATE NOT NULL,
            due_date DATE,
            paid_date DATE,
            subtotal DECIMAL(15,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 20.00,
            tax_amount DECIMAL(15,2) DEFAULT 0,
            discount_amount DECIMAL(15,2) DEFAULT 0,
            total_amount DECIMAL(15,2) DEFAULT 0,
            amount_paid DECIMAL(15,2) DEFAULT 0,
            currency VARCHAR(10) DEFAULT 'EUR',
            notes TEXT,
            terms TEXT,
            payment_method VARCHAR(50),
            recurring TINYINT(1) DEFAULT 0,
            recurring_interval VARCHAR(20),
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_number (invoice_number),
            KEY idx_company (company_id),
            KEY idx_contact (contact_id),
            KEY idx_status (status),
            KEY idx_type (type),
            KEY idx_dates (issue_date, due_date)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}invoice_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NOT NULL,
            description VARCHAR(500) NOT NULL,
            quantity DECIMAL(10,2) DEFAULT 1,
            unit_price DECIMAL(15,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 20.00,
            discount DECIMAL(5,2) DEFAULT 0,
            total DECIMAL(15,2) DEFAULT 0,
            sort_order INT DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_invoice (invoice_id)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}transactions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            type ENUM('income', 'expense', 'transfer') NOT NULL,
            category VARCHAR(100),
            description VARCHAR(500),
            amount DECIMAL(15,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'EUR',
            date DATE NOT NULL,
            reference VARCHAR(100),
            invoice_id BIGINT UNSIGNED,
            account VARCHAR(100),
            payment_method VARCHAR(50),
            status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
            notes TEXT,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_type (type),
            KEY idx_date (date),
            KEY idx_category (category),
            KEY idx_invoice (invoice_id)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}employees (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED,
            employee_number VARCHAR(50),
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(50),
            birth_date DATE,
            hire_date DATE,
            end_date DATE,
            department VARCHAR(100),
            position VARCHAR(200),
            manager_id BIGINT UNSIGNED,
            salary DECIMAL(15,2) DEFAULT 0,
            salary_type ENUM('monthly', 'hourly', 'annual') DEFAULT 'monthly',
            contract_type ENUM('cdi', 'cdd', 'freelance', 'intern', 'other') DEFAULT 'cdi',
            status ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
            address TEXT,
            city VARCHAR(100),
            country VARCHAR(100),
            avatar_url VARCHAR(500),
            emergency_contact VARCHAR(255),
            emergency_phone VARCHAR(50),
            bank_details TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_user (user_id),
            KEY idx_department (department),
            KEY idx_status (status),
            KEY idx_manager (manager_id)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}leave_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            employee_id BIGINT UNSIGNED NOT NULL,
            type ENUM('vacation', 'sick', 'personal', 'maternity', 'other') DEFAULT 'vacation',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            days_count DECIMAL(4,1) DEFAULT 0,
            reason TEXT,
            status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
            approved_by BIGINT UNSIGNED,
            approved_at DATETIME,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_employee (employee_id),
            KEY idx_status (status),
            KEY idx_dates (start_date, end_date)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}projects (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            client_id BIGINT UNSIGNED,
            status ENUM('planning', 'active', 'in_progress', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            start_date DATE,
            end_date DATE,
            budget DECIMAL(15,2) DEFAULT 0,
            spent DECIMAL(15,2) DEFAULT 0,
            progress INT DEFAULT 0,
            manager_id BIGINT UNSIGNED,
            color VARCHAR(20),
            tags TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_status (status),
            KEY idx_client (client_id),
            KEY idx_manager (manager_id)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            project_id BIGINT UNSIGNED,
            parent_id BIGINT UNSIGNED,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('todo', 'in_progress', 'review', 'done') DEFAULT 'todo',
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            assigned_to BIGINT UNSIGNED,
            start_date DATE,
            due_date DATE,
            completed_at DATETIME,
            estimated_hours DECIMAL(6,2) DEFAULT 0,
            actual_hours DECIMAL(6,2) DEFAULT 0,
            labels TEXT,
            position INT DEFAULT 0,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_project (project_id),
            KEY idx_status (status),
            KEY idx_assigned (assigned_to),
            KEY idx_parent (parent_id)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}time_entries (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            task_id BIGINT UNSIGNED,
            project_id BIGINT UNSIGNED,
            user_id BIGINT UNSIGNED NOT NULL,
            description VARCHAR(500),
            hours DECIMAL(6,2) NOT NULL,
            date DATE NOT NULL,
            billable TINYINT(1) DEFAULT 1,
            rate DECIMAL(10,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_task (task_id),
            KEY idx_project (project_id),
            KEY idx_user (user_id),
            KEY idx_date (date)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}products (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            sku VARCHAR(100),
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            unit VARCHAR(50) DEFAULT 'unit',
            price DECIMAL(15,2) DEFAULT 0,
            cost DECIMAL(15,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 20.00,
            quantity INT DEFAULT 0,
            min_quantity INT DEFAULT 0,
            max_quantity INT DEFAULT 0,
            location VARCHAR(200),
            supplier_id BIGINT UNSIGNED,
            barcode VARCHAR(100),
            image_url VARCHAR(500),
            status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
            weight DECIMAL(10,3),
            dimensions VARCHAR(100),
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_sku (sku),
            KEY idx_category (category),
            KEY idx_status (status),
            KEY idx_supplier (supplier_id)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}stock_movements (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            type ENUM('in', 'out', 'adjustment', 'return') NOT NULL,
            quantity INT NOT NULL,
            reference VARCHAR(200),
            notes TEXT,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_product (product_id),
            KEY idx_type (type),
            KEY idx_date (created_at)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}purchase_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            supplier_id BIGINT UNSIGNED,
            order_number VARCHAR(50) NOT NULL,
            status ENUM('draft', 'sent', 'confirmed', 'received', 'cancelled') DEFAULT 'draft',
            order_date DATE,
            expected_date DATE,
            received_date DATE,
            subtotal DECIMAL(15,2) DEFAULT 0,
            tax_amount DECIMAL(15,2) DEFAULT 0,
            total_amount DECIMAL(15,2) DEFAULT 0,
            notes TEXT,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_supplier (supplier_id),
            KEY idx_status (status)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255),
            message TEXT NOT NULL,
            link VARCHAR(500),
            is_read TINYINT(1) DEFAULT 0,
            read_at DATETIME,
            data LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_read (user_id, is_read),
            KEY idx_company (company_id),
            KEY idx_created (created_at)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}audit_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50),
            entity_id BIGINT UNSIGNED,
            description TEXT,
            old_values LONGTEXT,
            new_values LONGTEXT,
            ip_address VARCHAR(45),
            user_agent VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_user (user_id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_action (action),
            KEY idx_created (created_at)
        ) $charset;";

        $tables[] = "CREATE TABLE {$prefix}automations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            trigger_type VARCHAR(100) NOT NULL,
            trigger_config LONGTEXT,
            action_type VARCHAR(100) NOT NULL,
            action_config LONGTEXT,
            is_active TINYINT(1) DEFAULT 1,
            last_run DATETIME,
            run_count INT DEFAULT 0,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id),
            KEY idx_active (is_active),
            KEY idx_trigger (trigger_type)
        ) $charset;";

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        update_option('saasphere_db_version', SAASPHERE_ERP_VERSION);
    }

    public static function seed_demo_data() {
        if (get_option('saasphere_demo_seeded')) return;

        global $wpdb;
        $prefix = $wpdb->prefix . 'saasphere_';
        $user_id = get_current_user_id() ?: 1;

        $wpdb->insert($prefix . 'companies', [
            'name'       => 'Ma Entreprise',
            'slug'       => 'ma-entreprise',
            'email'      => get_option('admin_email'),
            'currency'   => 'EUR',
            'plan'       => 'enterprise',
            'is_active'  => 1,
            'created_by' => $user_id,
        ]);
        $company_id = $wpdb->insert_id;

        update_user_meta($user_id, 'saasphere_company_id', $company_id);
        update_user_meta($user_id, 'saasphere_role', 'super_admin');

        $contacts = [
            ['client', 'Marie', 'Dupont', 'marie@example.com', 'Tech Solutions', 85],
            ['client', 'Pierre', 'Martin', 'pierre@example.com', 'Digital Agency', 72],
            ['prospect', 'Sophie', 'Bernard', 'sophie@example.com', 'StartUp Inc', 45],
            ['lead', 'Lucas', 'Thomas', 'lucas@example.com', 'Innovation Lab', 30],
            ['client', 'Emma', 'Robert', 'emma@example.com', 'Design Studio', 90],
        ];

        foreach ($contacts as $c) {
            $wpdb->insert($prefix . 'contacts', [
                'company_id' => $company_id, 'type' => $c[0], 'first_name' => $c[1],
                'last_name' => $c[2], 'email' => $c[3], 'organization' => $c[4],
                'score' => $c[5], 'status' => 'active', 'assigned_to' => $user_id,
            ]);
        }

        $deals = [
            ['Refonte site web', 15000, 'qualified'],
            ['Application mobile', 45000, 'proposal'],
            ['Consulting IT', 8000, 'prospect'],
            ['Migration cloud', 32000, 'negotiation'],
            ['Formation equipe', 5000, 'won'],
        ];

        foreach ($deals as $i => $d) {
            $wpdb->insert($prefix . 'deals', [
                'company_id' => $company_id, 'contact_id' => $i + 1, 'title' => $d[0],
                'amount' => $d[1], 'stage' => $d[2], 'assigned_to' => $user_id,
                'expected_close_date' => date('Y-m-d', strtotime('+' . ($i + 1) . ' months')),
            ]);
        }

        for ($m = 1; $m <= 6; $m++) {
            $num = str_pad($m, 4, '0', STR_PAD_LEFT);
            $total = rand(2000, 15000);
            $wpdb->insert($prefix . 'invoices', [
                'company_id' => $company_id, 'contact_id' => ($m % 5) + 1,
                'invoice_number' => "FAC-2024-{$num}", 'type' => 'invoice',
                'status' => $m <= 4 ? 'paid' : 'pending',
                'issue_date' => "2024-{$m}-01", 'due_date' => "2024-{$m}-30",
                'paid_date' => $m <= 4 ? "2024-{$m}-15" : null,
                'total_amount' => $total, 'amount_paid' => $m <= 4 ? $total : 0,
                'tax_rate' => 20, 'created_by' => $user_id,
            ]);
        }

        $employees = [
            ['Jean', 'Durand', 'Développement', 'Développeur Senior', 4500],
            ['Claire', 'Moreau', 'Design', 'UI/UX Designer', 3800],
            ['Alexandre', 'Petit', 'Marketing', 'Chef de projet', 4200],
            ['Julie', 'Leroy', 'Commercial', 'Business Developer', 3500],
            ['Thomas', 'Roux', 'Développement', 'DevOps', 4800],
        ];

        foreach ($employees as $e) {
            $wpdb->insert($prefix . 'employees', [
                'company_id' => $company_id, 'first_name' => $e[0], 'last_name' => $e[1],
                'department' => $e[2], 'position' => $e[3], 'salary' => $e[4],
                'hire_date' => date('Y-m-d', strtotime('-' . rand(1, 36) . ' months')),
                'status' => 'active', 'contract_type' => 'cdi',
            ]);
        }

        $projects = [
            ['Refonte E-commerce', 'active', 'high', 65],
            ['Application Mobile V2', 'in_progress', 'urgent', 40],
            ['Migration Cloud AWS', 'planning', 'medium', 10],
            ['Redesign Branding', 'completed', 'low', 100],
        ];

        foreach ($projects as $i => $p) {
            $wpdb->insert($prefix . 'projects', [
                'company_id' => $company_id, 'name' => $p[0], 'status' => $p[1],
                'priority' => $p[2], 'progress' => $p[3], 'manager_id' => $user_id,
                'start_date' => date('Y-m-d', strtotime('-' . rand(1, 6) . ' months')),
                'end_date' => date('Y-m-d', strtotime('+' . rand(1, 6) . ' months')),
                'budget' => rand(10000, 80000),
            ]);
        }

        $tasks = [
            [1, 'Maquettes UI/UX', 'done'], [1, 'Integration frontend', 'in_progress'],
            [1, 'Backend API', 'in_progress'], [1, 'Tests unitaires', 'todo'],
            [2, 'Architecture technique', 'done'], [2, 'Ecrans principaux', 'in_progress'],
            [2, 'Push notifications', 'todo'], [2, 'Tests QA', 'todo'],
        ];

        foreach ($tasks as $t) {
            $wpdb->insert($prefix . 'tasks', [
                'company_id' => $company_id, 'project_id' => $t[0],
                'title' => $t[1], 'status' => $t[2], 'assigned_to' => $user_id,
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'due_date' => date('Y-m-d', strtotime('+' . rand(1, 30) . ' days')),
            ]);
        }

        $products = [
            ['Licence Pro', 'LIC-001', 'Licences', 99.99, 0, 500],
            ['Support Premium', 'SUP-001', 'Services', 49.99, 0, 200],
            ['Serveur Dédié', 'SRV-001', 'Infrastructure', 299.99, 150, 50],
            ['Formation en ligne', 'FRM-001', 'Formation', 199.99, 0, 100],
        ];

        foreach ($products as $p) {
            $wpdb->insert($prefix . 'products', [
                'company_id' => $company_id, 'name' => $p[0], 'sku' => $p[1],
                'category' => $p[2], 'price' => $p[3], 'cost' => $p[4],
                'quantity' => $p[5], 'min_quantity' => 10, 'status' => 'active',
            ]);
        }

        update_option('saasphere_demo_seeded', true);
    }
}
