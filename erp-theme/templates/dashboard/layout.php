<?php
defined('ABSPATH') || exit;
if (!is_user_logged_in()) { wp_redirect(home_url('/login')); exit; }

$module = get_query_var('saasphere_module', 'overview');
$action = get_query_var('saasphere_action', 'index');
$id = get_query_var('saasphere_id', 0);
$user = wp_get_current_user();
$dark_mode = get_user_meta($user->ID, 'saasphere_dark_mode', true);
$is_super_admin = SaaSphere_Roles::is_super_admin();
$unread_count = SaaSphere_Notifications::count_unread($user->ID);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SaaSphere</title>
    <?php wp_head(); ?>
</head>
<body class="ss-body <?php echo $dark_mode === 'dark' ? 'ss-dark' : ''; ?>">

<?php if ($is_super_admin): ?>
<div class="ss-super-admin-bar">
    <div class="ss-super-admin-bar__left">
        <span class="ss-super-admin-bar__badge">SUPER ADMIN</span>
        <div class="ss-super-admin-bar__client-switch">
            <span>Voir en tant que :</span>
            <select id="ss-company-switch" onchange="window.location.href='/dashboard/admin/switch-company/'+this.value">
                <?php
                $companies = SaaSphere_Company::get_all(['per_page' => 100]);
                $current_company = saasphere_get_company_id();
                foreach ($companies['items'] as $c): ?>
                    <option value="<?php echo $c->id; ?>" <?php selected($current_company, $c->id); ?>><?php echo esc_html($c->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="ss-super-admin-bar__right">
        <span><?php echo esc_html($user->display_name); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="ss-layout">
    <aside class="ss-sidebar" id="ss-sidebar">
        <div class="ss-sidebar__brand">
            <div class="ss-sidebar__logo">S</div>
            <span class="ss-sidebar__brand-text">SaaSphere</span>
        </div>

        <nav class="ss-sidebar__nav">
            <div class="ss-sidebar__section">
                <span class="ss-sidebar__section-label">Principal</span>
                <a href="/dashboard" class="ss-sidebar__nav-item <?php echo $module === 'overview' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span class="ss-sidebar__nav-text">Tableau de bord</span>
                </a>
                <a href="/dashboard/analytics" class="ss-sidebar__nav-item <?php echo $module === 'analytics' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span class="ss-sidebar__nav-text">Analytics</span>
                </a>
            </div>

            <div class="ss-sidebar__section">
                <span class="ss-sidebar__section-label">Gestion</span>
                <a href="/dashboard/crm" class="ss-sidebar__nav-item <?php echo $module === 'crm' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span class="ss-sidebar__nav-text">CRM</span>
                </a>
                <a href="/dashboard/finance" class="ss-sidebar__nav-item <?php echo $module === 'finance' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span class="ss-sidebar__nav-text">Finance</span>
                </a>
                <a href="/dashboard/hr" class="ss-sidebar__nav-item <?php echo $module === 'hr' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    <span class="ss-sidebar__nav-text">Ressources Humaines</span>
                </a>
                <a href="/dashboard/projects" class="ss-sidebar__nav-item <?php echo $module === 'projects' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span class="ss-sidebar__nav-text">Projets</span>
                </a>
                <a href="/dashboard/inventory" class="ss-sidebar__nav-item <?php echo $module === 'inventory' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27,6.96 12,12.01 20.73,6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <span class="ss-sidebar__nav-text">Inventaire</span>
                </a>
            </div>

            <div class="ss-sidebar__section">
                <span class="ss-sidebar__section-label">Outils</span>
                <a href="/dashboard/automation" class="ss-sidebar__nav-item <?php echo $module === 'automation' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,3 21,3 21,8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21,16 21,21 16,21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                    <span class="ss-sidebar__nav-text">Automatisation</span>
                </a>
                <a href="/dashboard/reports" class="ss-sidebar__nav-item <?php echo $module === 'reports' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span class="ss-sidebar__nav-text">Rapports</span>
                </a>
                <a href="/dashboard/ai-assistant" class="ss-sidebar__nav-item <?php echo $module === 'ai-assistant' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2H8V6a4 4 0 0 1 4-4z"/><rect x="4" y="8" width="16" height="10" rx="2"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/></svg>
                    <span class="ss-sidebar__nav-text">Assistant IA</span>
                </a>
            </div>

            <?php if ($is_super_admin): ?>
            <div class="ss-sidebar__section">
                <span class="ss-sidebar__section-label">Administration</span>
                <a href="/dashboard/admin" class="ss-sidebar__nav-item <?php echo $module === 'admin' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span class="ss-sidebar__nav-text">Super Admin</span>
                    <span class="ss-sidebar__nav-badge">!</span>
                </a>
                <a href="/dashboard/clients" class="ss-sidebar__nav-item <?php echo $module === 'clients' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                    <span class="ss-sidebar__nav-text">Comptes clients</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="ss-sidebar__section">
                <span class="ss-sidebar__section-label">Compte</span>
                <a href="/dashboard/settings" class="ss-sidebar__nav-item <?php echo $module === 'settings' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span class="ss-sidebar__nav-text">Parametres</span>
                </a>
                <a href="/dashboard/profile" class="ss-sidebar__nav-item <?php echo $module === 'profile' ? 'active' : ''; ?>">
                    <svg class="ss-sidebar__nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="ss-sidebar__nav-text">Profil</span>
                </a>
            </div>
        </nav>

        <div class="ss-sidebar__user">
            <?php echo saasphere_user_avatar($user->ID, 36); ?>
            <div class="ss-sidebar__user-info">
                <div class="ss-sidebar__user-name"><?php echo esc_html($user->display_name); ?></div>
                <div class="ss-sidebar__user-role"><?php echo esc_html(ucfirst(SaaSphere_Roles::get_user_role())); ?></div>
            </div>
        </div>
    </aside>

    <div class="ss-mobile-overlay" id="ss-mobile-overlay"></div>

    <main class="ss-main">
        <header class="ss-header">
            <div class="ss-header__left">
                <button class="ss-header__toggle" id="ss-sidebar-toggle">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="ss-header__breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"/></svg>
                    <span class="current"><?php echo esc_html(ucfirst($module)); ?></span>
                </div>
            </div>

            <div class="ss-header__right">
                <div class="ss-search">
                    <svg class="ss-search__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" class="ss-search__input" placeholder="Rechercher...">
                    <span class="ss-search__shortcut">Ctrl+K</span>
                    <div class="ss-search__results"></div>
                </div>

                <button class="ss-header-btn" data-toggle-dark title="Mode sombre">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                <button class="ss-header-btn" data-open-notifications>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($unread_count > 0): ?><span class="ss-header-btn__badge"></span><?php endif; ?>
                </button>

                <div class="ss-dropdown">
                    <button class="ss-header-btn" data-dropdown="ss-user-menu">
                        <?php echo saasphere_user_avatar($user->ID, 32); ?>
                    </button>
                    <div class="ss-dropdown__menu" id="ss-user-menu">
                        <a href="/dashboard/profile" class="ss-dropdown__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Mon profil
                        </a>
                        <a href="/dashboard/settings" class="ss-dropdown__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.82 1.18V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1.08-1.51"/></svg>
                            Parametres
                        </a>
                        <div class="ss-dropdown__separator"></div>
                        <a href="<?php echo wp_logout_url(home_url('/login')); ?>" class="ss-dropdown__item" style="color:var(--ss-danger)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Deconnexion
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="ss-content">
            <?php
            $template_file = SAASPHERE_THEME_DIR . '/templates/dashboard/' . $module . '.php';
            if (file_exists($template_file)) {
                include $template_file;
            } else {
                include SAASPHERE_THEME_DIR . '/templates/dashboard/overview.php';
            }
            ?>
        </div>
    </main>
</div>

<div class="ss-notification-panel" id="ss-notification-panel">
    <div class="ss-notification-panel__header">
        <h3>Notifications</h3>
        <button class="ss-btn ss-btn--ghost ss-btn--sm" onclick="SaaSphere.ajax('mark_all_notifications_read')">Tout marquer comme lu</button>
    </div>
    <div class="ss-notification-panel__list"></div>
</div>

<button class="ss-ai-fab" title="Assistant IA">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2H8V6a4 4 0 0 1 4-4z"/><rect x="4" y="8" width="16" height="10" rx="2"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/></svg>
</button>

<div class="ss-ai-chat" id="ss-ai-chat">
    <div class="ss-ai-chat__header">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2H8V6a4 4 0 0 1 4-4z"/><rect x="4" y="8" width="16" height="10" rx="2"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/></svg>
        <div>
            <div style="font-weight:700">Assistant IA</div>
            <div style="font-size:12px;opacity:0.8">Posez vos questions</div>
        </div>
        <button class="ss-ai-chat__close" style="margin-left:auto;background:none;border:none;color:white;cursor:pointer;font-size:18px">&times;</button>
    </div>
    <div class="ss-ai-chat__messages">
        <div class="ss-ai-chat__message ss-ai-chat__message--ai">Bonjour ! Je suis l'assistant IA de SaaSphere. Comment puis-je vous aider ?</div>
    </div>
    <div class="ss-ai-chat__input">
        <input type="text" placeholder="Tapez votre question...">
        <button class="ss-btn ss-btn--primary ss-btn--sm ss-ai-chat__send">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9"/></svg>
        </button>
    </div>
</div>

<div class="ss-toast-container"></div>

<?php wp_footer(); ?>
</body>
</html>
