<?php
require_once 'config.php';
require_once 'functions.php';

// 验证登录
if (!isLoggedIn()) {
    redirect('login.php');
}

// 初始化会话变量
if (!isset($_SESSION['is_admin'])) {
    $_SESSION['is_admin'] = false;
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 0;
}

// 获取案件ID
$caseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$caseId) {
    $_SESSION['error'] = '无效的案件ID';
    redirect('cases.php');
}

// 初始化模型
$caseModel = new CaseModel($pdo);
$logModel = new Log($pdo);

// 检查权限并获取案件信息
if ($_SESSION['is_admin']) {
    $case = $caseModel->getCaseById($caseId);
} else {
    $case = $caseModel->getUserCaseById($caseId, $_SESSION['user_id']);
}

if (!$case) {
    $_SESSION['error'] = '案件不存在或您没有权限查看此案件';
    redirect('cases.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_record'])) {
    if (!$_SESSION['is_admin'] && !$caseModel->isCaseBelongsToUser($caseId, $_SESSION['user_id'])) {
        $_SESSION['error'] = '您没有权限添加跟踪记录';
        redirect('cases.php');
    }

    $trackDate = trim($_POST['track_date'] ?? date('Y-m-d'));
    $trackRecord = trim($_POST['track_record'] ?? '');
    
    if (empty($trackRecord)) {
        $_SESSION['error'] = '跟踪记录不能为空';
    } else {
        try {
            // 获取当前跟踪信息
            $currentTrackRecord = $case['last_track_record'] ?? '';
            
            // 更新跟踪信息（不更新case_progress字段）
            if ($caseModel->updateCaseTrackingOnly($caseId, $trackDate, $trackRecord)) {
                // 记录操作日志
                $logModel->addLog(
                    'update',
                    'cases',
                    $caseId,
                    'last_track_record',
                    $currentTrackRecord,
                    $trackRecord,
                    $_SESSION['user_id']
                );
                
                // 添加新的进展条目到单独的表
                $progressEntry = "[{$trackDate}] 跟踪记录: {$trackRecord}";
                $caseModel->addCaseProgressEntry($caseId, $progressEntry);
                
                $_SESSION['success'] = '跟踪记录已更新';
                redirect("case_tracking.php?id={$caseId}");
            } else {
                $_SESSION['error'] = '更新跟踪记录失败';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = '操作失败: ' . $e->getMessage();
            error_log("案件跟踪更新失败: " . $e->getMessage());
        }
    }
}

// 获取案件进展记录
$progressEntries = $caseModel->getCaseProgressEntries($caseId);

// 获取案件操作日志
$logs = $logModel->getRecordLogs('cases', $caseId, 100);
if (!$_SESSION['is_admin']) {
    $logs = array_filter($logs, function($log) {
        return ($log['user_id'] ?? null) == $_SESSION['user_id'];
    });
    $logs = array_values($logs);
}

// 字段名映射
$fieldNameMap = [
    'case_number' => '报案号',
    'policy_number' => '保单号/哈啰订单号',
    'insured_name' => '被保险人',
    'insured_phone' => '被保险人电话',
    'id_card' => '身份证号',
    'age' => '年龄',
    'gender' => '性别',
    'accident_liability' => '事故责任',
    'product_type' => '产品类型',
    'report_time' => '报案时间',
    'accident_time' => '出险时间',
    'accident_province' => '出险省份',
    'accident_city' => '出险城市',
    'accident_district' => '出险区域',
    'address' => '详细地址',
    'accident_reason' => '出险原因',
    'accident_type' => '事故类型',
    'accident_description' => '事故经过',
    'medical_status' => '就医情况',
    'injury_part' => '受伤情况',
    'third_party_medical_status' => '三者就医情况',
    'third_party_injury_part' => '三者受伤部位',
    'estimated_amount' => '估损金额',
    'paid_amount' => '赔付金额',
    'case_status' => '案件状态',
    'close_date' => '结案日期',
    'last_track_date' => '最近跟踪时间',
    'last_track_record' => '最新跟踪记录',
    'remark' => '备注',
    'days_since_last_track' => '距离最新回访天数',
    'days_since_report' => '距离报案时间天数',
    'reporter_name' => '接报案人'
];

// 字段值映射
$fieldValueMap = [
    'case_status' => [
        'pending' => '处理中',
        'completed' => '已完成',
        'rejected' => '已拒绝',
        'paid' => '已赔付',
        'processing' => '处理中',
        'closed' => '已结案'
    ],
    'gender' => [
        'male' => '男',
        'female' => '女',
        'unknown' => '未知'
    ],
    'accident_liability' => [
        'full' => '全责',
        'major' => '主责',
        'equal' => '同责',
        'minor' => '次责',
        'none' => '无责'
    ],
    'medical_status' => [
        'treated' => '已就医',
        'not_treated' => '未就医',
        'emergency' => '急诊'
    ]
];

$title = '案件跟踪 - ' . htmlspecialchars($case['case_number']);
require 'views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <h2 class="mb-4">案件跟踪 <small><?php echo htmlspecialchars($case['case_number']); ?></small></h2>

            <!-- 案件基本信息 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">案件基本信息</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>报案号:</strong> <?php echo htmlspecialchars($case['case_number']); ?></p>
                            <p><strong>被保险人:</strong> <?php echo htmlspecialchars($case['insured_name']); ?></p>
                            <p><strong>联系电话:</strong> <?php echo htmlspecialchars($case['insured_phone']); ?></p>
                            <p><strong>事故经过:</strong> <?php echo htmlspecialchars($case['accident_description'] ?? '无'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>案件状态:</strong> <span class="badge badge-<?php 
                                echo $case['case_status'] === '已赔付' ? 'success' : 
                                    ($case['case_status'] === '处理中' ? 'warning' : 'secondary'); 
                            ?>"><?php echo htmlspecialchars($case['case_status']); ?></span></p>
                            <p><strong>产品类型:</strong> <?php echo htmlspecialchars($case['product_type']); ?></p>
                            <p><strong>报案时间:</strong> <?php echo htmlspecialchars($case['report_time']); ?></p>
                            <p><strong>受伤情况:</strong> <?php echo htmlspecialchars($case['injury_part'] ?? '无'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>最新跟踪日期:</strong> <?php echo htmlspecialchars($case['last_track_date'] ?? '暂无'); ?></p>
                            <p><strong>跟踪记录:</strong> <?php echo htmlspecialchars(mb_substr($case['last_track_record'] ?? '无', 0, 15)) . (mb_strlen($case['last_track_record'] ?? '') > 15 ? '...' : ''); ?></p>
                            <p><strong>距离最新回访:</strong> <?php echo htmlspecialchars($case['days_since_last_track'] ?? '0'); ?> 天</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 添加跟踪记录 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">添加跟踪记录</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="track_date">跟踪日期</label>
                                <input type="date" class="form-control" id="track_date" name="track_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group col-md-9">
                                <label for="track_record">跟踪记录</label>
                                <textarea class="form-control" id="track_record" name="track_record" 
                                          rows="3" required placeholder="请输入详细的跟踪记录..."></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 保存记录
                            </button>
                            <a href="case_view.php?id=<?php echo $caseId; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> 返回案件详情
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 历史跟踪记录 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">历史跟踪记录</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($progressEntries)): ?>
                        <div class="timeline">
                            <?php foreach ($progressEntries as $entry): ?>
                                <div class="timeline-item">
                                    <div class="timeline-content">
                                        <?php echo htmlspecialchars($entry['entry']); ?>
                                        <small class="text-muted d-block mt-1">
                                            <?php echo date('Y-m-d H:i', strtotime($entry['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            暂无历史跟踪记录
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 操作日志 -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">操作日志</h5>
                    <small>最近100条记录</small>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($logs)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="logTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="150">操作时间</th>
                                        <th width="120">操作人</th>
                                        <th width="100">操作类型</th>
                                        <th>变更内容</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="log-row">
                                            <td class="no-click"><?php echo htmlspecialchars($log['operation_time'] ?? ''); ?></td>
                                            <td class="no-click"><?php echo htmlspecialchars($log['username'] ?? '系统'); ?></td>
                                            <td class="no-click">
                                                <span class="badge badge-<?php 
                                                    echo $log['operation_type'] === 'create' ? 'success' : 
                                                        ($log['operation_type'] === 'update' ? 'primary' : 'info'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($log['operation_type'] === 'create' ? '创建' : 
                                                        ($log['operation_type'] === 'update' ? '更新' : $log['operation_type'])); ?>
                                                </span>
                                            </td>
                                            <td class="no-click">
                                                <?php if (!empty($log['field_name'])): ?>
                                                    <?php 
                                                    $fieldName = $fieldNameMap[$log['field_name']] ?? $log['field_name'];
                                                    $oldValue = $log['old_value'] ?? '空';
                                                    $newValue = $log['new_value'] ?? '空';
                                                    
                                                    if (isset($fieldValueMap[$log['field_name']])) {
                                                        $oldValue = $fieldValueMap[$log['field_name']][$oldValue] ?? $oldValue;
                                                        $newValue = $fieldValueMap[$log['field_name']][$newValue] ?? $newValue;
                                                    }
                                                    
                                                    if (in_array($log['field_name'], ['report_time', 'accident_time', 'close_date', 'last_track_date'])) {
                                                        $oldValue = $oldValue !== '空' ? date('Y-m-d H:i', strtotime($oldValue)) : $oldValue;
                                                        $newValue = $newValue !== '空' ? date('Y-m-d H:i', strtotime($newValue)) : $newValue;
                                                    }
                                                    
                                                    if (in_array($log['field_name'], ['estimated_amount', 'paid_amount'])) {
                                                        $oldValue = $oldValue !== '空' ? '¥' . number_format($oldValue, 2) : $oldValue;
                                                        $newValue = $newValue !== '空' ? '¥' . number_format($newValue, 2) : $newValue;
                                                    }
                                                    ?>
                                                    <div class="log-change-item">
                                                        <strong><?php echo htmlspecialchars($fieldName); ?>:</strong>
                                                        <div class="change-details">
                                                            <span class="from">从 <?php echo htmlspecialchars($oldValue); ?></span>
                                                            <span class="to">改为 <?php echo htmlspecialchars($newValue); ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($log['operation_type'] === 'create' ? '创建了新案件' : '执行了操作'); ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            暂无操作日志
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
    margin-left: 15px;
    border-left: 2px solid #2c7be5;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #2c7be5;
    z-index: 1;
}

.timeline-content {
    position: relative;
    background: #f8f9fa;
    padding: 12px 15px;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

#logTable {
    width: 100%;
    border-collapse: collapse;
}

#logTable th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
}

.log-row {
    cursor: default !important;
    pointer-events: none !important;
    transition: none !important;
}

.log-row:hover {
    background-color: inherit !important;
}

.no-click {
    pointer-events: none;
    user-select: none;
}

.log-change-item {
    margin-bottom: 8px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.log-change-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.change-details {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 5px;
}

.from, .to {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.from {
    background-color: #fff5f5;
    border-left: 3px solid #ff6b6b;
    color: #c92a2a;
}

.to {
    background-color: #ebfbee;
    border-left: 3px solid #40c057;
    color: #2b8a3e;
}

@media (max-width: 768px) {
    .change-details {
        flex-direction: column;
        gap: 5px;
    }
    
    #logTable th, #logTable td {
        padding: 8px 5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logTable = document.getElementById('logTable');
    if (logTable) {
        logTable.style.pointerEvents = 'none';
        
        logTable.querySelectorAll('*').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }, true);
        });
    }

    document.body.addEventListener('click', function(e) {
        if (e.target.closest('#logTable')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, true);
    
    document.addEventListener('contextmenu', function(e) {
        if (e.target.closest('#logTable')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php 
require 'views/footer.php';
?>