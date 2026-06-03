/**
 * Admin Dashboard Charts
 */
document.addEventListener('DOMContentLoaded', function() {
    const isAdmin = document.querySelector('.admin-sidebar') !== null;
    const adminColor = isAdmin ? 'rgba(245, 240, 235, 0.7)' : '#333';

    // ── Category Bar Chart ──
    const barCanvas = document.getElementById('groupedBarChart');
    if (barCanvas) {
        const labels = JSON.parse(barCanvas.dataset.labels || '[]');
        const usageData = JSON.parse(barCanvas.dataset.usage || '[]');
        const incomeData = JSON.parse(barCanvas.dataset.income || '[]');
        const expenseData = JSON.parse(barCanvas.dataset.expense || '[]');

        const topLabelsPlugin = {
            id: 'topLabels',
            afterDatasetsDraw(chart) {
                const ctx = chart.ctx;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.font = 'bold 11px Inter, system-ui, sans-serif';

                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    meta.data.forEach((bar, index) => {
                        const dataValue = dataset.data[index];
                        if (dataValue === 0) return;

                        let displayValue = '';
                        if (i === 0) {
                            displayValue = dataValue + ' lượt';
                        } else {
                            if (dataValue >= 1000000) {
                                displayValue = (dataValue / 1000000).toFixed(1) + 'M';
                            } else if (dataValue >= 1000) {
                                displayValue = (dataValue / 1000).toFixed(0) + 'K';
                            } else {
                                displayValue = dataValue;
                            }
                        }

                        ctx.fillStyle = adminColor;
                        ctx.fillText(displayValue, bar.x, bar.y - 5);
                    });
                });
                ctx.restore();
            }
        };

        new Chart(barCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Số lượt sử dụng',
                        data: usageData,
                        backgroundColor: 'rgba(212, 168, 67, 0.15)',
                        borderColor: '#d4a843',
                        borderWidth: 2,
                        yAxisID: 'yCount',
                        barPercentage: 0.85,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Tổng tiền Thu',
                        data: incomeData,
                        backgroundColor: 'rgba(46, 204, 113, 0.15)',
                        borderColor: '#2ecc71',
                        borderWidth: 2,
                        yAxisID: 'yMoney',
                        barPercentage: 0.85,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Tổng tiền Chi',
                        data: expenseData,
                        backgroundColor: 'rgba(231, 76, 60, 0.15)',
                        borderColor: '#e74c3c',
                        borderWidth: 2,
                        yAxisID: 'yMoney',
                        barPercentage: 0.85,
                        categoryPercentage: 0.75
                    }
                ]
            },
            plugins: [topLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 30 } },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart',
                    delay: function(ctx) {
                        return ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 80 : 0;
                    },
                    y: {
                        from: function(ctx) {
                            if (ctx.type === 'data' && ctx.mode === 'default' && !ctx.active) {
                                var scaleId = ctx.dataset.yAxisID;
                                if (ctx.chart.scales[scaleId]) {
                                    return ctx.chart.scales[scaleId].getPixelForValue(0);
                                }
                            }
                        }
                    }
                },
                scales: {
                    yMoney: { display: false },
                    yCount: { display: false },
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold', size: 12 }, color: 'rgba(245, 240, 235, 0.5)' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(13, 13, 13, 0.95)',
                        titleColor: '#d4a843',
                        padding: 12,
                        borderColor: 'rgba(212, 168, 67, 0.2)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (context.datasetIndex === 0) {
                                    return label + ': ' + context.raw + ' lần';
                                }
                                return label + ': ' + context.raw.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                }
            }
        });
    }

    // ── Trend Chart (line) ──
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        const tLabels = JSON.parse(trendCanvas.dataset.labels || '[]');
        const tIncome = JSON.parse(trendCanvas.dataset.income || '[]');
        const tExpense = JSON.parse(trendCanvas.dataset.expense || '[]');

        new Chart(trendCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: tLabels,
                datasets: [
                    {
                        label: 'Thu nhập',
                        data: tIncome,
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#2ecc71',
                        pointBorderColor: '#1a1512',
                        pointBorderWidth: 2,
                    },
                    {
                        label: 'Chi tiêu',
                        data: tExpense,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#e74c3c',
                        pointBorderColor: '#1a1512',
                        pointBorderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                animation: {
                    duration: 1800,
                    easing: 'easeOutQuart'
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(58, 50, 42, 0.3)' },
                        ticks: { font: { weight: 'bold', size: 11 }, color: 'rgba(245, 240, 235, 0.5)' }
                    },
                    y: {
                        grid: { color: 'rgba(58, 50, 42, 0.2)' },
                        ticks: {
                            font: { size: 11 },
                            color: 'rgba(245, 240, 235, 0.4)',
                            callback: function(v) {
                                if (v >= 1000000) return (v / 1000000).toFixed(1) + 'M';
                                if (v >= 1000) return (v / 1000).toFixed(0) + 'K';
                                return v;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 14, padding: 16, font: { weight: '600' }, color: 'rgba(245, 240, 235, 0.6)' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(13, 13, 13, 0.95)',
                        titleColor: '#d4a843',
                        padding: 12,
                        borderColor: 'rgba(212, 168, 67, 0.2)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.dataset.label + ': ' + ctx.raw.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                }
            }
        });
    }
});
