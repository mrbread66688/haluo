<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/User.php';

// 检查管理员权限
if (!isAdmin()) {
    redirect('login.php');
    exit;
}

// 生成CSRF令牌
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userModel = new User($pdo);

// 处理来源用户ID --------------------------------------------------
$fromUserId = filter_input(INPUT_GET, 'from_user_id', FILTER_VALIDATE_INT);

if (!$fromUserId || $fromUserId < 1) {
    $_SESSION['error_message'] = '无效的用户ID参数';
    redirect('users.php');
    exit;
}

// 获取来源用户信息 --------------------------------------------------
try {
    $fromUser = $userModel->getUserById($fromUserId);
    
    if (empty($fromUser) || !isset($fromUser['id'])) {
        throw new Exception("用户ID {$fromUserId} 不存在");
    }
} catch (Exception $e) {
    error_log("[".date('Y-m-d H:i:s')."] 用户查询失败: " . $e->getMessage());
    $_SESSION['error_message'] = '无法获取用户信息';
    redirect('users.php');
    exit;
}

// 处理表单提交 --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = '非法请求';
        redirect('users.php');
        exit;
    }

    $toUserId = filter_input(INPUT_POST, 'to_user_id', FILTER_VALIDATE_INT);
    $caseIds = isset($_POST['case_ids']) ? array_map('intval', $_POST['case_ids']) : [];

    try {
        // 验证接收用户
        if (!$toUserId || $toUserId < 1) {
            throw new InvalidArgumentException('请选择接收用户');
        }
        
        // 验证案件选择
        if (empty($caseIds)) {
            throw new InvalidArgumentException('请选择要转移的案件');
        }

        // 执行转移操作
        if ($userModel->transferCases($fromUserId, $toUserId, $caseIds)) {
            $_SESSION['success_message'] = sprintf(
                '成功转移 %d 个案件到 %s',
                count($caseIds),
                $userModel->getUsernameById($toUserId)
            );
        } else {
            throw new RuntimeException('案件转移操作失败');
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        $_SESSION['form_data'] = [
            'to_user_id' => $toUserId,
            'case_ids' => $caseIds
        ];
    }
    
    redirect($_SERVER['REQUEST_URI']);
    exit;
}

// 准备视图数据 --------------------------------------------------
$cases = $userModel->getUserAssignedCases($fromUserId);
$receivers = $userModel->getTransferrableUsers($_SESSION['user_id']);

// 设置页面标题
$title = sprintf(
    '案件转移 - %s (@%s)',
    htmlspecialchars($fromUser['real_name'] ?? '未知用户'),
    htmlspecialchars($fromUser['username'] ?? '未知账号')
);

require 'views/header.php';
?>

<div class="container-fluid py-4">
    <div class="card shadow-lg">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 font-weight-light">
                    <i class="fas fa-people-arrows mr-2"></i>
                    案件转移：<?= htmlspecialchars($fromUser['real_name'] ?? '未知用户') ?> 
                    <small class="font-weight-normal">
                        (@<?= htmlspecialchars($fromUser['username'] ?? '未知账号') ?>)
                    </small>
                </h4>
                <a href="users.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left mr-2"></i>返回
                </a>
            </div>
        </div>

        <div class="card-body px-0">
            <?php if (empty($cases)): ?>
                <div class="alert alert-info m-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    当前用户暂无负责案件
                </div>
            <?php else: ?>
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="row g-4">
                        <!-- 左侧控制面板 -->
                        <div class="col-lg-4 border-end">
                            <div class="p-4 sticky-top" style="top: 1rem;">
                                <div class="mb-4">
                                    <label class="form-label text-muted small mb-2">转移至</label>
                                    <select name="to_user_id" 
                                            class="form-select form-select-lg shadow-sm" 
                                            required
                                            data-searchable="true"
                                            title="选择接收用户">
                                        <option value="">选择接收用户...</option>
                                        <?php foreach ($receivers as $user): ?>
                                            <option value="<?= $user['id'] ?>" 
                                                <?= isset($_SESSION['form_data']['to_user_id']) && $_SESSION['form_data']['to_user_id'] == $user['id'] ? 'selected' : '' ?>
                                                data-avatar="<?= htmlspecialchars($user['avatar_url'] ?? 'default.png') ?>">
                                                <?= htmlspecialchars($user['real_name']) ?> 
                                                <small class="text-muted">@<?= $user['username'] ?></small>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" 
                                            class="btn btn-primary btn-lg shadow-sm"
                                            data-loader="true">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        转移选中案件
                                        <span class="badge bg-white text-primary ms-2"
                                              id="selectedCount">0</span>
                                    </button>
                                </div>

                                <div class="mt-3 text-center small text-muted">
                                    共 <?= count($cases) ?> 个可转移案件
                                </div>
                            </div>
                        </div>

                        <!-- 右侧案件列表 -->
                        <div class="col-lg-8">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" 
                                               id="selectAll" 
                                               class="form-check-input"
                                               style="transform: scale(1.5)"
                                               <?= isset($_SESSION['form_data']['case_ids']) && count($_SESSION['form_data']['case_ids']) === count($cases) ? 'checked' : '' ?>>
                                        <label class="form-check-label ms-2" for="selectAll">
                                            全选/全不选
                                        </label>
                                    </div>
                                    <div class="text-muted small">
                                        <span class="d-none d-md-inline">点击行快速选择</span>
                                    </div>
                                </div>

                                <div class="table-responsive rounded-3 shadow-sm">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 40px;"></th>
                                                <th>案件信息</th>
                                                <th class="d-none d-lg-table-cell">事故详情</th>
                                                <th>状态</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cases as $case): ?>
                                            <tr class="case-row position-relative <?= isset($_SESSION['form_data']['case_ids']) && in_array($case['id'], $_SESSION['form_data']['case_ids']) ? 'selected' : '' ?>">
                                                <td>
                                                    <input type="checkbox" 
                                                           name="case_ids[]" 
                                                           value="<?= $case['id'] ?>" 
                                                           class="form-check-input case-checkbox"
                                                           style="transform: scale(1.3)"
                                                           <?= isset($_SESSION['form_data']['case_ids']) && in_array($case['id'], $_SESSION['form_data']['case_ids']) ? 'checked' : '' ?>>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-primary">
                                                            <?= htmlspecialchars($case['case_number']) ?>
                                                        </span>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($case['insured_name']) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td class="d-none d-lg-table-cell">
                                                    <div class="text-nowrap">
                                                        <span class="badge bg-info">
                                                            <?= htmlspecialchars($case['accident_type']) ?>
                                                        </span>
                                                        <div class="text-success mt-1">
                                                            <?php if ($case['estimated_amount'] > 0): ?>
                                                                ¥<?= number_format($case['estimated_amount'], 2) ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">未填写</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= date('m/d H:i', strtotime($case['accident_time'])) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $statusClass = [
                                                            'pending' => 'warning',
                                                            'processing' => 'primary',
                                                            'completed' => 'success'
                                                        ][strtolower($case['case_status'])] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $statusClass ?> rounded-pill">
                                                        <?= htmlspecialchars($case['case_status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <?php unset($_SESSION['form_data']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 初始化选中数量
    function updateSelectedCount() {
        const count = $('.case-checkbox:checked').length;
        $('#selectedCount').text(count);
        $('#selectAll').prop('checked', count === $('.case-checkbox').length);
    }
    updateSelectedCount();

    // 行点击选择
    $('.case-row').on('click', function(e) {
        if (!$(e.target).is('input')) {
            const checkbox = $(this).find('.case-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });

    // 全选功能
    $('#selectAll').on('change', function() {
        $('.case-checkbox').prop('checked', this.checked).trigger('change');
    });

    // 单个选择更新
    $('.case-checkbox').on('change', function() {
        $(this).closest('tr').toggleClass('selected', this.checked);
        updateSelectedCount();
    });

    // 初始化选择框
    $('[data-searchable="true"]').select2({
        placeholder: "搜索用户...",
        minimumResultsForSearch: 3,
        templateResult: function(user) {
            if (!user.id) return user.text;
            const $elem = $(
                `<div class="d-flex align-items-center">
                    <img src="/avatars/${user.element.dataset.avatar}" 
                         class="rounded-circle me-3" 
                         width="30" 
                         height="30">
                    <div>
                        <div>${user.text}</div>
                        <small class="text-muted">@${user.element.dataset.username}</small>
                    </div>
                </div>`
            );
            return $elem;
        }
    });
});
</script>

<style>
.case-row {
    cursor: pointer;
    transition: all 0.2s;
}
.case-row:hover {
    background-color: #f8f9fa !important;
}
.case-row.selected {
    background-color: #e3f2fd !important;
    border-left: 3px solid #2196F3;
}
.table-hover tbody tr:hover {
    transform: translateX(2px);
}
.select2-container--default .select2-results__option--highlighted {
    background-color: #e3f2fd !important;
    color: #2196F3 !important;
}
</style>

<?php require 'views/footer.php'; ?>