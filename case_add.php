<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/CaseModel.php';
require_once 'classes/RegionModel.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$caseModel = new CaseModel($pdo);
$regionModel = new RegionModel($pdo);
$error = '';
$warnings = [];
$formData = []; // 用于保存表单数据以便错误时回填

// 获取所有省份
$provinces = $regionModel->getProvinces();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    
    // 根据保单号最后一位设置产品类型
    if (!empty($formData['policy_number'])) {
        $lastDigit = substr($formData['policy_number'], -1);
        if ($lastDigit === '1') {
            $formData['product_type'] = '单车意外险';
        } elseif ($lastDigit === '2') {
            $formData['product_type'] = '助动车意外险';
        }
    }
    
    // 确保报案号不为空
    if (empty($formData['case_number'])) {
        $error = '报案号不能为空';
    } else {
        $result = $caseModel->createCase($formData);
        
        if ($result['success']) {
            // 记录操作日志
            $log = new Log($pdo);
            $log->addLog('create', 'Cases', $result['case_id']);
            
            redirect('cases.php');
        } else {
            // 处理重复字段警告
            if (!empty($result['duplicates'])) {
                $fieldNames = [
                    'case_number' => '报案号',
                    'policy_number' => '保单号',
                    'id_card' => '身份证号'
                ];
                
                foreach ($result['duplicates'] as $field => $value) {
                    $warnings[] = "{$fieldNames[$field]} [{$value}] 已存在";
                }
            }
            
            $error = $result['message'] ?: '创建案件失败，请重试';
        }
    }
}

$title = '新增案件';
require 'views/header.php';
?>

<style>
    :root {
        --primary-color: #4a90e2;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --light-bg: #f8f9fa;
    }
    
    body {
        background-color: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: none;
        overflow: hidden;
    }
    
    .card-header {
        border-bottom: none;
        padding: 1.25rem 1.5rem;
    }
    
    .form-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
    }
    
    .form-section h5 {
        color: var(--primary-color);
        font-weight: 600;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        align-items: center;
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
    
    .form-control, .form-select {
        border-radius: 6px;
        padding: 10px 15px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(74, 144, 226, 0.25);
    }
    
    .btn {
        border-radius: 6px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn i {
        margin-right: 8px;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
        background-color: #3a7bc8;
        border-color: #3a7bc8;
    }
    
    .btn-outline-secondary {
        border-color: #ddd;
    }
    
    .btn-outline-secondary:hover {
        background-color: #f8f9fa;
    }
    
    .alert {
        border-radius: 8px;
    }
    
    .invalid-feedback {
        font-size: 0.85rem;
    }
    
    .field-warning {
        font-size: 0.85rem;
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }
        
        .form-section {
            padding: 1rem;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
    }
    
    /* 加载状态样式 */
    .btn-loading {
        position: relative;
        pointer-events: none;
    }
    
    .btn-loading .fa-spinner {
        animation: fa-spin 1s infinite linear;
    }
    
    @keyframes fa-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white" style="border-radius: 10px 10px 0 0 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>新增案件</h4>
                        <a href="cases.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i>返回列表
                        </a>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($warnings)): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2 fs-4"></i>
                            <div>
                                <strong>警告：以下信息已存在，请确认是否继续：</strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <?php foreach ($warnings as $warning): ?>
                                    <li><?php echo $warning; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" class="needs-validation" novalidate>
                        <!-- 基本信息部分 -->
                        <div class="form-section">
                            <h5 class="text-primary">
                                <i class="fas fa-info-circle me-2"></i>基本信息
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="case_number" class="form-label">报案号</label>
                                    <input type="text" class="form-control" id="case_number" name="case_number" 
                                        value="<?php echo htmlspecialchars($formData['case_number'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">请输入报案号</div>
                                    <div class="form-text text-muted">系统唯一标识，请勿重复</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="policy_number" class="form-label">保单号</label>
                                    <input type="text" class="form-control" id="policy_number" name="policy_number" 
                                        value="<?php echo htmlspecialchars($formData['policy_number'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="insured_name" class="form-label">被保险人</label>
                                    <input type="text" class="form-control" id="insured_name" name="insured_name" 
                                        value="<?php echo htmlspecialchars($formData['insured_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-3">
                                <div class="col-md-4">
                                    <label for="insured_phone" class="form-label">被保险人电话</label>
                                    <input type="text" class="form-control" id="insured_phone" name="insured_phone" 
                                        value="<?php echo htmlspecialchars($formData['insured_phone'] ?? ''); ?>">
                                    <small class="form-text text-muted">格式：13800138000</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="id_card" class="form-label">身份证号</label>
                                    <input type="text" class="form-control" id="id_card" name="id_card" 
                                        value="<?php echo htmlspecialchars($formData['id_card'] ?? ''); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="age" class="form-label">年龄</label>
                                    <input type="text" class="form-control" id="age" readonly
                                        value="<?php echo htmlspecialchars($formData['age'] ?? ''); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="gender" class="form-label">性别</label>
                                    <input type="text" class="form-control" id="gender" readonly
                                        value="<?php echo htmlspecialchars($formData['gender'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 事故信息部分 -->
                        <div class="form-section">
                            <h5 class="text-primary">
                                <i class="fas fa-car-crash me-2"></i>事故信息
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="accident_liability" class="form-label">事故责任</label>
                                    <select id="accident_liability" name="accident_liability" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="全责" <?php selected($formData['accident_liability'] ?? '', '全责'); ?>>全责</option>
                                        <option value="主责" <?php selected($formData['accident_liability'] ?? '', '主责'); ?>>主责</option>
                                        <option value="同责" <?php selected($formData['accident_liability'] ?? '', '同责'); ?>>同责</option>
                                        <option value="次责" <?php selected($formData['accident_liability'] ?? '', '次责'); ?>>次责</option>
                                        <option value="无责" <?php selected($formData['accident_liability'] ?? '', '无责'); ?>>无责</option>
                                        <option value="待确定" <?php selected($formData['accident_liability'] ?? '', '待确定'); ?>>待确定</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="product_type" class="form-label">产品类型</label>
                                    <select id="product_type" name="product_type" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="助动车意外险" <?php selected($formData['product_type'] ?? '', '助动车意外险'); ?>>助动车意外险</option>
                                        <option value="单车意外险" <?php selected($formData['product_type'] ?? '', '单车意外险'); ?>>单车意外险</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="report_time" class="form-label">报案时间</label>
                                    <input type="datetime-local" class="form-control" id="report_time" name="report_time" 
                                        value="<?php echo htmlspecialchars($formData['report_time'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="accident_time" class="form-label">出险时间</label>
                                    <input type="datetime-local" class="form-control" id="accident_time" name="accident_time" 
                                        value="<?php echo htmlspecialchars($formData['accident_time'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-3">
                                <div class="col-md-3">
                                    <label for="accident_province" class="form-label">出险省份</label>
                                    <select id="accident_province" name="accident_province" class="form-select">
                                        <option value="">加载省份数据...</option>
                                        <?php if (!empty($provinces)): ?>
                                            <?php foreach ($provinces as $province): ?>
                                            <option value="<?= htmlspecialchars($province['code']) ?>" 
                                                <?php selected($formData['accident_province'] ?? '', $province['code']); ?>>
                                                <?= htmlspecialchars($province['name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="">省份数据加载失败</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="accident_city" class="form-label">出险城市</label>
                                    <select id="accident_city" name="accident_city" class="form-select">
                                        <option value="">请先选择省份</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="accident_district" class="form-label">出险区域</label>
                                    <select id="accident_district" name="accident_district" class="form-select">
                                        <option value="">请先选择城市</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="address" class="form-label">详细地址</label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                        value="<?php echo htmlspecialchars($formData['address'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <label for="accident_reason" class="form-label">出险原因</label>
                                    <select id="accident_reason" name="accident_reason" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="第三方事故" <?php selected($formData['accident_reason'] ?? '', '第三方事故'); ?>>第三方事故</option>
                                        <option value="意外摔伤" <?php selected($formData['accident_reason'] ?? '', '意外摔伤'); ?>>意外摔伤</option>
                                        <option value="质量事故" <?php selected($formData['accident_reason'] ?? '', '质量事故'); ?>>质量事故</option>
                                        <option value="非事故" <?php selected($formData['accident_reason'] ?? '', '非事故'); ?>>非事故</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="accident_type" class="form-label">事故类型</label>
                                    <select id="accident_type" name="accident_type" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="单方事故摔伤" <?php selected($formData['accident_type'] ?? '', '单方事故摔伤'); ?>>单方事故摔伤</option>
                                        <option value="双方事故(对方机动车)" <?php selected($formData['accident_type'] ?? '', '双方事故(对方机动车)'); ?>>双方事故(对方机动车)</option>
                                        <option value="双方事故(对方非机动车)" <?php selected($formData['accident_type'] ?? '', '双方事故(对方非机动车)'); ?>>双方事故(对方非机动车)</option>
                                        <option value="其他报案(非理赔)" <?php selected($formData['accident_type'] ?? '', '其他报案(非理赔)'); ?>>其他报案(非理赔)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label for="accident_description" class="form-label">出险经过</label>
                                <textarea class="form-control" id="accident_description" name="accident_description" rows="3"><?php 
                                    echo htmlspecialchars($formData['accident_description'] ?? ''); 
                                ?></textarea>
                            </div>
                        </div>
                        
                        <!-- 医疗与赔付信息部分 -->
                        <div class="form-section">
                            <h5 class="text-primary">
                                <i class="fas fa-procedures me-2"></i>医疗与赔付信息
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="medical_status" class="form-label">就医情况</label>
                                    <select id="medical_status" name="medical_status" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="未就医" <?php selected($formData['medical_status'] ?? '', '未就医'); ?>>未就医</option>
                                        <option value="门诊医疗" <?php selected($formData['medical_status'] ?? '', '门诊医疗'); ?>>门诊医疗</option>
                                        <option value="轻伤住院" <?php selected($formData['medical_status'] ?? '', '轻伤住院'); ?>>轻伤住院</option>
                                        <option value="重伤" <?php selected($formData['medical_status'] ?? '', '重伤'); ?>>重伤</option>
                                        <option value="残疾" <?php selected($formData['medical_status'] ?? '', '残疾'); ?>>残疾</option>
                                        <option value="身故" <?php selected($formData['medical_status'] ?? '', '身故'); ?>>身故</option>
                                        <option value="轻伤检查" <?php selected($formData['medical_status'] ?? '', '轻伤检查'); ?>>轻伤检查</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="injury_part" class="form-label">标的受伤部位</label>
                                    <input type="text" class="form-control" id="injury_part" name="injury_part" 
                                        value="<?php echo htmlspecialchars($formData['injury_part'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="third_party_injury_part" class="form-label">三者受伤部位</label>
                                    <input type="text" class="form-control" id="third_party_injury_part" name="third_party_injury_part" 
                                        value="<?php echo htmlspecialchars($formData['third_party_injury_part'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-3">
                                <div class="col-md-4">
                                    <label for="third_party_medical_status" class="form-label">三者就医情况</label>
                                    <select id="third_party_medical_status" name="third_party_medical_status" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="未就医" <?php selected($formData['third_party_medical_status'] ?? '', '未就医'); ?>>未就医</option>
                                        <option value="门诊医疗" <?php selected($formData['third_party_medical_status'] ?? '', '门诊医疗'); ?>>门诊医疗</option>
                                        <option value="轻伤住院" <?php selected($formData['third_party_medical_status'] ?? '', '轻伤住院'); ?>>轻伤住院</option>
                                        <option value="重伤" <?php selected($formData['third_party_medical_status'] ?? '', '重伤'); ?>>重伤</option>
                                        <option value="残疾" <?php selected($formData['third_party_medical_status'] ?? '', '残疾'); ?>>残疾</option>
                                        <option value="身故" <?php selected($formData['third_party_medical_status'] ?? '', '身故'); ?>>身故</option>
                                        <option value="轻伤检查" <?php selected($formData['third_party_medical_status'] ?? '', '轻伤检查'); ?>>轻伤检查</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="estimated_amount" class="form-label">估损金额</label>
                                    <div class="input-group">
                                        <span class="input-group-text">¥</span>
                                        <input type="number" step="0.01" class="form-control" id="estimated_amount" name="estimated_amount" 
                                            value="<?php echo htmlspecialchars($formData['estimated_amount'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="paid_amount" class="form-label">赔付金额</label>
                                    <div class="input-group">
                                        <span class="input-group-text">¥</span>
                                        <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" 
                                            value="<?php echo htmlspecialchars($formData['paid_amount'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-3">
                                <div class="col-md-3">
                                    <label for="case_status" class="form-label">案件状态</label>
                                    <select id="case_status" name="case_status" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="撤案" <?php selected($formData['case_status'] ?? '', '撤案'); ?>>撤案</option>
                                        <option value="处理中" <?php selected($formData['case_status'] ?? '', '处理中'); ?>>处理中</option>
                                        <option value="已赔付" <?php selected($formData['case_status'] ?? '', '已赔付'); ?>>已赔付</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="close_date" class="form-label">结案日期</label>
                                    <input type="date" class="form-control" id="close_date" name="close_date" 
                                        value="<?php echo htmlspecialchars($formData['close_date'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 跟踪与备注信息部分 -->
                        <div class="form-section">
                            <h5 class="text-primary">
                                <i class="fas fa-clipboard-list me-2"></i>跟踪与备注信息
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="last_track_date" class="form-label">最近跟踪日期</label>
                                    <input type="date" class="form-control" id="last_track_date" name="last_track_date" 
                                        value="<?php echo htmlspecialchars($formData['last_track_date'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="remark" class="form-label">备注</label>
                                    <select id="remark" name="remark" class="form-select">
                                        <option value="">请选择</option>
                                        <option value="案件受理" <?php selected($formData['remark'] ?? '', '案件受理'); ?>>案件受理</option>
                                        <option value="补充资料" <?php selected($formData['remark'] ?? '', '补充资料'); ?>>补充资料</option>
                                        <option value="理赔中" <?php selected($formData['remark'] ?? '', '理赔中'); ?>>理赔中</option>
                                        <option value="已结案" <?php selected($formData['remark'] ?? '', '已结案'); ?>>已结案</option>
                                        <option value="引导车险(成功)" <?php selected($formData['remark'] ?? '', '引导车险(成功)'); ?>>引导车险(成功)</option>
                                        <option value="引导车险(未决)" <?php selected($formData['remark'] ?? '', '引导车险(未决)'); ?>>引导车险(未决)</option>
                                        <option value="待撤案" <?php selected($formData['remark'] ?? '', '待撤案'); ?>>待撤案</option>
                                        <option value="已撤案(零结案)" <?php selected($formData['remark'] ?? '', '已撤案(零结案)'); ?>>已撤案(零结案)</option>
                                        <option value="已撤案(拒赔)" <?php selected($formData['remark'] ?? '', '已撤案(拒赔)'); ?>>已撤案(拒赔)</option>
                                        <option value="已撤案(技术性)" <?php selected($formData['remark'] ?? '', '已撤案(技术性)'); ?>>已撤案(技术性)</option>
                                        <option value="已撤案(非理赔)" <?php selected($formData['remark'] ?? '', '已撤案(非理赔)'); ?>>已撤案(非理赔)</option>
                                        <option value="已撤案(非哈啰车)" <?php selected($formData['remark'] ?? '', '已撤案(非哈啰车)'); ?>>已撤案(非哈啰车)</option>
                                        <option value="诉讼案件" <?php selected($formData['remark'] ?? '', '诉讼案件'); ?>>诉讼案件</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label for="last_track_record" class="form-label">最新跟踪记录</label>
                                <textarea class="form-control" id="last_track_record" name="last_track_record" rows="3"><?php 
                                    echo htmlspecialchars($formData['last_track_record'] ?? ''); 
                                ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="cases.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>返回列表
                            </a>
                            <div>
                                <button type="reset" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-undo me-2"></i>重置表单
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>保存案件
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// 确保jQuery UI已加载
function ensureJQueryUI(callback) {
    if (typeof $.ui === 'undefined') {
        $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js')
            .done(callback)
            .fail(function() {
                console.error('加载jQuery UI失败!');
                // 即使加载失败也执行回调
                callback();
            });
    } else {
        callback();
    }
}

// 初始化地区选择器
function initRegionSelect() {
    const $province = $('#accident_province');
    const $city = $('#accident_city');
    const $district = $('#accident_district');

    // 加载省份
    function loadProvinces() {
        $province.html('<option value="">加载中...</option>').prop('disabled', true);
        
        fetch('api/get_provinces.php')
            .then(response => {
                if (!response.ok) throw new Error('网络响应不正常');
                return response.json();
            })
            .then(data => {
                if (data.status !== 'success') {
                    throw new Error(data.message || '获取省份数据失败');
                }
                
                $province.html('<option value="">请选择省份</option>');
                data.data.forEach(province => {
                    $province.append(`<option value="${province.code}">${province.name}</option>`);
                });
                
                // 恢复选中值
                const selectedValue = "<?= htmlspecialchars($formData['accident_province'] ?? '') ?>";
                if (selectedValue) {
                    $province.val(selectedValue).trigger('change');
                }
            })
            .catch(error => {
                console.error('加载省份失败:', error);
                $province.html('<option value="">加载失败，点击重试</option>')
                    .on('click', function() {
                        $(this).off('click');
                        loadProvinces();
                    });
            })
            .finally(() => {
                $province.prop('disabled', false);
            });
    }

    // 加载城市
    function loadCities(provinceCode) {
        if (!provinceCode) return;
        
        $city.html('<option value="">加载中...</option>').prop('disabled', true);
        
        fetch(`api/get_cities.php?province_code=${encodeURIComponent(provinceCode)}`)
            .then(response => {
                if (!response.ok) throw new Error('网络响应不正常');
                return response.json();
            })
            .then(data => {
                if (data.status !== 'success') {
                    throw new Error(data.message || '获取城市数据失败');
                }
                
                $city.html('<option value="">请选择城市</option>');
                data.data.forEach(city => {
                    $city.append(`<option value="${city.code}">${city.name}</option>`);
                });
                
                // 恢复选中值
                const selectedValue = "<?= htmlspecialchars($formData['accident_city'] ?? '') ?>";
                if (selectedValue) {
                    $city.val(selectedValue).trigger('change');
                }
            })
            .catch(error => {
                console.error('加载城市失败:', error);
                $city.html('<option value="">加载失败</option>');
            })
            .finally(() => {
                $city.prop('disabled', false);
            });
    }

    // 加载区县
    function loadDistricts(cityCode) {
        if (!cityCode) return;
        
        $district.html('<option value="">加载中...</option>').prop('disabled', true);
        
        fetch(`api/get_districts.php?city_code=${encodeURIComponent(cityCode)}`)
            .then(response => {
                if (!response.ok) throw new Error('网络响应不正常');
                return response.json();
            })
            .then(data => {
                if (data.status !== 'success') {
                    throw new Error(data.message || '获取区县数据失败');
                }
                
                $district.html('<option value="">请选择区县</option>');
                data.data.forEach(district => {
                    $district.append(`<option value="${district.code}">${district.name}</option>`);
                });
                
                // 恢复选中值
                const selectedValue = "<?= htmlspecialchars($formData['accident_district'] ?? '') ?>";
                if (selectedValue) {
                    $district.val(selectedValue);
                }
            })
            .catch(error => {
                console.error('加载区县失败:', error);
                $district.html('<option value="">加载失败</option>');
            })
            .finally(() => {
                $district.prop('disabled', false);
            });
    }

    // 事件绑定
    $province.on('change', function() {
        loadCities($(this).val());
        $district.html('<option value="">请先选择城市</option>');
    });

    $city.on('change', function() {
        loadDistricts($(this).val());
    });

    // 初始加载
    loadProvinces();
}

// 检查字段是否重复
function checkDuplicate(field, value, fieldName) {
    if (!value) return Promise.resolve(false);
    
    return fetch('api/check_duplicate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `field=${field}&value=${encodeURIComponent(value)}`
    })
    .then(response => {
        if (!response.ok) throw new Error('网络响应不正常');
        return response.json();
    })
    .then(data => {
        if (data.exists) {
            showFieldWarning(field, `${fieldName}已存在`);
            return true;
        } else {
            clearFieldWarning(field);
            return false;
        }
    })
    .catch(error => {
        console.error('检查重复失败:', error);
        return false;
    });
}

// 显示字段警告
function showFieldWarning(fieldId, message) {
    const $input = $(`#${fieldId}`);
    let $warning = $input.next('.field-warning');
    
    if ($warning.length === 0) {
        $warning = $(`<div class="field-warning text-danger small mt-1"></div>`);
        $input.after($warning);
    }
    
    $warning.text(message);
    $input.addClass('is-invalid');
}

// 清除字段警告
function clearFieldWarning(fieldId) {
    $(`#${fieldId}`).removeClass('is-invalid')
        .next('.field-warning').remove();
}

// 页面初始化
$(document).ready(function() {
    // 初始化日期选择器
    ensureJQueryUI(function() {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    });

    // 初始化地区选择
    initRegionSelect();

    // 身份证自动计算
    $('#id_card').on('change', function() {
        const idCard = this.value;
        if (idCard.length === 18) {
            const birthYear = idCard.substring(6, 10);
            $('#age').val(new Date().getFullYear() - birthYear);
            $('#gender').val(idCard.substring(16, 17) % 2 === 0 ? '女' : '男');
        }
    });

    // 保单号自动设置
    $('#policy_number').on('change', function() {
        const lastDigit = this.value.slice(-1);
        if (lastDigit === '1') $('#product_type').val('单车意外险');
        if (lastDigit === '2') $('#product_type').val('助动车意外险');
    });

    // 自动设置时间
    if (!$('#report_time').val()) {
        const now = new Date();
        $('#report_time').val(now.toISOString().slice(0, 16));
        $('#accident_time').val(new Date(now.getTime() - 3600000).toISOString().slice(0, 16));
    }

    // 金额输入框自动保留两位小数
    $('#estimated_amount, #paid_amount').on('blur', function() {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });

    // 关键字段重复检查
    const fieldsToCheck = {
        'case_number': '报案号',
        'policy_number': '保单号',
        'id_card': '身份证号'
    };

    Object.keys(fieldsToCheck).forEach(field => {
        $(`#${field}`).on('blur', function() {
            checkDuplicate(field, this.value, fieldsToCheck[field]);
        });
    });

    // 表单提交处理
    $('form').on('submit', function(e) {
        // 阻止默认提交行为
        e.preventDefault();
        
        const form = this;
        const submitBtn = $(form).find('button[type="submit"]');
        
        // 设置加载状态
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>处理中...');
        
        // 检查报案号是否为空
        if ($('#case_number').val().trim() === '') {
            e.stopPropagation();
            $('#case_number').addClass('is-invalid');
            $('#case_number').after('<div class="invalid-feedback">请输入报案号</div>');
            
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="fas fa-save me-2"></i>保存案件');
            return;
        }
        
        // 检查字段重复
        const checkPromises = [];
        Object.keys(fieldsToCheck).forEach(field => {
            const value = $(`#${field}`).val();
            if (value) {
                checkPromises.push(
                    checkDuplicate(field, value, fieldsToCheck[field])
                );
            }
        });
        
        // 等待所有重复检查完成
        Promise.all(checkPromises).then(results => {
            const hasDuplicates = results.some(result => result);
            
            if (hasDuplicates) {
                // 显示重复警告
                $('.submit-warning').remove();
                
                const duplicateMessages = [];
                results.forEach((isDuplicate, index) => {
                    if (isDuplicate) {
                        const field = Object.keys(fieldsToCheck)[index];
                        duplicateMessages.push(fieldsToCheck[field]);
                    }
                });
                
                const warningDiv = $(`
                    <div class="alert alert-warning alert-dismissible fade show submit-warning">
                        <strong>警告！</strong> 以下字段值已存在：${duplicateMessages.join('、')}。确认要继续提交吗？
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <div class="mt-2">
                            <button type="button" class="btn btn-warning btn-sm confirm-submit">确认提交</button>
                            <button type="button" class="btn btn-secondary btn-sm ms-2" data-bs-dismiss="alert">取消</button>
                        </div>
                    </div>
                `);
                
                $('.card-body').prepend(warningDiv);
                
                // 恢复按钮状态
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="fas fa-save me-2"></i>保存案件');
                
                // 确认提交处理
                $('.confirm-submit').on('click', function() {
                    submitBtn.prop('disabled', true);
                    submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>处理中...');
                    form.submit();
                });
            } else {
                // 没有重复，直接提交表单
                form.submit();
            }
        }).catch(error => {
            console.error('检查重复时出错:', error);
            // 出错时也允许提交
            form.submit();
        });
    });
});
</script>

<?php require 'views/footer.php'; ?>