/**
 * Admin Dashboard Chart Logic
 */
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('groupedBarChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    
    // Parse data from data-attributes
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const usageData = JSON.parse(canvas.dataset.usage || '[]');
    const incomeData = JSON.parse(canvas.dataset.income || '[]');
    const expenseData = JSON.parse(canvas.dataset.expense || '[]');

    // Custom Plugin to draw values on top of bars
    const topLabelsPlugin = {
        id: 'topLabels',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
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
                        // Shorten money for display if large (e.g. 1M instead of 1.000.000)
                        if (dataValue >= 1000000) {
                            displayValue = (dataValue / 1000000).toFixed(1) + 'M';
                        } else if (dataValue >= 1000) {
                            displayValue = (dataValue / 1000).toFixed(0) + 'K';
                        } else {
                            displayValue = dataValue;
                        }
                    }

                    ctx.fillStyle = dataset.borderColor || '#333';
                    ctx.fillText(displayValue, bar.x, bar.y - 5);
                });
            });
            ctx.restore();
        }
    };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Số lượt sử dụng',
                    data: usageData,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: '#3498db',
                    borderWidth: 2,
                    yAxisID: 'yCount',
                    barPercentage: 0.8,
                    categoryPercentage: 0.7
                },
                {
                    label: 'Tổng tiền Thu',
                    data: incomeData,
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: '#2ecc71',
                    borderWidth: 2,
                    yAxisID: 'yMoney',
                    barPercentage: 0.8,
                    categoryPercentage: 0.7
                },
                {
                    label: 'Tổng tiền Chi',
                    data: expenseData,
                    backgroundColor: 'rgba(231, 76, 60, 0.2)',
                    borderColor: '#e74c3c',
                    borderWidth: 2,
                    yAxisID: 'yMoney',
                    barPercentage: 0.8,
                    categoryPercentage: 0.7
                }
            ]
        },
        plugins: [topLabelsPlugin],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 30 // Extra space for labels on top
                }
            },
            animation: {
                duration: 2500,
                easing: 'easeOutQuart',
                y: {
                    from: (ctx) => {
                        if (ctx.type === 'data') {
                            if (ctx.mode === 'default' && !ctx.active) {
                                return ctx.chart.scales.yMoney.getPixelForValue(0);
                            }
                        }
                    }
                }
            },
            scales: {
                yMoney: {
                    display: false // Hide fixed money scale
                },
                yCount: {
                    display: false // Hide fixed count scale
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { weight: 'bold' }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
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
});
