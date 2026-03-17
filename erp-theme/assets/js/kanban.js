(function() {
    'use strict';

    window.SaaSphereKanban = {
        init(containerSelector, options = {}) {
            const container = document.querySelector(containerSelector);
            if (!container) return;

            const columns = container.querySelectorAll('.ss-kanban__cards, .ss-pipeline__cards');
            columns.forEach(col => {
                new Sortable(col, {
                    group: options.group || 'kanban',
                    animation: 200,
                    ghostClass: 'dragging',
                    dragClass: 'dragging',
                    onEnd: (evt) => {
                        const itemId = evt.item.dataset.id;
                        const newStatus = evt.to.dataset.status;
                        const newIndex = evt.newIndex;

                        if (options.onMove) {
                            options.onMove(itemId, newStatus, newIndex);
                        }

                        this.updateColumnCounts(container);
                    }
                });
            });
        },

        updateColumnCounts(container) {
            container.querySelectorAll('.ss-kanban__column, .ss-pipeline__column').forEach(col => {
                const count = col.querySelector('.ss-kanban__cards, .ss-pipeline__cards').children.length;
                const countEl = col.querySelector('.ss-kanban__count, .ss-pipeline__count');
                if (countEl) countEl.textContent = count;
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.ss-pipeline')) {
            SaaSphereKanban.init('.ss-pipeline', {
                group: 'pipeline',
                onMove(dealId, newStage, newIndex) {
                    SaaSphere.rest('crm/deals/' + dealId, 'PUT', { stage: newStage, position: newIndex })
                        .done(() => SaaSphere.showToast('Deal d\u00e9plac\u00e9 avec succ\u00e8s', 'success'))
                        .fail(() => SaaSphere.showToast('Erreur lors du d\u00e9placement', 'error'));
                }
            });
        }

        if (document.querySelector('.ss-kanban')) {
            SaaSphereKanban.init('.ss-kanban', {
                group: 'tasks',
                onMove(taskId, newStatus, newIndex) {
                    SaaSphere.rest('projects/tasks/' + taskId, 'PUT', { status: newStatus, position: newIndex })
                        .done(() => SaaSphere.showToast('T\u00e2che mise \u00e0 jour', 'success'))
                        .fail(() => SaaSphere.showToast('Erreur lors de la mise \u00e0 jour', 'error'));
                }
            });
        }
    });
})();
