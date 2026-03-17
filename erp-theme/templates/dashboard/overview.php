<?php
defined('ABSPATH') || exit;
$company_id = saasphere_get_company_id();
global $wpdb;
$prefix = $wpdb->prefix . 'saasphere_';

$month_start = date('Y-m-01');
$prev_month_start = date('Y-m-01', strtotime('-1 month'));
$prev_month_end = date('Y-m-t', strtotime('-1 month'));

$revenue = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_amount),0) FROM {$prefix}invoices WHERE company_id=%d AND status='paid' AND paid_date >= %s", $company_id, $month_start));
$prev_revenue = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_amount),0) FROM {$prefix}invoices WHERE company_id=%d AND status='paid' AND paid_date BETWEEN %s AND %s", $company_id, $prev_month_start, $prev_month_end));
$revenue_trend = $prev_revenue > 0 ? round((($revenue - $prev_revenue) / $prev_revenue) * 100, 1) : 0;

$total_clients = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}contacts WHERE company_id=%d AND type='client'", $company_id));
$active_projects = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}projects WHERE company_id=%d AND status IN ('active','in_progress')", $company_id));
$pending_amount = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_amount),0) FROM {$prefix}invoices WHERE company_id=%d AND status IN ('pending','overdue')", $company_id));

$recent_activities = $wpdb->get_results($wpdb->prepare("SELECT a.*, u.display_name as user_name FROM {$prefix}audit_log a LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID WHERE a.company_id=%d ORDER BY a.created_at DESC LIMIT 10", $company_id));
$recent_deals = $wpdb->get_results($wpdb->prepare("SELECT d.*, CONCAT(c.first_name,' ',c.last_name) as contact_name, c.organization FROM {$prefix}deals d LEFT JOIN {$prefix}contacts c ON d.contact_id=c.id WHERE d.company_id=%d ORDER BY d.updated_at DESC LIMIT 5", $company_id));
$upcoming_tasks = $wpdb->get_results($wpdb->prepare("SELECT t.*, p.name as project_name FROM {$prefix}tasks t LEFT JOIN {$prefix}projects p ON t.project_id=p.id WHERE t.company_id=%d AND t.status != 'done' ORDER BY t.due_date ASC LIMIT 5", $company_id));
?>

<div class="ss-dashboard-overview">
    <div class="ss-page-header">
        <div>
            <h1 class="ss-page-header__title">Tableau de bord</h1>
            <p class="ss-page-header__subtitle">Bienvenue, <?php echo esc_html(wp_get_current_user()->display_name); ?>. Voici un apercu de votre activite.</p>
        </div>
        <div class="ss-page-header__actions">
            <button class="ss-btn ss-btn--secondary" data-modal="ss-export-modal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exporter
            </button>
            <button class="ss-btn ss-btn--primary" onclick="window.location.href='/dashboard/crm/new'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nouveau contact
            </button>
        </div>
    </div>

    <div class="ss-grid ss-grid--4" style="margin-bottom:24px">
        <div class="ss-stat-card">
            <div class="ss-stat-card__header">
                <div class="ss-stat-card__icon ss-stat-card__icon--primary">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="ss-stat-card__trend ss-stat-card__trend--<?php echo $revenue_trend >= 0 ? 'up' : 'down'; ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="<?php echo $revenue_trend >= 0 ? '23,6 13.5,15.5 8.5,10.5 1,18' : '23,18 13.5,8.5 8.5,13.5 1,6'; ?>"/></svg>
                    <?php echo abs($revenue_trend); ?>%
                </div>
            </div>
            <div class="ss-stat-card__value ss-stat-revenue"><?php echo saasphere_format_currency($revenue); ?></div>
            <div class="ss-stat-card__label">Revenus du mois</div>
        </div>

        <div class="ss-stat-card">
            <div class="ss-stat-card__header">
                <div class="ss-stat-card__icon ss-stat-card__icon--success">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="ss-stat-card__trend ss-stat-card__trend--up">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/></svg>
                    +12%
                </div>
            </div>
            <div class="ss-stat-card__value ss-stat-clients"><?php echo number_format($total_clients); ?></div>
            <div class="ss-stat-card__label">Clients actifs</div>
        </div>

        <div class="ss-stat-card">
            <div class="ss-stat-card__header">
                <div class="ss-stat-card__icon ss-stat-card__icon--info">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
            </div>
            <div class="ss-stat-card__value ss-stat-projects"><?php echo $active_projects; ?></div>
            <div class="ss-stat-card__label">Projets actifs</div>
        </div>

        <div class="ss-stat-card">
            <div class="ss-stat-card__header">
                <div class="ss-stat-card__icon ss-stat-card__icon--warning">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                </div>
            </div>
            <div class="ss-stat-card__value ss-stat-pending"><?php echo saasphere_format_currency($pending_amount); ?></div>
            <div class="ss-stat-card__label">En attente de paiement</div>
        </div>
    </div>

    <div class="ss-grid ss-grid--sidebar" style="margin-bottom:24px">
        <div class="ss-card">
            <div class="ss-card__header">
                <h3 class="ss-card__title">Revenus mensuels</h3>
                <div style="display:flex;gap:8px">
                    <button class="ss-btn ss-btn--ghost ss-btn--sm active">Annee</button>
                    <button class="ss-btn ss-btn--ghost ss-btn--sm">Trimestre</button>
                </div>
            </div>
            <div class="ss-card__body">
                <div class="ss-widget-chart">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <div class="ss-card">
            <div class="ss-card__header">
                <h3 class="ss-card__title">Pipeline commercial</h3>
            </div>
            <div class="ss-card__body">
                <div class="ss-widget-chart" style="height:260px">
                    <canvas id="salesPipelineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="ss-grid ss-grid--sidebar" style="margin-bottom:24px">
        <div class="ss-card">
            <div class="ss-card__header">
                <h3 class="ss-card__title">Revenus vs Depenses</h3>
            </div>
            <div class="ss-card__body">
                <div class="ss-widget-chart">
                    <canvas id="expensesChart"></canvas>
                </div>
            </div>
        </div>

        <div class="ss-card">
            <div class="ss-card__header">
                <h3 class="ss-card__title">Taches a venir</h3>
                <a href="/dashboard/projects" class="ss-btn ss-btn--ghost ss-btn--sm">Voir tout</a>
            </div>
            <div class="ss-card__body" style="padding:0">
                <?php if (empty($upcoming_tasks)): ?>
                    <div class="ss-empty-state" style="padding:30px"><p style="color:var(--ss-text-muted)">Aucune tache a venir</p></div>
                <?php else: ?>
                    <?php foreach ($upcoming_tasks as $task): ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--ss-border-light)">
                        <div style="width:8px;height:8px;border-radius:50%;background:<?php echo $task->priority === 'urgent' ? 'var(--ss-danger)' : ($task->priority === 'high' ? 'var(--ss-warning)' : 'var(--ss-info)'); ?>"></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo esc_html($task->title); ?></div>
                            <div style="font-size:12px;color:var(--ss-text-muted)"><?php echo esc_html($task->project_name); ?></div>
                        </div>
                        <?php echo saasphere_get_status_badge($task->status); ?>
                        <span style="font-size:12px;color:var(--ss-text-muted)"><?php echo $task->due_date ? saasphere_format_date($task->due_date) : '-'; ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ss-grid ss-grid--2">
        <div class="ss-card">
            <div class="ss-card__header">
                <h3 class="ss-card__title">Derniers deals</h3>
                <a href="/dashboard/crm/pipeline" class="ss-btn ss-btn--ghost ss-btn--sm">Pipeline</a>
            </div>
            <div class="ss-card__body" style="padding:0">
                <?php if (empty($recent_deals)): ?>
                    <div class="ss-empty-state" style="padding:30px"><p style="color:var(--ss-text-muted)">Aucun deal</p></div>
                <?php else: ?>
                    <div class="ss-table-container">
                        <table class="ss-table">
                            <thead><tr><th>Deal</th><th>Montant</th><th>Etape</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_deals as $deal): ?>
                                <tr>
                                    <td>
                                        <div class="ss-table__cell-primary"><?php echo esc_html($deal->title); ?></div>
                                        <div style="font-size:12px;color:var(--ss-text-muted)"><?php echo esc_html($deal->contact_name); ?></div>
                                    </td>
                                    <td style="font-weight:600"><?php echo saasphere_format_currency($deal->amount); ?></td>
                                    <td><?php echo saasphere_get_status_badge($deal->stage); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="ss-card">
            <div class="ss-card__header">
                <h3 class="ss-card__title">Activite recente</h3>
            </div>
            <div class="ss-card__body">
                <div class="ss-activity-feed">
                    <?php if (empty($recent_activities)): ?>
                        <p style="color:var(--ss-text-muted);text-align:center;padding:20px">Aucune activite recente</p>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="ss-activity-item">
                            <div class="ss-activity-item__icon" style="background:var(--ss-primary-light);color:var(--ss-primary)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                            </div>
                            <div class="ss-activity-item__content">
                                <div class="ss-activity-item__title"><strong><?php echo esc_html($activity->user_name ?: 'Systeme'); ?></strong> <?php echo esc_html($activity->description); ?></div>
                                <div class="ss-activity-item__time"><?php echo saasphere_time_ago($activity->created_at); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
