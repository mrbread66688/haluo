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
    $_SESSION['error'] = '无权编辑此案件或案件不存在';
    redirect('cases.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['id'] = $caseId;
    
    if (!empty($data['insured_phone']) && !preg_match('/^1[3-9]\d{9}$/', $data['insured_phone'])) {
        $error = '请输入有效的手机号码';
    }
    
    if (!empty($data['id_card']) && !preg_match('/(^\d{15}$)|(^\d{17}([0-9]|X)$)/', $data['id_card'])) {
        $error = '请输入有效的身份证号码';
    }
    
    if (!$error) {
        if (!empty($data['report_time'])) {
            $data['report_time'] = date('Y-m-d H:i:s', strtotime($data['report_time']));
        }
        if (!empty($data['accident_time'])) {
            $data['accident_time'] = date('Y-m-d H:i:s', strtotime($data['accident_time']));
        }
        
        // 修改后的调用方式，只传递$data数组
        if ($caseModel->updateCase($data)) {
            $_SESSION['success'] = '案件更新成功';
            redirect('case_view.php?id=' . $caseId);
        } else {
            $error = '案件更新失败，请重试';
        }
    }
}

$title = '编辑案件 - ' . safeOutput($case['case_number']);
require 'views/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">编辑案件</h4>
            <div>
                <a href="case_view.php?id=<?= $caseId ?>" class="btn btn-sm btn-light me-2">返回查看</a>
                <a href="cases.php" class="btn btn-sm btn-light">返回列表</a>
            </div>
        </div>
        
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= safeOutput($error) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="id" value="<?= $caseId ?>">
                
                <!-- 基本信息 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">基本信息</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">报案号</label>
                                    <input type="text" class="form-control" name="case_number" 
                                           value="<?= safeOutput($case['case_number']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">保单号</label>
                                    <input type="text" class="form-control" name="policy_number" 
                                           value="<?= safeOutput($case['policy_number']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">被保险人</label>
                                    <input type="text" class="form-control" name="insured_name" 
                                           value="<?= safeOutput($case['insured_name']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">联系电话</label>
                                    <input type="tel" class="form-control" name="insured_phone" 
                                           value="<?= safeOutput($case['insured_phone']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">补充信息</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">身份证号</label>
                                    <input type="text" class="form-control" name="id_card" 
                                           value="<?= safeOutput($case['id_card']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">年龄</label>
                                    <input type="number" class="form-control" name="age" 
                                           value="<?= safeOutput($case['age']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">性别</label>
                                    <select class="form-select" name="gender">
                                        <option value="">请选择</option>
                                        <option value="男" <?= $case['gender'] === '男' ? 'selected' : '' ?>>男</option>
                                        <option value="女" <?= $case['gender'] === '女' ? 'selected' : '' ?>>女</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">产品类型</label>
                                    <input type="text" class="form-control" name="product_type" 
                                           value="<?= safeOutput($case['product_type']) ?>">
                                </div>
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
                                <div class="mb-3">
                                    <label class="form-label">报案时间</label>
                                    <input type="datetime-local" class="form-control" name="report_time" 
                                           value="<?= !empty($case['report_time']) ? date('Y-m-d\TH:i', strtotime($case['report_time'])) : '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">事故时间</label>
                                    <input type="datetime-local" class="form-control" name="accident_time" 
                                           value="<?= !empty($case['accident_time']) ? date('Y-m-d\TH:i', strtotime($case['accident_time'])) : '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">事故责任</label>
                                    <select class="form-select" name="accident_liability">
                                        <option value="">请选择</option>
                                        <option value="全责" <?= $case['accident_liability'] === '全责' ? 'selected' : '' ?>>全责</option>
                                        <option value="主责" <?= $case['accident_liability'] === '主责' ? 'selected' : '' ?>>主责</option>
                                        <option value="同责" <?= $case['accident_liability'] === '同责' ? 'selected' : '' ?>>同责</option>
                                        <option value="次责" <?= $case['accident_liability'] === '次责' ? 'selected' : '' ?>>次责</option>
                                        <option value="无责" <?= $case['accident_liability'] === '无责' ? 'selected' : '' ?>>无责</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">事故省份</label>
                                    <input type="text" class="form-control" name="accident_province" 
                                           value="<?= safeOutput($case['accident_province']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">事故城市</label>
                                    <input type="text" class="form-control" name="accident_city" 
                                           value="<?= safeOutput($case['accident_city']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">事故区县</label>
                                    <input type="text" class="form-control" name="accident_district" 
                                           value="<?= safeOutput($case['accident_district']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">详细地址</label>
                                    <input type="text" class="form-control" name="address" 
                                           value="<?= safeOutput($case['address']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">事故原因</label>
                                    <input type="text" class="form-control" name="accident_reason" 
                                           value="<?= safeOutput($case['accident_reason']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">事故类型</label>
                                    <input type="text" class="form-control" name="accident_type" 
                                           value="<?= safeOutput($case['accident_type']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">事故描述</label>
                            <textarea class="form-control" name="accident_description" rows="4"><?= safeOutput($case['accident_description']) ?></textarea>
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
                                <div class="mb-3">
                                    <label class="form-label">医疗状态</label>
                                    <select class="form-select" name="medical_status">
                                        <option value="">请选择</option>
                                        <option value="门诊治疗" <?= $case['medical_status'] === '门诊治疗' ? 'selected' : '' ?>>门诊治疗</option>
                                        <option value="住院治疗" <?= $case['medical_status'] === '住院治疗' ? 'selected' : '' ?>>住院治疗</option>
                                        <option value="治疗结束" <?= $case['medical_status'] === '治疗结束' ? 'selected' : '' ?>>治疗结束</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">受伤部位</label>
                                    <input type="text" class="form-control" name="injury_part" 
                                           value="<?= safeOutput($case['injury_part']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">第三方医疗状态</label>
                                    <input type="text" class="form-control" name="third_party_medical_status" 
                                           value="<?= safeOutput($case['third_party_medical_status']) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">估损金额 (元)</label>
                                    <input type="number" step="0.01" class="form-control" name="estimated_amount" 
                                           value="<?= safeOutput($case['estimated_amount']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">赔付金额 (元)</label>
                                    <input type="number" step="0.01" class="form-control" name="paid_amount" 
                                           value="<?= safeOutput($case['paid_amount']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">案件状态</label>
                                    <select class="form-select" name="case_status">
                                        <option value="处理中" <?= $case['case_status'] === '处理中' ? 'selected' : '' ?>>处理中</option>
                                        <option value="已赔付" <?= $case['case_status'] === '已赔付' ? 'selected' : '' ?>>已赔付</option>
                                        <option value="撤案" <?= $case['case_status'] === '撤案' ? 'selected' : '' ?>>撤案</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">结案日期</label>
                                    <input type="date" class="form-control" name="close_date" 
                                           value="<?= safeOutput($case['close_date']) ?>">
                                </div>
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
                            <textarea class="form-control" name="last_track_record" rows="4"><?= safeOutput($case['last_track_record']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">案件进展</label>
                            <textarea class="form-control" name="case_progress" rows="4"><?= safeOutput($case['case_progress']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">备注</label>
                            <textarea class="form-control" name="remark" rows="4"><?= safeOutput($case['remark']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 操作按钮 -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-save"></i> 保存修改
                        </button>
                        <a href="case_view.php?id=<?= $caseId ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> 取消
                        </a>
                    </div>
                    <a href="cases.php" class="btn btn-light">
                        <i class="bi bi-list-ul"></i> 返回列表
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'views/footer.php'; ?>