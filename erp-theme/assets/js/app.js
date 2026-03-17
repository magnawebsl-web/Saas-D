(function() {
    'use strict';

    window.SaaSphere = {
        init() {
            this.initSidebar();
            this.initDarkMode();
            this.initSearch();
            this.initDropdowns();
            this.initModals();
            this.initToasts();
            this.initNotifications();
            this.initTabs();
            this.initAIChat();
        },

        initSidebar() {
            const sidebar = document.querySelector('.ss-sidebar');
            const toggle = document.querySelector('.ss-header__toggle');
            const overlay = document.querySelector('.ss-mobile-overlay');

            if (toggle && sidebar) {
                toggle.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.toggle('mobile-open');
                    } else {
                        sidebar.classList.toggle('collapsed');
                        localStorage.setItem('ss_sidebar_collapsed', sidebar.classList.contains('collapsed'));
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('mobile-open');
                });
            }

            if (sidebar && localStorage.getItem('ss_sidebar_collapsed') === 'true' && window.innerWidth > 768) {
                sidebar.classList.add('collapsed');
            }

            document.querySelectorAll('.ss-sidebar__nav-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (this.dataset.href) {
                        window.location.href = this.dataset.href;
                    }
                });
            });
        },

        initDarkMode() {
            const saved = localStorage.getItem('ss_dark_mode');
            if (saved === 'true') {
                document.body.classList.add('ss-dark');
            }

            document.querySelectorAll('[data-toggle-dark]').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.body.classList.toggle('ss-dark');
                    const isDark = document.body.classList.contains('ss-dark');
                    localStorage.setItem('ss_dark_mode', isDark);

                    if (typeof jQuery !== 'undefined') {
                        jQuery.post(SaaSphereCfg.ajaxUrl, {
                            action: 'saasphere_toggle_dark_mode',
                            nonce: SaaSphereCfg.nonce,
                            mode: isDark ? 'dark' : 'light'
                        });
                    }
                });
            });
        },

        initSearch() {
            const input = document.querySelector('.ss-search__input');
            const results = document.querySelector('.ss-search__results');
            if (!input || !results) return;

            let debounceTimer;
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                if (query.length < 2) { results.classList.remove('active'); return; }

                debounceTimer = setTimeout(() => {
                    jQuery.post(SaaSphereCfg.ajaxUrl, {
                        action: 'saasphere_search',
                        nonce: SaaSphereCfg.nonce,
                        query: query
                    }, function(response) {
                        if (!response.success || !response.data.length) {
                            results.innerHTML = '<div class="ss-search__result-item" style="justify-content:center;color:var(--ss-text-muted)">Aucun r\u00e9sultat</div>';
                            results.classList.add('active');
                            return;
                        }
                        const icons = { contact: '\ud83d\udc64', invoice: '\ud83d\udcdd', project: '\ud83d\udcc1' };
                        const urls = {
                            contact: '/dashboard/crm/view/',
                            invoice: '/dashboard/finance/invoice/',
                            project: '/dashboard/projects/view/'
                        };
                        results.innerHTML = response.data.map(r =>
                            `<a href="${urls[r.type] || '#'}${r.id}" class="ss-search__result-item">
                                <span style="font-size:18px">${icons[r.type] || '\ud83d\udcce'}</span>
                                <div>
                                    <div style="font-weight:600;font-size:13px">${SaaSphere.esc(r.title)}</div>
                                    <div style="font-size:12px;color:var(--ss-text-muted)">${SaaSphere.esc(r.subtitle)}</div>
                                </div>
                            </a>`
                        ).join('');
                        results.classList.add('active');
                    });
                }, 300);
            });

            document.addEventListener('click', e => {
                if (!e.target.closest('.ss-search')) results.classList.remove('active');
            });

            document.addEventListener('keydown', e => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    input.focus();
                }
                if (e.key === 'Escape') {
                    results.classList.remove('active');
                    input.blur();
                }
            });
        },

        initDropdowns() {
            document.addEventListener('click', e => {
                const trigger = e.target.closest('[data-dropdown]');
                if (trigger) {
                    e.stopPropagation();
                    const menu = document.getElementById(trigger.dataset.dropdown);
                    document.querySelectorAll('.ss-dropdown__menu.active').forEach(m => {
                        if (m !== menu) m.classList.remove('active');
                    });
                    if (menu) menu.classList.toggle('active');
                    return;
                }
                if (!e.target.closest('.ss-dropdown__menu')) {
                    document.querySelectorAll('.ss-dropdown__menu.active').forEach(m => m.classList.remove('active'));
                }
            });
        },

        initModals() {
            document.addEventListener('click', e => {
                const trigger = e.target.closest('[data-modal]');
                if (trigger) {
                    const modal = document.getElementById(trigger.dataset.modal);
                    if (modal) modal.classList.add('active');
                }

                if (e.target.closest('.ss-modal__close') || e.target.classList.contains('ss-modal-overlay')) {
                    e.target.closest('.ss-modal-overlay').classList.remove('active');
                }
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.ss-modal-overlay.active').forEach(m => m.classList.remove('active'));
                }
            });
        },

        initToasts() {
            if (!document.querySelector('.ss-toast-container')) {
                const container = document.createElement('div');
                container.className = 'ss-toast-container';
                document.body.appendChild(container);
            }
        },

        showToast(message, type = 'info', duration = 4000) {
            const container = document.querySelector('.ss-toast-container');
            const toast = document.createElement('div');
            toast.className = `ss-toast ss-toast--${type}`;
            const icons = { success: '\u2705', error: '\u274c', warning: '\u26a0\ufe0f', info: '\u2139\ufe0f' };
            toast.innerHTML = `<span>${icons[type] || ''}</span><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        },

        initNotifications() {
            const panel = document.querySelector('.ss-notification-panel');
            const trigger = document.querySelector('[data-open-notifications]');
            if (!panel || !trigger) return;

            trigger.addEventListener('click', () => {
                panel.classList.toggle('open');
                if (panel.classList.contains('open')) this.loadNotifications();
            });

            document.addEventListener('click', e => {
                if (!e.target.closest('.ss-notification-panel') && !e.target.closest('[data-open-notifications]')) {
                    panel.classList.remove('open');
                }
            });
        },

        loadNotifications() {
            jQuery.post(SaaSphereCfg.ajaxUrl, {
                action: 'saasphere_get_notifications',
                nonce: SaaSphereCfg.nonce
            }, function(response) {
                if (!response.success) return;
                const list = document.querySelector('.ss-notification-panel__list');
                if (!list) return;
                if (!response.data.length) {
                    list.innerHTML = '<div class="ss-empty-state" style="padding:40px"><p>Aucune notification</p></div>';
                    return;
                }
                list.innerHTML = response.data.map(n =>
                    `<div class="ss-notification-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}">
                        <div class="ss-notification-item__icon" style="background:var(--ss-primary-light);color:var(--ss-primary)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </div>
                        <div class="ss-notification-item__content">
                            <div class="ss-notification-item__title">${n.message}</div>
                            <div class="ss-notification-item__time">${n.created_at}</div>
                        </div>
                    </div>`
                ).join('');
            });
        },

        initTabs() {
            document.addEventListener('click', e => {
                const tab = e.target.closest('.ss-tab');
                if (!tab) return;
                const container = tab.closest('.ss-tabs-container');
                if (!container) return;

                container.querySelectorAll('.ss-tab').forEach(t => t.classList.remove('active'));
                container.querySelectorAll('.ss-tab-panel').forEach(p => p.style.display = 'none');
                tab.classList.add('active');
                const panel = container.querySelector(`#${tab.dataset.tab}`);
                if (panel) panel.style.display = 'block';
            });
        },

        initAIChat() {
            const fab = document.querySelector('.ss-ai-fab');
            const chat = document.querySelector('.ss-ai-chat');
            if (!fab || !chat) return;

            fab.addEventListener('click', () => {
                const isOpen = chat.classList.toggle('open');
                fab.style.display = isOpen ? 'none' : 'flex';
            });

            const closeBtn = chat.querySelector('.ss-ai-chat__close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    chat.classList.remove('open');
                    fab.style.display = 'flex';
                });
            }

            const chatInput = chat.querySelector('.ss-ai-chat__input input');
            const sendBtn = chat.querySelector('.ss-ai-chat__send');
            const messages = chat.querySelector('.ss-ai-chat__messages');

            const sendMessage = () => {
                const text = chatInput.value.trim();
                if (!text) return;

                messages.innerHTML += `<div class="ss-ai-chat__message ss-ai-chat__message--user">${this.esc(text)}</div>`;
                chatInput.value = '';
                messages.scrollTop = messages.scrollHeight;

                messages.innerHTML += `<div class="ss-ai-chat__message ss-ai-chat__message--ai"><div class="ss-spinner" style="width:20px;height:20px;border-width:2px"></div></div>`;
                messages.scrollTop = messages.scrollHeight;

                jQuery.post(SaaSphereCfg.restUrl + 'ai/chat', {
                    message: text
                }, function(response) {
                    const loadingMsg = messages.querySelector('.ss-spinner');
                    if (loadingMsg) loadingMsg.closest('.ss-ai-chat__message').innerHTML = response.reply || 'Je suis l\'assistant IA SaaSphere. Cette fonctionnalit\u00e9 sera bient\u00f4t disponible.';
                    messages.scrollTop = messages.scrollHeight;
                }).fail(function() {
                    const loadingMsg = messages.querySelector('.ss-spinner');
                    if (loadingMsg) loadingMsg.closest('.ss-ai-chat__message').innerHTML = 'D\u00e9sol\u00e9, une erreur est survenue. Veuillez r\u00e9essayer.';
                });
            };

            if (chatInput) {
                chatInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });
            }
            if (sendBtn) sendBtn.addEventListener('click', sendMessage);
        },

        esc(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        confirm(message, onConfirm) {
            const overlay = document.createElement('div');
            overlay.className = 'ss-modal-overlay active';
            overlay.innerHTML = `
                <div class="ss-modal" style="max-width:420px">
                    <div class="ss-modal__header">
                        <h3 class="ss-modal__title">Confirmation</h3>
                        <button class="ss-modal__close">&times;</button>
                    </div>
                    <div class="ss-modal__body"><p>${message}</p></div>
                    <div class="ss-modal__footer">
                        <button class="ss-btn ss-btn--secondary ss-confirm-cancel">Annuler</button>
                        <button class="ss-btn ss-btn--danger ss-confirm-ok">Confirmer</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
            overlay.querySelector('.ss-confirm-ok').addEventListener('click', () => { onConfirm(); overlay.remove(); });
            overlay.querySelector('.ss-confirm-cancel').addEventListener('click', () => overlay.remove());
            overlay.querySelector('.ss-modal__close').addEventListener('click', () => overlay.remove());
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: SaaSphereCfg.currency || 'EUR' }).format(amount);
        },

        formatDate(dateStr) {
            return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(dateStr));
        },

        ajax(action, data = {}) {
            return jQuery.post(SaaSphereCfg.ajaxUrl, { action: 'saasphere_' + action, nonce: SaaSphereCfg.nonce, ...data });
        },

        rest(endpoint, method = 'GET', data = null) {
            return jQuery.ajax({
                url: SaaSphereCfg.restUrl + endpoint,
                method: method,
                data: data ? JSON.stringify(data) : null,
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', SaaSphereCfg.restNonce);
                }
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => SaaSphere.init());
})();
