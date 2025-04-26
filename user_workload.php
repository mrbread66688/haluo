<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/User.php';

if (!isAdmin()) {
    redirect('login.php');
}

$userModel = new User($pdo);
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$timeRange = isset($_GET['range']) ? $_GET['range'] : 'week';

// 验证用户ID
if ($userId <= 0) {
    $_SESSION['error_message'] = '无效的用户ID';
    redirect('users.php');
}

$user = $userModel->getUserById($userId);
if (!$user) {
    $_SESSION['error_message'] = '用户不存在';
    redirect('users.php');
}

// 获取统计数据
$workloadStats = $userModel->getUserWorkloadStats($userId, $timeRange);

$title = '用户工作量分析 - ' . htmlspecialchars($user['real_name']);
require 'views/header.php';
?>

<style>
.dashboard-header {
    background: linear-gradient(135deg, #4B79A1 0%, #283E51 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255,255,255,0.95);
    border-radius: 12px;
    transition: transform 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.time-range-btn.active {
    background: #4e73df !important;
    color: white !important;
    box-shadow: 0 2px 4px rgba(78,115,223,0.3);
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }
</style>

<div class="container-fluid">
    <!-- 头部 -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0"><?= htmlspecialchars($user['real_name']) ?></h1>
                <p class="mb-0">工作量综合分析报告</p>
            </div>
            <div class="btn-group">
                <a href="?id=<?= $userId ?>&range=day" 
                   class="btn btn-outline-light time-range-btn <?= $timeRange === 'day' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-day"></i> 日统计
                </a>
                <a href="?id=<?= $userId ?>&range=week" 
                   class="btn btn-outline-light time-range-btn <?= $timeRange === 'week' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-week"></i> 周统计
                </a>
                <a href="?id=<?= $userId ?>&range=month" 
                   class="btn btn-outline-light time-range-btn <?= $timeRange === 'month' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i> 月统计
                </a>
            </div>
        </div>
    </div>

    <!-- 统计卡片 -->
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col">
            <div class="stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">新建案件总数</h6>
                            <h2 class="text-primary mb-0"><?= array_sum(array_column($workloadStats, 'creations')) ?></h2>
                        </div>
                        <div class="bg-primary text-white rounded-circle p-3">
                            <i class="fas fa-file-medical fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 其他统计卡片类似结构 -->
    </div>

    <!-- 图表区 -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-line"></i> 工作量趋势分析</h5>
                <canvas id="workloadChart" height="150"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-pie"></i> 工作类型分布</h5>
                <canvas id="ratioChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- 数据表格 -->
    <div class="chart-container">
        <h5 class="mb-3"><i class="fas fa-table"></i> 详细数据</h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>日期</th>
                        <th>新建案件</th>
                        <th>案件跟踪</th>
                        <th>总工作量</th>
                        <th>趋势分析</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workloadStats as $stat): ?>
                    <tr>
                        <td><?= date('m/d', strtotime($stat['date'])) ?></td>
                        <td><?= $stat['creations'] ?></td>
                        <td><?= $stat['tracks'] ?></td>
                        <td class="fw-bold"><?= $stat['total'] ?></td>
                        <td>
                            <?php if($stat['trend'] === 'up'): ?>
                                <i class="fas fa-arrow-up trend-up"></i>
                                <span class="text-muted small">+<?= $stat['total'] - $stat['prev_total'] ?? 0 ?></span>
                            <?php elseif($stat['trend'] === 'down'): ?>
                                <i class="fas fa-arrow-down trend-down"></i>
                                <span class="text-muted small">-<?= ($stat['prev_total'] ?? 0) - $stat['total'] ?></span>
                            <?php else: ?>
                                <i class="fas fa-minus text-secondary"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 返回按钮 -->
    <div class="mt-4">
        <a href="users.php" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>返回用户列表
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 折线图配置
    const ctx = document.getElementById('workloadChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($workloadStats, 'date')) ?>,
            datasets: [{
                label: '新建案件',
                data: <?= json_encode(array_column($workloadStats, 'creations')) ?>,
                borderColor: '#4e73df',
                tension: 0.3,
                fill: false
            },{
                label: '案件跟踪',
                data: <?= json_encode(array_column($workloadStats, 'tracks')) ?>,
                borderColor: '#1cc88a',
                tension: 0.3,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 饼图配置
    const ratioCtx = document.getElementById('ratioChart').getContext('2d');
    new Chart(ratioCtx, {
        type: 'doughnut',
        data: {
            labels: ['新建案件', '案件跟踪'],
            datasets: [{
                data: [
                    <?= array_sum(array_column($workloadStats, 'creations')) ?>,
                    <?= array_sum(array_column($workloadStats, 'tracks')) ?>
                ],
                backgroundColor: ['#4e73df', '#1cc88a'],
                hoverOffset: 10
            }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { enabled: true }
            }
        }
    });
});
</script>

<?php require 'views/footer.php'; ?>