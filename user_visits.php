<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/User.php';

if (!isAdmin()) {
    redirect('login.php');
}

$userModel = new User($pdo);
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    $_SESSION['error_message'] = '无效的用户ID';
    redirect('users.php');
}

$user = $userModel->getUserById($userId);
if (!$user) {
    $_SESSION['error_message'] = '用户不存在';
    redirect('users.php');
}

$reminders = $userModel->getCaseVisitReminders($userId);

$title = '用户回访提醒 - ' . htmlspecialchars($user['real_name']);
require 'views/header.php';
?>

<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-bell mr-2"></i><?= htmlspecialchars($user['real_name']) ?> 的案件回访提醒
            </h5>
            <div>
                <a href="users.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i>返回用户列表
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (empty($reminders)): ?>
                <div class="alert alert-success">
                    当前没有逾期未回访的案件。
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>案件ID</th>
                                <th>估损金额</th>
                                <th>受伤部位</th>
                                <th>上次回访</th>
                                <th>应回访间隔</th>
                                <th>已逾期天数</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reminders as $reminder): ?>
                            <tr class="<?= $reminder['overdue_days'] > 3 ? 'table-danger' : 'table-warning' ?>">
                                <td><?= $reminder['case_id'] ?></td>
                                <td><?= $reminder['estimated_amount'] ?>元</td>
                                <td><?= htmlspecialchars($reminder['injury_parts']) ?></td>
                                <td><?= $reminder['last_visit_date'] ?></td>
                                <td><?= $reminder['required_interval'] ?>个工作日</td>
                                <td><?= $reminder['overdue_days'] ?>天</td>
                                <td>
                                    <a href="case_detail.php?id=<?= $reminder['case_id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> 查看
                                    </a>
                                    <a href="case_track_add.php?case_id=<?= $reminder['case_id'] ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-phone"></i> 记录回访
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require 'views/footer.php'; ?>