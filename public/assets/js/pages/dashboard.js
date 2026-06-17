function initDashboard() {
  // Destroy existing Chart instances before re-init (if Chart is available)
  if (typeof Chart !== 'undefined') {
    var existingCharts = Chart.instances;
    if (existingCharts) {
      Object.keys(existingCharts).forEach(function(k) {
        try { existingCharts[k].destroy(); } catch(e) {}
      });
    }
  }

    // --- Biểu đồ cột: Thu / Chi 6 tháng ---
    if (typeof Chart !== 'undefined') {
    var barCanvas = document.getElementById('userBarChart');
    if (barCanvas) {
        var months  = JSON.parse(barCanvas.dataset.months  || '[]');
        var income  = JSON.parse(barCanvas.dataset.income  || '[]');
        var expense = JSON.parse(barCanvas.dataset.expense || '[]');

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
                        backgroundColor: 'rgba(16, 185, 129, 0.18)',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                    {
                        label: 'Chi tiêu',
                        data: expense,
                        backgroundColor: 'rgba(239, 68, 68, 0.18)',
                        borderColor: '#ef4444',
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
                    duration: 1200,
                    easing: 'easeOutBack',
                    y: {
                        from: function (ctx) {
                            if (ctx.type === 'data' && ctx.mode === 'default') {
                                return ctx.chart.scales.y.getPixelForValue(0);
                            }
                        }
                    },
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
                        grid: { color: 'rgba(0,0,0,0.03)' },
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
                        labels: { boxWidth: 14, padding: 16, font: { weight: '600' } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.92)',
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
            var palette = ['#6366f1', '#f43f5e', '#10b981', '#f59e0b', '#ec4899', '#0ea5e9', '#8b7cf6', '#14b8a6', '#f97316', '#84cc16'];

            new Chart(pieCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: palette.slice(0, labels.length),
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverOffset: 16,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1500,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12, font: { weight: '600' } }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)',
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
  }

  // ── Cursor-based pagination: load more ──
  var dashTbody = document.getElementById('dashTbody');
  var dashLoadBtn = document.getElementById('dashBtnLoadMore');
  var dashLoadContainer = document.getElementById('dashLoadMoreContainer');
  var dashSpinner = document.getElementById('dashLoadMoreSpinner');
  var dashLastId = typeof window.dashLastId !== 'undefined' ? window.dashLastId : 0;
  var dashLoading = false;
  var dashHasMore = typeof window.dashHasMore !== 'undefined' ? window.dashHasMore : false;

  if (dashLoadContainer && !dashHasMore) {
    dashLoadContainer.style.display = 'none';
  }

  if (dashLoadBtn) {
    dashLoadBtn.addEventListener('click', function() {
      if (dashLoading || !dashHasMore) return;
      dashLoading = true;
      dashLoadBtn.style.display = 'none';
      if (dashSpinner) dashSpinner.style.display = 'inline';

      fetch('?template=user&action=dashboard&ajax=1&last_id=' + dashLastId)
        .then(function(r) { return r.json(); })
        .then(function(res) {
          dashLoading = false;
          if (dashSpinner) dashSpinner.style.display = 'none';

          if (!res.success) {
            dashLoadBtn.textContent = 'Lỗi tải dữ liệu';
            dashLoadBtn.style.display = 'inline';
            return;
          }

          var data = res.data;
          if (data.count === 0) {
            dashHasMore = false;
            if (dashLoadContainer) dashLoadContainer.style.display = 'none';
            return;
          }

          var emptyRow = document.getElementById('dashEmptyRow');
          if (emptyRow) emptyRow.remove();

          if (dashTbody) dashTbody.insertAdjacentHTML('beforeend', data.rows);

          dashLastId = data.next_last_id;
          dashHasMore = data.has_more;

          if (dashHasMore) {
            dashLoadBtn.style.display = 'inline';
          } else {
            if (dashLoadContainer) dashLoadContainer.style.display = 'none';
          }
        })
        .catch(function() {
          dashLoading = false;
          if (dashSpinner) dashSpinner.style.display = 'none';
          dashLoadBtn.textContent = 'Lỗi, thử lại';
          dashLoadBtn.style.display = 'inline';
        });
    });
  }

  // ── Budget AJAX month/year filter ──
  var bForm = document.getElementById('budgetFilterForm');
  if (bForm) {
    var bMonth = bForm.querySelector('[name="budget_month"]');
    var bYear = bForm.querySelector('[name="budget_year"]');
    if (bMonth) bMonth.addEventListener('change', loadBudget);
    if (bYear) bYear.addEventListener('change', loadBudget);
  }
}

function loadBudget() {
  var f = document.getElementById('budgetFilterForm');
  var c = document.getElementById('budgetContent');
  if (!f || !c) return;
  var fd = new FormData(f);
  var qs = new URLSearchParams(fd).toString() + '&ajax=budget';
  c.innerHTML = '<div style="text-align:center;padding:20px;color:#999;">Đang tải...</div>';
  fetch('?' + qs)
    .then(function(r) { return r.json(); })
    .then(function(res) { if (res.success && res.data.html) c.innerHTML = res.data.html; })
    .catch(function() { window.location.href = '?' + qs; });
}

document.addEventListener('DOMContentLoaded', initDashboard);
document.addEventListener('page-loaded', initDashboard);
