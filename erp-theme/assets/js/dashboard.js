(function() {
    'use strict';

    window.SaaSphereDashboard = {
        charts: {},

        init() {
            this.loadStats();
            this.initRevenueChart();
            this.initSalesChart();
            this.initExpensesChart();
        },

        loadStats() {
            SaaSphere.ajax('dashboard_stats').done(response => {
                if (!response.success) return;
                const d = response.data;

                this.animateCounter('.ss-stat-revenue', d.revenue);
                this.animateCounter('.ss-stat-clients', d.new_clients, false);
                this.animateCounter('.ss-stat-projects', d.active_projects, false);
                this.animateCounter('.ss-stat-pending', d.pending_invoices);

                if (d.monthly_revenue && d.monthly_revenue.length) {
                    this.updateRevenueChart(d.monthly_revenue);
                }
            });
        },

        animateCounter(selector, target, isCurrency = true) {
            const el = document.querySelector(selector);
            if (!el) return;
            let current = 0;
            const step = target / 40;
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                el.textContent = isCurrency ? SaaSphere.formatCurrency(current) : Math.round(current).toLocaleString('fr-FR');
            }, 25);
        },

        initRevenueChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;

            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(99,102,241,0.3)');
            gradient.addColorStop(1, 'rgba(99,102,241,0)');

            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'F\u00e9v', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Ao\u00fb', 'Sep', 'Oct', 'Nov', 'D\u00e9c'],
                    datasets: [{
                        label: 'Revenus',
                        data: [0,0,0,0,0,0,0,0,0,0,0,0],
                        borderColor: '#6366f1',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 12, weight: '600' },
                            bodyFont: { size: 13 },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: ctx => SaaSphere.formatCurrency(ctx.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 }, color: '#94a3b8' }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                font: { size: 11 },
                                color: '#94a3b8',
                                callback: v => (v / 1000) + 'k'
                            }
                        }
                    }
                }
            });
        },

        updateRevenueChart(data) {
            if (!this.charts.revenue) return;
            const monthlyData = new Array(12).fill(0);
            data.forEach(item => { monthlyData[item.month - 1] = parseFloat(item.total); });
            this.charts.revenue.data.datasets[0].data = monthlyData;
            this.charts.revenue.update('none');
        },

        initSalesChart() {
            const ctx = document.getElementById('salesPipelineChart');
            if (!ctx) return;

            this.charts.sales = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Prospect', 'Qualifi\u00e9', 'Proposition', 'N\u00e9gociation', 'Gagn\u00e9'],
                    datasets: [{
                        data: [30, 25, 20, 15, 10],
                        backgroundColor: ['#3b82f6', '#f59e0b', '#8b5cf6', '#f97316', '#22c55e'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } }
                        }
                    }
                }
            });
        },

        initExpensesChart() {
            const ctx = document.getElementById('expensesChart');
            if (!ctx) return;

            this.charts.expenses = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'F\u00e9v', 'Mar', 'Avr', 'Mai', 'Jun'],
                    datasets: [
                        {
                            label: 'Revenus',
                            data: [45000, 52000, 49000, 61000, 55000, 67000],
                            backgroundColor: 'rgba(99,102,241,0.8)',
                            borderRadius: 6,
                            borderSkipped: false
                        },
                        {
                            label: 'D\u00e9penses',
                            data: [32000, 38000, 35000, 42000, 39000, 44000],
                            backgroundColor: 'rgba(244,63,94,0.8)',
                            borderRadius: 6,
                            borderSkipped: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { font: { size: 11 }, callback: v => (v / 1000) + 'k' }
                        }
                    }
                }
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.ss-dashboard-overview')) {
            SaaSphereDashboard.init();
        }
    });
})();
