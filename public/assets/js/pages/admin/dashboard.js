/**
 * Admin Dashboard Charts
 */
document.addEventListener('DOMContentLoaded', function() {
    const isAdmin = document.querySelector('.admin-sidebar') !== null;
    const adminColor = isAdmin ? 'rgba(245, 240, 235, 0.7)' : '#333';

    // ── Category Horizontal Sub-Charts ──

    function barEndLabel(forceInside) {
        return {
            id: 'barEndLabel',
            afterDatasetsDraw: function(chart) {
                var ctx = chart.ctx;
                var rightEdge = chart.chartArea.right;
                ctx.save();
                chart.data.datasets.forEach(function(ds, dsIdx) {
                    var dsMeta = chart.getDatasetMeta(dsIdx);
                    dsMeta.data.forEach(function(bar, idx) {
                        var val = ds.data[idx];
                        if (val === 0) return;
                        var text;
                        if (chart.data.datasets.length === 1) {
                            text = val >= 1000 ? (val / 1000).toFixed(0) + 'K' : String(val);
                        } else {
                            text = val >= 1000000 ? (val / 1000000).toFixed(1) + 'M' :
                                   val >= 1000 ? (val / 1000).toFixed(0) + 'K' : String(val);
                        }
                        ctx.font = '700 12px Inter, system-ui, sans-serif';
                        ctx.textBaseline = 'middle';
                        var tw = ctx.measureText(text).width;
                        var gap = 6;
                        var inside = forceInside || (bar.x + gap + tw >= rightEdge);
                        ctx.textAlign = inside ? 'right' : 'left';
                        var x = inside ? bar.x - gap : bar.x + gap;
                        ctx.shadowColor = inside ? 'rgba(0,0,0,0.35)' : 'rgba(0,0,0,0.12)';
                        ctx.shadowBlur = 4;
                        ctx.shadowOffsetX = 1;
                        ctx.shadowOffsetY = 1;
                        if (chart.data.datasets.length === 1) {
                            ctx.fillStyle = inside ? '#faf8f5' : '#d4a843';
                        } else {
                            ctx.fillStyle = dsIdx === 0 ? '#2ecc71' : '#e74c3c';
                            if (inside) ctx.fillStyle = '#faf8f5';
                        }
                        ctx.fillText(text, x, bar.y);
                    });
                });
                ctx.restore();
            }
        };
    }

    var usageCanvas = document.getElementById('usageChart');
    if (usageCanvas) {
        var labels = JSON.parse(usageCanvas.dataset.labels || '[]');
        var values = JSON.parse(usageCanvas.dataset.values || '[]');
        new Chart(usageCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Số lượt sử dụng',
                    data: values,
                    backgroundColor: 'rgba(212, 168, 67, 0.82)',
                    borderColor: 'rgba(255, 210, 100, 0.3)',
                    borderWidth: 1,
                    borderRadius: 7,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.82
                }]
            },
            plugins: [barEndLabel(true)],
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1400,
                    easing: 'easeOutQuart',
                    delay: function(ctx) {
                        return ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 50 : 0;
                    },
                    x: {
                        from: function(ctx) {
                            if (ctx.type === 'data' && ctx.mode === 'default' && !ctx.active) {
                                var scale = ctx.chart.scales.x;
                                return scale ? scale.getPixelForValue(0) : 0;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { display: false }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { weight: '700', size: 11 },
                            color: 'rgba(245, 240, 235, 0.55)'
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(13,13,13,0.95)',
                        titleColor: '#d4a843',
                        padding: 10,
                        borderColor: 'rgba(212,168,67,0.2)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(ctx) {
                                return 'Số lượt sử dụng: ' + ctx.raw + ' lần';
                            }
                        }
                    }
                }
            }
        });
    }

    var moneyCanvas = document.getElementById('moneyChart');
    if (moneyCanvas) {
        var labels = JSON.parse(moneyCanvas.dataset.labels || '[]');
        var incomeData = JSON.parse(moneyCanvas.dataset.income || '[]');
        var expenseData = JSON.parse(moneyCanvas.dataset.expense || '[]');
        new Chart(moneyCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Thu nhập',
                    data: incomeData,
                    backgroundColor: 'rgba(46, 204, 113, 0.78)',
                    borderColor: 'rgba(46, 204, 113, 0.3)',
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false,
                    barPercentage: 0.35,
                    categoryPercentage: 0.75
                }, {
                    label: 'Chi tiêu',
                    data: expenseData,
                    backgroundColor: 'rgba(231, 76, 60, 0.78)',
                    borderColor: 'rgba(231, 76, 60, 0.3)',
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false,
                    barPercentage: 0.35,
                    categoryPercentage: 0.75
                }]
            },
            plugins: [barEndLabel()],
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1400,
                    easing: 'easeOutQuart',
                    delay: function(ctx) {
                        return ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 50 : 0;
                    },
                    x: {
                        from: function(ctx) {
                            if (ctx.type === 'data' && ctx.mode === 'default' && !ctx.active) {
                                var scale = ctx.chart.scales.x;
                                return scale ? scale.getPixelForValue(0) : 0;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { display: false }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { weight: '700', size: 11 },
                            color: 'rgba(245, 240, 235, 0.55)'
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(13,13,13,0.95)',
                        titleColor: '#d4a843',
                        padding: 10,
                        borderColor: 'rgba(212,168,67,0.2)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ' + ctx.raw.toLocaleString('vi-VN') + ' đ';
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
                    duration: 2200,
                    easing: 'easeOutQuart',
                    delay: function(ctx) {
                        return ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 120 : 0;
                    },
                    y: {
                        from: function(ctx) {
                            if (ctx.type === 'data' && ctx.mode === 'default' && !ctx.active) {
                                var scale = ctx.chart.scales.y;
                                return scale ? scale.getPixelForValue(0) : undefined;
                            }
                        }
                    }
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
