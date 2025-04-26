<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$caseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($caseId <= 0) {
    $_SESSION['error'] = '无效的案件ID';
    redirect('cases.php');
}

$caseModel = new CaseModel($pdo);
$isAdmin = isAdmin();
$userId = $_SESSION['user_id'];

$case = $caseModel->getCaseById($caseId, !$isAdmin, $userId);
if (!$case) {
    $_SESSION['error'] = '无权查看此案件或案件不存在';
    redirect('cases.php');
}

function formatViewDate($dateString) {
    if (empty($dateString) || $dateString == '0000-00-00') {
        return '未填写';
    }
    try {
        $date = new DateTime($dateString);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return '日期无效';
    }
}

function displayValue($value) {
    return !empty($value) ? safeOutput($value) : '<span class="text-muted">未填写</span>';
}

$title = '查看案件 - ' . safeOutput($case['case_number']);
require 'views/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">案件详情</h4>
            <div>
                <a href="case_edit.php?id=<?= $caseId ?>" class="btn btn-sm btn-warning me-2">编辑</a>
                <a href="cases.php" class="btn btn-sm btn-light">返回列表</a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- 基本信息 -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">基本信息</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">报案号</th>
                                    <td><?= displayValue($case['case_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>保单号</th>
                                    <td><?= displayValue($case['policy_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>被保险人</th>
                                    <td><?= displayValue($case['insured_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>联系电话</th>
                                    <td><?= displayValue($case['insured_phone']) ?></td>
                                </tr>
                                <tr>
                                    <th>身份证号</th>
                                    <td><?= displayValue($case['id_card']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">补充信息</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">年龄</th>
                                    <td><?= displayValue($case['age']) ?></td>
                                </tr>
                                <tr>
                                    <th>性别</th>
                                    <td><?= displayValue($case['gender']) ?></td>
                                </tr>
                                <tr>
                                    <th>产品类型</th>
                                    <td><?= displayValue($case['product_type']) ?></td>
                                </tr>
                                <tr>
                                    <th>案件状态</th>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $case['case_status'] === '已赔付' ? 'success' : 
                                            ($case['case_status'] === '处理中' ? 'warning' : 'secondary') 
                                        ?>">
                                            <?= safeOutput($case['case_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>报案人</th>
                                    <td><?= displayValue($case['reporter_name']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 事故信息 -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">事故信息</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">报案时间</th>
                                    <td><?= formatDateTime($case['report_time']) ?></td>
                                </tr>
                                <tr>
                                    <th>事故时间</th>
                                    <td><?= formatDateTime($case['accident_time']) ?></td>
                                </tr>
                                <tr>
                                    <th>事故责任</th>
                                    <td><?= displayValue($case['accident_liability']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">事故省份</th>
                                    <td><?= displayValue($case['accident_province']) ?></td>
                                </tr>
                                <tr>
                                    <th>事故城市</th>
                                    <td><?= displayValue($case['accident_city']) ?></td>
                                </tr>
                                <tr>
                                    <th>事故区县</th>
                                    <td><?= displayValue($case['accident_district']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="15%">详细地址</th>
                                    <td><?= displayValue($case['address']) ?></td>
                                </tr>
                                <tr>
                                    <th>事故原因</th>
                                    <td><?= displayValue($case['accident_reason']) ?></td>
                                </tr>
                                <tr>
                                    <th>事故类型</th>
                                    <td><?= displayValue($case['accident_type']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label fw-bold">事故描述</label>
                        <div class="border p-3 bg-light rounded"><?= nl2br(displayValue($case['accident_description'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- 医疗和赔付信息 -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">医疗和赔付信息</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">医疗状态</th>
                                    <td><?= displayValue($case['medical_status']) ?></td>
                                </tr>
                                <tr>
                                    <th>受伤部位</th>
                                    <td><?= displayValue($case['injury_part']) ?></td>
                                </tr>
                                <tr>
                                    <th>第三方医疗状态</th>
                                    <td><?= displayValue($case['third_party_medical_status']) ?></td>
                                </tr>
                                <tr>
                                    <th>第三方受伤部位</th>
                                    <td><?= displayValue($case['third_party_injury_part']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">估损金额</th>
                                    <td><?= !empty($case['estimated_amount']) ? '¥' . number_format($case['estimated_amount'], 2) : '未填写' ?></td>
                                </tr>
                                <tr>
                                    <th>赔付金额</th>
                                    <td><?= !empty($case['paid_amount']) ? '¥' . number_format($case['paid_amount'], 2) : '未填写' ?></td>
                                </tr>
                                <tr>
                                    <th>结案日期</th>
                                    <td><?= formatViewDate($case['close_date']) ?></td>
                                </tr>
                                <tr>
                                    <th>最后跟踪日期</th>
                                    <td><?= formatViewDate($case['last_track_date']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 跟踪记录和备注 -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">跟踪记录和备注</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">最后跟踪记录</label>
                        <div class="border p-3 bg-light rounded"><?= nl2br(displayValue($case['last_track_record'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">案件进展</label>
                        <div class="border p-3 bg-light rounded"><?= nl2br(displayValue($case['case_progress'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">备注</label>
                        <div class="border p-3 bg-light rounded"><?= nl2br(displayValue($case['remark'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- 系统信息 -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">系统信息</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-bordered">
                        <tr>
                            <th width="20%">创建时间</th>
                            <td><?= formatDateTime($case['created_at']) ?></td>
                            <th width="20%">最后更新时间</th>
                            <td><?= formatDateTime($case['updated_at']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 操作按钮 -->
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="case_edit.php?id=<?= $caseId ?>" class="btn btn-warning me-2">
                        <i class="bi bi-pencil"></i> 编辑案件
                    </a>
                    <a href="case_tracking.php?id=<?= $caseId ?>" class="btn btn-info me-2">
                        <i class="bi bi-journal-plus"></i> 添加跟踪
                    </a>
                </div>
                <a href="cases.php" class="btn btn-secondary">
                    <i class="bi bi-list-ul"></i> 返回列表
                </a>
            </div>
        </div>
    </div>
</div>

<?php require 'views/footer.php'; ?>