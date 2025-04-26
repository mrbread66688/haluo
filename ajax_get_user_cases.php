<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/User.php';

if (!isAdmin()) {
    die(json_encode(['error' => '无权访问']));
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'view';

if ($userId <= 0) {
    die(json_encode(['error' => '无效的用户ID']));
}

$userModel = new User($pdo);
$cases = $userModel->getUserAssignedCases($userId);

if ($mode === 'view') {
    // 查看模式
    if (empty($cases)) {
        echo '<tr><td colspan="6" class="text-center">该用户暂无负责案件</td></tr>';
        exit;
    }
    
    foreach ($cases as $case) {
        echo '<tr>
            <td>'.htmlspecialchars($case['case_number']).'</td>
            <td>'.htmlspecialchars($case['insured_name']).'</td>
            <td>'.htmlspecialchars($case['accident_type']).'</td>
            <td>'.($case['estimated_amount'] ? number_format($case['estimated_amount'], 2) : '-').'</td>
            <td>'.(!empty($case['accident_time']) ? date('Y-m-d H:i', strtotime($case['accident_time'])) : '-').'</td>
            <td>'.htmlspecialchars($case['case_status']).'</td>
        </tr>';
    }
} else {
    // 转移模式
    if (empty($cases)) {
        echo '<div class="alert alert-warning mb-0">该用户暂无负责案件</div>';
        exit;
    }
    
    foreach ($cases as $case) {
        echo '<div class="form-check case-item">
            <input class="form-check-input case-checkbox" type="checkbox" 
                   name="case_ids[]" value="'.$case['id'].'" id="case_'.$case['id'].'">
            <label class="form-check-label d-block" for="case_'.$case['id'].'">
                <strong>'.htmlspecialchars($case['case_number']).'</strong> - 
                '.htmlspecialchars($case['insured_name']).'
                <div class="text-muted small mt-1">
                    <span class="mr-2">事故类型: '.htmlspecialchars($case['accident_type']).'</span>
                    <span class="mr-2">估损金额: '.($case['estimated_amount'] ? number_format($case['estimated_amount'], 2) : '-').'</span>
                    <span>状态: '.htmlspecialchars($case['case_status']).'</span>
                </div>
            </label>
        </div>';
    }
}
?>