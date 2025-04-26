<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/classes/CaseModel.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isAdmin()) {
    die('无权访问此页面');
}

$caseModel = new CaseModel($pdo);
$stats = $caseModel->getCaseStatistics();

$title = '案件统计分析';
require 'views/header.php';
?>

<style>
    .stat-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .stat-icon {
        font-size: 1.8rem;
        margin-right: 10px;
    }
    .stat-value {
        font-size: 1.8rem;
        font-weight: 600;
    }
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 20px;
    }
    .analysis-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }
    .section-title {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }
    .table th {
        background-color: #f1f5f9;
        font-weight: 600;
    }
    .badge-pill {
        padding: 5px 10px;
        margin-right: 8px;
        margin-bottom: 8px;
    }
    .progress {
        height: 24px;
        border-radius: 12px;
    }
    .progress-bar {
        line-height: 24px;
        font-size: 0.8rem;
    }
    /* 新增备注分析样式 */
    .remark-progress {
        height: 24px;
        border-radius: 12px;
        overflow: hidden;
    }
    .remark-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 15px;
    }
    .remark-legend-item {
        display: flex;
        align-items: center;
        font-size: 0.85rem;
    }
    .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 5px;
    }
</style>

<div class="container-fluid py-4">
    <!-- 统计概览卡片 -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file-alt stat-icon text-primary"></i>
                        <div>
                            <div class="stat-value text-primary"><?= number_format($stats['basic']['total_cases']) ?></div>
                            <div class="stat-label">总案件数</div>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small"><?= date('Y年m月') ?>数据</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card card border-left-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-yen-sign stat-icon text-success"></i>
                        <div>
                            <div class="stat-value text-success">¥<?= number_format($stats['basic']['total_paid'], 2) ?></div>
                            <div class="stat-label">总赔付金额</div>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        平均¥<?= ($stats['basic']['total_cases'] > 0) 
                            ? number_format($stats['basic']['total_paid']/$stats['basic']['total_cases'], 2)
                            : '0.00' ?>每件
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card card border-left-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-day stat-icon text-info"></i>
                        <div>
                            <div class="stat-value text-info"><?= round($stats['basic']['avg_process_days']) ?>天</div>
                            <div class="stat-label">平均处理周期</div>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        最短<?= $stats['basic']['min_process_days'] ?>天
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card card border-left-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-percentage stat-icon text-warning"></i>
                        <div>
                            <div class="stat-value text-warning">
                                <?= ($stats['basic']['total_estimated'] > 0)
                                    ? round(($stats['basic']['total_paid']/$stats['basic']['total_estimated'])*100)
                                    : 0 ?>%
                            </div>
                            <div class="stat-label">预估准确率</div>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        差异¥<?= number_format(abs($stats['basic']['total_estimated']-$stats['basic']['total_paid']), 2) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 案件状态分析 -->
    <div class="analysis-section">
        <h4 class="section-title"><i class="fas fa-chart-pie me-2"></i>案件状态分析</h4>
        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">状态分布</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <?php foreach($stats['status']['labels'] as $index => $label): ?>
                                <?php 
                                    $count = $stats['status']['datasets'][0]['data'][$index] ?? 0;
                                    $color = $stats['status']['datasets'][0]['backgroundColor'][$index] ?? '#CCCCCC';
                                ?>
                                <span class="badge badge-pill" style="background-color: <?= $color ?>">
                                    <?= htmlspecialchars($label) ?>: <?= number_format($count) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">状态趋势</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-map-marked-alt me-1"></i> 地区分布</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>省份</th>
                                        <th class="text-success">结案</th>
                                        <th class="text-warning">处理中</th>
                                        <th class="text-secondary">撤案</th>
                                        <th>合计</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stats['geo_distribution'] as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['province']) ?></td>
                                            <td><?= number_format($item['closed']) ?></td>
                                            <td><?= number_format($item['processing']) ?></td>
                                            <td><?= number_format($item['withdrawn']) ?></td>
                                            <td><strong><?= number_format($item['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-balance-scale me-1"></i> 责任类型</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>责任类型</th>
                                        <th class="text-success">结案</th>
                                        <th class="text-warning">处理中</th>
                                        <th class="text-secondary">撤案</th>
                                        <th>合计</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stats['liability_matrix'] as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['liability']) ?></td>
                                            <td><?= number_format($item['closed']) ?></td>
                                            <td><?= number_format($item['processing']) ?></td>
                                            <td><?= number_format($item['withdrawn']) ?></td>
                                            <td><strong><?= number_format($item['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 产品与金额分析 -->
    <div class="analysis-section">
        <h4 class="section-title"><i class="fas fa-boxes me-2"></i>产品与金额分析</h4>
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">产品类型分布</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="productChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">赔付金额分布</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>金额区间</th>
                                        <th>案件数量</th>
                                        <th>占比</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalAmountCases = array_sum($stats['amount_distribution']);
                                    foreach($stats['amount_distribution'] as $range => $count): 
                                        $percentage = $totalAmountCases > 0 ? round(($count/$totalAmountCases)*100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($range) ?></td>
                                            <td><?= number_format($count) ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-info" 
                                                         role="progressbar" 
                                                         style="width: <?= $percentage ?>%" 
                                                         aria-valuenow="<?= $percentage ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?= $percentage ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 时间与估损分析 -->
    <div class="analysis-section">
        <h4 class="section-title"><i class="fas fa-clock me-2"></i>时间与估损分析</h4>
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">每日案件分布 (近30天)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">处理中案件估损分析</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>金额区间</th>
                                        <th>案件数</th>
                                        <th>平均估损</th>
                                        <th>总估损</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stats['estimated_analysis'] as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['amount_range']) ?></td>
                                            <td><?= number_format($item['case_count']) ?></td>
                                            <td>¥<?= number_format($item['avg_amount'], 2) ?></td>
                                            <td class="font-weight-bold text-danger">¥<?= number_format($item['total_amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 新增备注类型分析 -->
    <div class="analysis-section">
        <h4 class="section-title"><i class="fas fa-comments me-2"></i> 备注类型分析</h4>
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">备注类型分布</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="remarkChart"></canvas>
                        </div>
                        <div class="remark-legend">
                            <?php foreach($stats['remark']['labels'] as $index => $label): ?>
                                <div class="remark-legend-item">
                                    <div class="legend-color" 
                                         style="background-color: <?= $stats['remark']['datasets'][0]['backgroundColor'][$index] ?>">
                                    </div>
                                    <?= htmlspecialchars($label) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list-ul me-1"></i> 详细数据</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>备注类型</th>
                                        <th>案件数量</th>
                                        <th>占比</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalRemark = array_sum($stats['remark']['datasets'][0]['data']);
                                    foreach ($stats['remark']['labels'] as $index => $label): 
                                        $count = $stats['remark']['datasets'][0]['data'][$index];
                                        $percentage = $totalRemark > 0 ? round(($count/$totalRemark)*100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($label) ?></td>
                                            <td><?= number_format($count) ?></td>
                                            <td>
                                                <div class="progress remark-progress">
                                                    <div class="progress-bar" 
                                                         role="progressbar" 
                                                         style="width: <?= $percentage ?>%; 
                                                                background-color: <?= $stats['remark']['datasets'][0]['backgroundColor'][$index] ?>">
                                                        <?= $percentage ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 将PHP统计数据转换为JavaScript对象
window.statsData = {
    status: <?= json_encode($stats['status'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    statusTrend: <?= json_encode($stats['statusTrend'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    product: <?= json_encode($stats['product'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    daily: <?= json_encode($stats['daily_distribution'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    remark: <?= json_encode($stats['remark'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};

// DOM加载完成后初始化图表
document.addEventListener('DOMContentLoaded', function() {
    // 1. 案件状态分布图
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && window.statsData.status) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: window.statsData.status,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? (context.raw / total * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.raw + '件 (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. 案件状态趋势图
    const statusTrendCtx = document.getElementById('statusTrendChart');
    if (statusTrendCtx && window.statsData.statusTrend) {
        new Chart(statusTrendCtx, {
            type: 'line',
            data: window.statsData.statusTrend,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest'
                }
            }
        });
    }

    // 3. 产品类型分布图
    const productCtx = document.getElementById('productChart');
    if (productCtx && window.statsData.product) {
        new Chart(productCtx, {
            type: 'doughnut',
            data: window.statsData.product,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? (context.raw / total * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.raw + '件 (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 4. 每日案件分布图
    const dailyCtx = document.getElementById('dailyChart');
    if (dailyCtx && window.statsData.daily) {
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: window.statsData.daily.map(item => item.day),
                datasets: [{
                    label: '案件数量',
                    data: window.statsData.daily.map(item => item.case_count),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '案件数量: ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }

    // 5. 备注分析图表
    const remarkCtx = document.getElementById('remarkChart');
    if (remarkCtx && window.statsData.remark) {
        new Chart(remarkCtx, {
            type: 'pie',
            data: window.statsData.remark,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 
                                    ? (context.raw / total * 100).toFixed(1)
                                    : 0;
                                return `${context.label}: ${context.raw}件 (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});

// 响应式调整
window.addEventListener('resize', function() {
    document.querySelectorAll('canvas').forEach(function(canvas) {
        if (canvas.chart) {
            canvas.chart.resize();
        }
    });
});
</script>

<?php require 'views/footer.php'; ?>