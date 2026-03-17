(function() {
    'use strict';

    window.SaaSphereCharts = {
        createLineChart(canvasId, labels, datasets, options = {}) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(99,102,241,0.2)');
            gradient.addColorStop(1, 'rgba(99,102,241,0)');

            return new Chart(ctx, {
                type: 'line',
                data: { labels, datasets: datasets.map(ds => ({
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    ...ds
                }))},
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: { legend: { display: datasets.length > 1, position: 'top', align: 'end', labels: { usePointStyle: true, font: { size: 11 } } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
                        y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 11 }, color: '#94a3b8' } }
                    },
                    ...options
                }
            });
        },

        createBarChart(canvasId, labels, datasets, options = {}) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            return new Chart(ctx, {
                type: 'bar',
                data: { labels, datasets: datasets.map(ds => ({ borderRadius: 6, borderSkipped: false, ...ds }))},
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, font: { size: 11 } } } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: 'rgba(0,0,0,0.04)' } }
                    },
                    ...options
                }
            });
        },

        createDoughnutChart(canvasId, labels, data, colors, options = {}) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 8 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, font: { size: 11 } } } },
                    ...options
                }
            });
        },

        createRadarChart(canvasId, labels, datasets, options = {}) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            return new Chart(ctx, {
                type: 'radar',
                data: { labels, datasets: datasets.map(ds => ({ borderWidth: 2, pointRadius: 3, ...ds })) },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { r: { beginAtZero: true, ticks: { font: { size: 10 } } } },
                    ...options
                }
            });
        }
    };
})();
