<?php
defined('ABSPATH') || exit;
$company_id = saasphere_get_company_id();
$action = get_query_var('saasphere_action', 'index');
$id = get_query_var('saasphere_id', 0);

if ($action === 'pipeline') {
    $deals = SaaSphere_CRM::get_deals(['company_id' => $company_id]);
    $stages = ['prospect' => 'Prospects', 'qualified' => 'Qualifies', 'proposal' => 'Proposition', 'negotiation' => 'Negociation', 'won' => 'Gagnes', 'lost' => 'Perdus'];
    $stage_colors = ['prospect' => '#3b82f6', 'qualified' => '#f59e0b', 'proposal' => '#8b5cf6', 'negotiation' => '#f97316', 'won' => '#22c55e', 'lost' => '#ef4444'];
    $grouped = [];
    foreach ($stages as $key => $label) $grouped[$key] = [];
    foreach ($deals as $deal) $grouped[$deal->stage][] = $deal;
    ?>
    <div class="ss-page-header">
        <div>
            <h1 class="ss-page-header__title">Pipeline commercial</h1>
            <p class="ss-page-header__subtitle">Gerez vos opportunites de vente</p>
        </div>
        <div class="ss-page-header__actions">
            <a href="/dashboard/crm" class="ss-btn ss-btn--secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Contacts
            </a>
            <button class="ss-btn ss-btn--primary" data-modal="ss-deal-modal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nouveau deal
            </button>
        </div>
    </div>

    <div class="ss-pipeline">
        <?php foreach ($stages as $stage_key => $stage_label):
            $stage_deals = $grouped[$stage_key];
            $total = array_sum(array_column($stage_deals, 'amount'));
        ?>
        <div class="ss-pipeline__column">
            <div class="ss-pipeline__header ss-pipeline__header--<?php echo $stage_key; ?>">
                <div>
                    <span class="ss-pipeline__title"><?php echo esc_html($stage_label); ?></span>
                    <span class="ss-pipeline__count"><?php echo count($stage_deals); ?></span>
                </div>
                <span style="font-size:12px;font-weight:600;color:var(--ss-text-secondary)"><?php echo saasphere_format_currency($total); ?></span>
            </div>
            <div class="ss-pipeline__cards" data-status="<?php echo $stage_key; ?>">
                <?php foreach ($stage_deals as $deal): ?>
                <div class="ss-pipeline-card" data-id="<?php echo $deal->id; ?>">
                    <div class="ss-pipeline-card__name"><?php echo esc_html($deal->title); ?></div>
                    <div class="ss-pipeline-card__company"><?php echo esc_html($deal->contact_name ?: $deal->organization ?: '-'); ?></div>
                    <div class="ss-pipeline-card__footer">
                        <span class="ss-pipeline-card__amount"><?php echo saasphere_format_currency($deal->amount); ?></span>
                        <span class="ss-pipeline-card__date"><?php echo $deal->expected_close_date ? saasphere_format_date($deal->expected_close_date) : ''; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="ss-modal-overlay" id="ss-deal-modal">
        <div class="ss-modal">
            <div class="ss-modal__header">
                <h3 class="ss-modal__title">Nouveau deal</h3>
                <button class="ss-modal__close">&times;</button>
            </div>
            <form class="ss-modal__body" id="ss-deal-form">
                <div class="ss-form-group"><label class="ss-label">Titre</label><input type="text" name="title" class="ss-input" required></div>
                <div class="ss-grid ss-grid--2">
                    <div class="ss-form-group"><label class="ss-label">Montant</label><input type="number" name="amount" class="ss-input" step="0.01"></div>
                    <div class="ss-form-group"><label class="ss-label">Etape</label>
                        <select name="stage" class="ss-select">
                            <?php foreach ($stages as $k => $v): ?><option value="<?php echo $k; ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="ss-form-group"><label class="ss-label">Date cloture prevue</label><input type="date" name="expected_close_date" class="ss-input"></div>
                <div class="ss-form-group"><label class="ss-label">Notes</label><textarea name="notes" class="ss-textarea" rows="3"></textarea></div>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
                    <button type="button" class="ss-btn ss-btn--secondary ss-modal__close">Annuler</button>
                    <button type="submit" class="ss-btn ss-btn--primary">Creer</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('ss-deal-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'saasphere_crm_save_deal');
        fd.append('nonce', SaaSphereCfg.nonce);
        jQuery.post(SaaSphereCfg.ajaxUrl, Object.fromEntries(fd), function(r) {
            if (r.success) { SaaSphere.showToast('Deal cree avec succes', 'success'); location.reload(); }
            else SaaSphere.showToast('Erreur', 'error');
        });
    });
    </script>

<?php } else { 
    $page = absint($_GET['pg'] ?? 1);
    $type = sanitize_text_field($_GET['type'] ?? '');
    $search = sanitize_text_field($_GET['s'] ?? '');
    $contacts = SaaSphere_CRM::get_contacts(['company_id' => $company_id, 'type' => $type, 'search' => $search, 'page' => $page]);
    $pipeline_stats = SaaSphere_CRM::get_pipeline_stats($company_id);
    $total_pipeline = 0;
    foreach ($pipeline_stats as $s) $total_pipeline += $s->total;
?>
    <div class="ss-page-header">
        <div>
            <h1 class="ss-page-header__title">CRM</h1>
            <p class="ss-page-header__subtitle"><?php echo $contacts['total']; ?> contacts au total</p>
        </div>
        <div class="ss-page-header__actions">
            <a href="/dashboard/crm/pipeline" class="ss-btn ss-btn--secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Pipeline
            </a>
            <button class="ss-btn ss-btn--primary" data-modal="ss-contact-modal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nouveau contact
            </button>
        </div>
    </div>

    <div class="ss-grid ss-grid--4" style="margin-bottom:24px">
        <div class="ss-stat-card"><div class="ss-stat-card__value"><?php echo $contacts['total']; ?></div><div class="ss-stat-card__label">Total contacts</div></div>
        <div class="ss-stat-card"><div class="ss-stat-card__value"><?php echo isset($pipeline_stats['won']) ? $pipeline_stats['won']->count : 0; ?></div><div class="ss-stat-card__label">Deals gagnes</div></div>
        <div class="ss-stat-card"><div class="ss-stat-card__value"><?php echo saasphere_format_currency($total_pipeline); ?></div><div class="ss-stat-card__label">Valeur pipeline</div></div>
        <div class="ss-stat-card"><div class="ss-stat-card__value"><?php echo count($pipeline_stats); ?></div><div class="ss-stat-card__label">Etapes actives</div></div>
    </div>

    <div class="ss-card">
        <div class="ss-card__header">
            <div class="ss-filter-bar" style="margin:0;flex:1">
                <div class="ss-filter-bar__search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" placeholder="Rechercher un contact..." value="<?php echo esc_attr($search); ?>" id="ss-crm-search">
                </div>
                <select class="ss-select" style="width:auto" id="ss-crm-type-filter">
                    <option value="">Tous les types</option>
                    <option value="client" <?php selected($type, 'client'); ?>>Clients</option>
                    <option value="prospect" <?php selected($type, 'prospect'); ?>>Prospects</option>
                    <option value="lead" <?php selected($type, 'lead'); ?>>Leads</option>
                    <option value="supplier" <?php selected($type, 'supplier'); ?>>Fournisseurs</option>
                </select>
            </div>
        </div>
        <div class="ss-table-container">
            <table class="ss-table">
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Organisation</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Statut</th>
                        <th>Cree le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts['items'] as $contact): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <?php echo saasphere_user_avatar($contact->id, 34); ?>
                                <div>
                                    <div class="ss-table__cell-primary"><?php echo esc_html($contact->first_name . ' ' . $contact->last_name); ?></div>
                                    <div style="font-size:12px;color:var(--ss-text-muted)"><?php echo esc_html($contact->email); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo esc_html($contact->organization ?: '-'); ?></td>
                        <td><?php echo saasphere_get_status_badge($contact->type === 'client' ? 'active' : ($contact->type === 'prospect' ? 'pending' : 'draft')); ?></td>
                        <td>
                            <div class="ss-progress" style="width:60px">
                                <div class="ss-progress__bar" style="width:<?php echo $contact->score; ?>%"></div>
                            </div>
                            <span style="font-size:11px;color:var(--ss-text-muted)"><?php echo $contact->score; ?>%</span>
                        </td>
                        <td><?php echo saasphere_get_status_badge($contact->status); ?></td>
                        <td style="font-size:12px;color:var(--ss-text-muted)"><?php echo saasphere_format_date($contact->created_at); ?></td>
                        <td>
                            <div class="ss-dropdown">
                                <button class="ss-btn ss-btn--ghost ss-btn--sm ss-btn--icon" data-dropdown="ss-contact-actions-<?php echo $contact->id; ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                </button>
                                <div class="ss-dropdown__menu" id="ss-contact-actions-<?php echo $contact->id; ?>">
                                    <a href="/dashboard/crm/view/<?php echo $contact->id; ?>" class="ss-dropdown__item">Voir</a>
                                    <a href="/dashboard/crm/edit/<?php echo $contact->id; ?>" class="ss-dropdown__item">Modifier</a>
                                    <div class="ss-dropdown__separator"></div>
                                    <button class="ss-dropdown__item" style="color:var(--ss-danger)" onclick="SaaSphere.confirm('Supprimer ce contact ?', function(){ SaaSphere.ajax('crm_delete_contact', {id:<?php echo $contact->id; ?>}).done(function(){location.reload()}) })">Supprimer</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($contacts['total'] > 0): ?>
        <div class="ss-card__footer">
            <?php echo saasphere_pagination($contacts['total'], 20, $page); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="ss-modal-overlay" id="ss-contact-modal">
        <div class="ss-modal" style="max-width:640px">
            <div class="ss-modal__header">
                <h3 class="ss-modal__title">Nouveau contact</h3>
                <button class="ss-modal__close">&times;</button>
            </div>
            <form class="ss-modal__body" id="ss-contact-form">
                <div class="ss-grid ss-grid--2">
                    <div class="ss-form-group"><label class="ss-label">Prenom</label><input type="text" name="first_name" class="ss-input" required></div>
                    <div class="ss-form-group"><label class="ss-label">Nom</label><input type="text" name="last_name" class="ss-input" required></div>
                </div>
                <div class="ss-grid ss-grid--2">
                    <div class="ss-form-group"><label class="ss-label">Email</label><input type="email" name="email" class="ss-input"></div>
                    <div class="ss-form-group"><label class="ss-label">Telephone</label><input type="tel" name="phone" class="ss-input"></div>
                </div>
                <div class="ss-grid ss-grid--2">
                    <div class="ss-form-group"><label class="ss-label">Organisation</label><input type="text" name="organization" class="ss-input"></div>
                    <div class="ss-form-group"><label class="ss-label">Type</label>
                        <select name="type" class="ss-select">
                            <option value="client">Client</option>
                            <option value="prospect">Prospect</option>
                            <option value="lead">Lead</option>
                            <option value="supplier">Fournisseur</option>
                            <option value="partner">Partenaire</option>
                        </select>
                    </div>
                </div>
                <div class="ss-form-group"><label class="ss-label">Poste</label><input type="text" name="job_title" class="ss-input"></div>
                <div class="ss-form-group"><label class="ss-label">Notes</label><textarea name="notes" class="ss-textarea" rows="3"></textarea></div>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
                    <button type="button" class="ss-btn ss-btn--secondary ss-modal__close">Annuler</button>
                    <button type="submit" class="ss-btn ss-btn--primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('ss-contact-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'saasphere_crm_save_contact');
        fd.append('nonce', SaaSphereCfg.nonce);
        jQuery.post(SaaSphereCfg.ajaxUrl, Object.fromEntries(fd), function(r) {
            if (r.success) { SaaSphere.showToast('Contact cree avec succes', 'success'); location.reload(); }
            else SaaSphere.showToast('Erreur', 'error');
        });
    });
    document.getElementById('ss-crm-type-filter').addEventListener('change', function() {
        const url = new URL(window.location);
        url.searchParams.set('type', this.value);
        window.location.href = url;
    });
    let searchTimer;
    document.getElementById('ss-crm-search').addEventListener('input', function() {
        clearTimeout(searchTimer);
        const val = this.value;
        searchTimer = setTimeout(function() {
            const url = new URL(window.location);
            url.searchParams.set('s', val);
            window.location.href = url;
        }, 500);
    });
    </script>
<?php } ?>
