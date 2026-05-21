/**
 * User Dashboard Chart Logic
 */
document.addEventListener('DOMContentLoaded', function () {

    // --- Biểu đồ cột: Thu / Chi 6 tháng ---
    var barCanvas = document.getElementById('userBarChart');
    if (barCanvas) {
        var months  = JSON.parse(barCanvas.dataset.months  || '[]');
        var income  = JSON.parse(barCanvas.dataset.income  || '[]');
        var expense = JSON.parse(barCanvas.dataset.expense || '[]');

        // Plugin vẽ nhãn giá trị trên đầu mỗi cột
        var barTopLabels = {
            id: 'barTopLabels',
            afterDatasetsDraw: function (chart) {
                var ctx = chart.ctx;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.font = 'bold 11px Inter, system-ui, sans-serif';

                chart.data.datasets.forEach(function (dataset, i) {
                    var meta = chart.getDatasetMeta(i);
                    meta.data.forEach(function (bar, index) {
                        var val = dataset.data[index];
                        if (!val || val === 0) return;
                        var label = val >= 1000000
                            ? (val / 1000000).toFixed(1) + 'M'
                            : val >= 1000
                                ? (val / 1000).toFixed(0) + 'K'
                                : val;
                        ctx.fillStyle = dataset.borderColor || '#555';
                        ctx.fillText(label, bar.x, bar.y - 4);
                    });
                });
                ctx.restore();
            }
        };

        new Chart(barCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Thu nhập',
                        data: income,
                        backgroundColor: 'rgba(46, 204, 113, 0.22)',
                        borderColor: '#2ecc71',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                    {
                        label: 'Chi tiêu',
                        data: expense,
                        backgroundColor: 'rgba(231, 76, 60, 0.22)',
                        borderColor: '#e74c3c',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }
                ]
            },
            plugins: [barTopLabels],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 24 } },
                animation: {
                    duration: 1000,
                    easing: 'easeOutBounce',
                    // Mỗi cột mọc từ baseline lên
                    y: {
                        from: function (ctx) {
                            if (ctx.type === 'data' && ctx.mode === 'default') {
                                return ctx.chart.scales.y.getPixelForValue(0);
                            }
                        }
                    },
                    // Delay stagger: cột sau xuất hiện muộn hơn cột trước
                    delay: function (ctx) {
                        if (ctx.type === 'data' && ctx.mode === 'default') {
                            return ctx.dataIndex * 80 + ctx.datasetIndex * 40;
                        }
                        return 0;
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: '600' } }
                    },
                    y: {
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            callback: function (v) {
                                if (v >= 1000000) return (v / 1000000).toFixed(1) + 'M';
                                if (v >= 1000)    return (v / 1000).toFixed(0) + 'K';
                                return v;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 14, padding: 16 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.85)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                return ' ' + ctx.dataset.label + ': ' + ctx.raw.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                }
            }
        });
    }

    // --- Biểu đồ tròn: Chi tiêu theo danh mục ---
    var pieCanvas = document.getElementById('userPieChart');
    if (pieCanvas) {
        var labels = JSON.parse(pieCanvas.dataset.labels || '[]');
        var values = JSON.parse(pieCanvas.dataset.values || '[]');

        if (values.length === 0 || values.every(function (v) { return v === 0; })) {
            pieCanvas.parentElement.innerHTML =
                '<p style="text-align:center;color:#aaa;padding-top:80px;font-size:14px;">Chưa có dữ liệu chi tiêu</p>';
        } else {
            var palette = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];

            new Chart(pieCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: palette.slice(0, labels.length),
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverOffset: 14,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    // Xoay từ góc 0 vào, từng slice xuất hiện lần lượt
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1200,
                        easing: 'easeOutQuart',
                        delay: function (ctx) {
                            return ctx.dataIndex * 60;
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12 }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.85)',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (ctx) {
                                    var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                    var pct   = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                    return ' ' + ctx.label + ': ' + ctx.raw.toLocaleString('vi-VN') + ' đ (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
